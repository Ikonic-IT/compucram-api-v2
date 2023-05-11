<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 4/14/17
 * Time: 6:08 PM
 */

namespace Hondros\Api\Console\Job\RunOnce;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Hondros\Api\Model\Entity;
use Laminas\ServiceManager\ServiceManager;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MigrateQuestionBankToModuleQuestionManager
 * @package Hondros\Api\Console\Job\RunOnce
 *
 * This needs to make sure we populate all module_questions for all question banks before we remove that table and
 * related columns. This will be the new structure moving forward that will allow us to share questions.
 */
class MigrateQuestionBankToModuleQuestionManager
{
    /**
     * used as a hack to hide questions
     */
    protected $hiddenQuestionBankId = 0;

    /**
     * @var ServiceManager
     */
    protected $serviceManger = null;

    /**
     * MigrateQuestionBankToModuleQuestionManager constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        $this->serviceManger = $serviceManager;
    }

    /**
     * loop through all module, find the banks, create the module_question for each type and gtg?
     *
     * @return array
     * @throws \Exception
     */
    public function migrateQuestions()
    {
        $migrated = 0;
        /** @var Entity\Module[] $modules */
        $modules = $this->serviceManger->get('moduleRepository')->findAll();

        if (empty($modules)) {
            throw new \Exception("No modules found.");
        }

        foreach ($modules as $module) {
            $types = [
                'study',
                'practice',
                'exam'
            ];

            // @todo add tags?
            foreach ($types as $type) {
                $method = 'get' . ucwords($type) . 'BankId';
                $questions = $this->serviceManger->get('questionRepository')->findByQuestionBankId($module->$method());

                if (empty($questions)) {
                    throw new \Exception("No {$type} questions found for {$module->getName()} {$module->$method()}.");
                }

                $migrated += $this->createModuleQuestionForQuestions($module, $type, $questions);

                // we have been using the same questions for both since we started
                if ($type === 'exam') {
                    $migrated += $this->createModuleQuestionForQuestions($module, 'preassessment', $questions);
                }

                unset($questions);
            }

            unset($module);

            // free up resources
            $this->getEntityManger()->clear();
        }

        unset($modules);

        return [
            'migrated' => $migrated
        ];
    }

    /**
     * These questions should be disabled and not assigned to any module.
     *
     * @return array
     */
    public function disableHiddenQuestions()
    {
        /** @var Entity\Question[] $questions */
        $questions = $this->serviceManger->get('questionRepository')->findByQuestionBankId($this->hiddenQuestionBankId);
        $questionCount = 0;

        foreach ($questions as $question) {
            if (!$question->getActive()) {
                continue;
            }

            $question->setActive(false);
            $this->getEntityManger()->merge($question);
            $questionCount++;
        }

        $this->getEntityManger()->flush();

        unset($questions);

        return [
            'disabled' => $questionCount
        ];
    }

