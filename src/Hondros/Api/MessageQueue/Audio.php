<?php
namespace Hondros\Api\MessageQueue;

use Laminas\Config\Config;
use Doctrine\ORM\EntityManager;
use Hondros\Api\Model\Repository;
use Hondros\Api\Model\Entity;
use PhpAmqpLib\Message\AMQPMessage;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Predis\Client as  Redis;

class Audio extends JobAbstract
{
    const QUEUE_QUESTIONS_TO_PROCESS = 'questions_to_process';
    const QUEUE_ANSWERS_TO_PROCESS = 'answers_to_process';
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;
    
    /**
     * @var \Hondros\Api\Model\Repository\Question
     */
    protected $questionRepository;
    
    /**
     * @var \Hondros\Api\Model\Repository\Answer
     */
    protected $answerRepository;
    
    public function __construct(Config $config, Redis $cacheAdapter, EntityManager $entityManager, Repository\Question $questionRepository,
        Repository\Answer $answerRepository)
    {
        parent::__construct($config, $cacheAdapter);

        $this->entityManager = $entityManager;
        $this->questionRepository = $questionRepository;
        $this->answerRepository = $answerRepository;
    }

    /**
     * Adds questions and answers to their respective queue to later process and get
     * the audio file from a service
     *
     * @return int Number of items added
     */
    public function addItemsToQueue()
    {
        $total = 0;
        
        // questions
        $offset = 0;
        do {
            $questions = $this->questionRepository->findForStudyWithoutAudio($offset);
            
            // add to queue
            foreach ($questions as $question) {
                $this->addQuestionForProcessing($question);
                $total++;
            }
            
            $offset += 500;

            // clear up uof
            $this->entityManager->getUnitOfWork()->clear();
        } while (!empty($questions));
        
        // answers
        $offset = 0;
        do {
            $answers = $this->answerRepository->findForStudyWithoutAudio($offset);
        
            // add to queue
            foreach ($answers as $answer) {
                $this->addAnswerForProcessing($answer);
                $total++;
            }
        
            $offset += 500;
            
            // clear up uof
            $this->entityManager->getUnitOfWork()->clear();
        } while (!empty($answers));
        
        return $total;
    }

    /**
     * Add question to audio processing queue
     *
     * @param Entity\Question $question
     * @return string
     */
    public function addQuestionForProcessing(Entity\Question $question)
    {
        return $this->addItemForProcessing($question);
    }

    /**
     * Add answer to audio processing queue
     *
     * @param Entity\Answer $answer
     * @return string
     */
    public function addAnswerForProcessing(Entity\Answer $answer)
    {
        return $this->addItemForProcessing($answer);
    }
    
    /**
     * Adds question or answer to their respective queue to later process and get
     * the audio file from a service
     *
     * @param Entity\Question|Entity\Answer $item
     * @return string audio hash
     */
    protected function addItemForProcessing($item)
    {
        // prepare vars
        if ($item instanceof Entity\Question) {
            $queue = self::QUEUE_QUESTIONS_TO_PROCESS;
            $text = $item->getQuestionText();
        } elseif ($item instanceof Entity\Answer) {
            $queue = self::QUEUE_ANSWERS_TO_PROCESS;
            $text = $item->getAnswerText();
        } else {
            $class = get_class($item);
            throw new InvalidArgumentException("Invalid item {$class} submitted for adding to be processed.", 400);
        }
        
        $channel = $this->getConn()->channel();
        $channel->queue_declare($queue, false, false, false, false);
        
        // create unique hash
        $audioHash = md5($text);
        
        // remove all html stuff
        $text = preg_replace("/&#?[a-z0-9]{2,8};/i", "", $text);
        
        // get the parts of the question
        $data = [
            'id' => $item->getId(),
            'audioHash' => $audioHash,
            'text' => $text
        ];
        
        $msg = new AMQPMessage(json_encode($data), [
            'delivery_mode' => 2
        ]);
        $channel->basic_publish($msg, '', $queue);
        
        // close the channel
        $channel->close();
        
        return $audioHash;
    }
    
    public function processQuestionsFromQueue()
    {
        return $this->processItemsFromQueue('question');
    }
    
    public function processAnswersFromQueue()
    {
        return $this->processItemsFromQueue('answer');
    }
    
    /**
     * This method is used to loop over and process any questions text to audio file from third party api
     * 
     * Check on the audioHash to see if there is already a file in the filesystem for it. If so, update the
     * question audioFile property and done. Else, create audio file, save, update question, and done.
     */
    protected function processItemsFromQueue($type)
    {
        // prepare vars
        switch ($type) {
            case 'question':
                $queue = self::QUEUE_QUESTIONS_TO_PROCESS;
                $repo = $this->questionRepository;
            break;
            
            case 'answer':
                $queue = self::QUEUE_ANSWERS_TO_PROCESS;
                $repo = $this->answerRepository;
            break;
            
            default:
                throw new InvalidArgumentException("Invalid type {$type} submitted for processing.", 400);
        }
        
        $channel = $this->getConn()->channel();
        $channel->queue_declare($queue, false, false, false, false);
        
        $callback = function($msg) use ($repo) {
            // get data
            $data = json_decode($msg->body);
            
            // is there already a file for this audioHash
            $path = $this->getPathFromHash($data->audioHash);
            $file = $data->audioHash . '.' . $this->getConfig()->audio->file->format;
            if (!realpath($path . DIRECTORY_SEPARATOR . $file)) {
                // get mp3 file
                $text = urlencode($data->text);
                $audioFile = @file_get_contents("{$this->getConfig()->audio->textToSpeech->apiUrl}?q={$text}");
                
                // errors?
                if ($audioFile === false) {
                    echo "Error loading online file data {$data->audioHash} with text {$data->text}", PHP_EOL;
                    $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], true);
                    return;
                }
                 
                // create dir if not there
                if (!realpath($path)) {
                    mkdir($path, 0775, true);
                }
                
                file_put_contents($path . DIRECTORY_SEPARATOR . $file, $audioFile);
            }
            
            // update the question entities with the same hash
            $item = $repo->find($data->id);
            $item->setAudioHash($data->audioHash);
            $item->setAudioFile($file);
            
            // save
            $this->entityManager->flush();
            
            echo ".";
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

            // clean up
            unset($item);
            
            // clear up uof
            $this->entityManager->getUnitOfWork()->clear();
        };
        
        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($queue, '', false, false, false, false, $callback);
        
        while(count($channel->callbacks)) {
            $channel->wait();
        }        
    }
    
    /**
     * We'll be using a two tier folder structure where the first character of the 
     * hash is the first folder, the first two characters the second folder, and the 
     * file goes inside that second folder.
     * 
     * ex: 9ba808d8d16122d70e44bd7f709d30fb becomes
     * <config path>/9/9b/
     * @param string $audioHash
     * @return string
     */
    protected function getPathFromHash($audioHash)
    {
        return $this->getConfig()->audio->file->path . DIRECTORY_SEPARATOR . substr($audioHash, 0, 1)
            . DIRECTORY_SEPARATOR . substr($audioHash, 0, 2);
    }
}