    /**
     * need to identify the dups, then need to update the following to use the correct question id
     *  module_question
     *  progress_question
     *  assessment_attempt_question
     *  module_attempt_question
     *
     * @todo might need to find out what progress_questions are affected so we can update that progress with the right
     *  question count and score and other values
     *
     * @return array
     */
    public function removeDuplicateQuestions(OutputInterface $output)
    {
        // identify all duplicate questions within a question bank
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('ids', 'ids');

        // let mysql find matches for us first, we can narrow it down more later
        $query = $this->getEntityManger()->createNativeQuery('
            select group_concat(id) as ids
            from question
            group by question_text, feedback, question_bank_id
            having count(*) > 1;
        ', $rsm);

        $ids = $query->getResult(AbstractQuery::HYDRATE_ARRAY);
        // track how many were deleted within question bank
        $deletedWithinCount = 0;
        foreach ($ids as $key => $value) {
            $questionIds = explode(',', $value['ids']);

            // really really make sure these match based on answers as well
            $verifiedIds = $this->findMatchingQuestionsAndAnswersByIds($questionIds);

            if (empty($verifiedIds) || count($verifiedIds) === 1) {
                continue;
            }

            $matchingIds[] = $verifiedIds;
            $removeIds = array_slice($verifiedIds, 1);

            // delete things
            $dql = "DELETE Hondros\\Api\\Model\\Entity\\Question q WHERE q.id IN (" . implode(',', $removeIds) . ")";
            $query = $this->getEntityManger()->createQuery($dql);
            $deletedWithinCount += $query->execute();

            $this->getEntityManger()->clear();
        }

        unset($ids);
        $output->writeln("Finished with same question bank questions.");

        // identify all duplicate questions everywhere else
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('ids', 'ids');

        // let mysql find matches for us first, we can narrow it down more later
        $query = $this->getEntityManger()->createNativeQuery('
            select group_concat(id) as ids
            from question
            group by question_text, feedback
            having count(*) > 1;
        ', $rsm);

        $ids = $query->getResult(AbstractQuery::HYDRATE_ARRAY);
        $output->writeln("Found " . count($ids) . " ids.");

        $loops = 0;
        $replacedCount = 0;
        $deletedCount = 0;
        $matchingIds = [];
        $deletedIds = [];

        foreach ($ids as $key => $value) {
            $loops++;
            $questionIds = explode(',', $value['ids']);
            $output->writeln("Starting with " . serialize($questionIds) . ". Loop {$loops}.");

            // really really make sure these match based on answers as well
            $verifiedIds = $this->findMatchingQuestionsAndAnswersByIds($questionIds);

            if (empty($verifiedIds) || count($verifiedIds) === 1) {
                continue;
            }
            $matchingIds[] = $verifiedIds;
            sort($verifiedIds);
            $keeperId = $verifiedIds[0];
            $removeIds = array_slice($verifiedIds, 1);

            // key is the id of the question that will replace all the values
            $deletedIds[$keeperId] = $removeIds;

            if (empty($deletedIds[$keeperId])) {
                continue;
            }

            // update things
            $entities = [
                'ModuleQuestion',
                'ProgressQuestion',
                'AssessmentAttemptQuestion',
                'ModuleAttemptQuestion'
            ];

            foreach ($entities as $entity) {
                $replacedCount += $this->replaceQuestionIds($entity, $keeperId, $removeIds);
            }

            // delete things
            $dql = "DELETE Hondros\\Api\\Model\\Entity\\Question q WHERE q.id IN (" . implode(',', $removeIds) . ")";
            $query = $this->getEntityManger()->createQuery($dql);
            $deletedCount += $query->execute();

            $this->getEntityManger()->clear();
            $output->writeln("Finished with " . serialize($questionIds) . ".");
        }

        return [
            'success' => true,
            'matching' => $matchingIds,
            'deleted' => $deletedIds,
            'replacedCount' => $replacedCount,
            'deletedWithinCount' => $deletedWithinCount,
            'deletedCount' => $deletedCount
        ];
    }

    /**
     * For all question ids passed, only return the ones that truly match based on all content
     *
     * @param array $ids
     * @return bool
     */
    protected function findMatchingQuestionsAndAnswersByIds($ids)
    {
        /** @var Entity\Question[] $questions */
        $questions = $this->serviceManger->get('questionRepository')->findByIdsWithAnswers($ids);

        $hashes = [];
        foreach ($questions as $question) {
            $questionText = $question->getQuestionText() . $question->getFeedback();
            $answers = $question->getAnswers();
            $answerText = null;

            /** @var Entity\Answer $answer */
            foreach ($answers as $answer) {
                $answerText .= $answer->getAnswerText();
            }

            $newHash = md5($questionText . $answerText);

            if (array_key_exists($newHash, $hashes)) {
                array_push($hashes[$newHash], $question->getId());
            } else {
                $hashes[$newHash] = [$question->getId()];
            }
        }

        // return the one that has the most matches
        $returnIds = [];
        foreach ($hashes as $hash) {
            if (count($hash) > count($returnIds)) {
                $returnIds = $hash;
            }
        }

        return $returnIds;
    }

    /**
     * @param $entity
     * @param int $keeperId
     * @param int[] $replaceIds
     * @return int
     */
    protected function replaceQuestionIds($entity, $keeperId, $replaceIds)
    {
        $replacedCount = 0;
        foreach ($replaceIds as $replaceId) {
            $dql = "
            UPDATE Hondros\\Api\\Model\\Entity\\{$entity} e 
            SET e.questionId = {$keeperId} 
            WHERE e.questionId = {$replaceId}
        ";
            $replacedCount += $this->getEntityManger()->createQuery($dql)->execute();
        }

        return $replacedCount;
    }

    /**
     * @param Entity\Module $module
     * @param string $type
     * @param Entity\Question[] $questions
     * @return array
     */
    protected function createModuleQuestionForQuestions($module, $type, $questions)
    {
        $moduleQuestionsCreated = 0;

        /** @var Entity\Question $question */
        foreach ($questions as $question) {
            if (!$question->getActive()) {
                continue;
            }

            $moduleQuestion = new Entity\ModuleQuestion();
            $moduleQuestion->setModule($this->getEntityManger()
                ->getReference('Hondros\Api\Model\Entity\Module', $module->getId()))
                ->setType($type)
                ->setQuestion($this->getEntityManger()
                    ->getReference('Hondros\Api\Model\Entity\Question', $question->getId()));

            $this->getEntityManger()->persist($moduleQuestion);
            $moduleQuestionsCreated++;
        }

        $this->getEntityManger()->flush();

        return $moduleQuestionsCreated;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManger()
    {
        return $this->serviceManger->get('entityManager');
    }
}