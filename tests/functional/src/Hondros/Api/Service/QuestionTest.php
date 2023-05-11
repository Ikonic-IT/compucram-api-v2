<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 3/29/17
 * Time: 6:24 PM
 */

namespace Hondros\Functional\Api\Service;

use Hondros\Api\Model\Entity;
use Hondros\Api\Util\Helper\EntityGeneratorUtil;
use Mockery as m;
use Hondros\Test\FunctionalAbstract;
use ReflectionClass;

class QuestionTest extends FunctionalAbstract
{
    use EntityGeneratorUtil {}

    /**
     * @var \Hondros\Api\Service\Question
     */
    protected $questionService;

    /**
     * @var \Mockery\Mock
     */
    protected $elasticsearchMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->questionService = $this->getServiceManager()->get('questionService');

        $this->elasticsearchMock = m::mock('\Elasticsearch\Client');

        // replace mailChimp with mock
        $reflectionClass = new ReflectionClass($this->questionService);
        $property = $reflectionClass->getProperty('elasticsearch');
        $property->setAccessible(true);
        $property->setValue($this->questionService, $this->elasticsearchMock);
    }

    protected function tearDown(): void
    {
        $this->questionService = null;
        $this->elasticsearchMock = null;

        m::close();

        parent::tearDown();
    }

    /**
     * @param int $questionId
     * @param string $errorMessage
     * @dataProvider dataProviderAudit
     * @todo need to make sure we are adding created by and modified by
     */
    public function testAudit($questionId, $errorMessage)
    {
        try {
            $response = $this->questionService->findAudits($questionId);
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertEquals($errorMessage, $e->getMessage());

            return;
        }

        // clean up before we start
        $this->getEntityManager()->clear();

        if ($errorMessage == 'empty') {
            $this->assertEmpty($response);
            return;
        }

        if (!empty($errorMessage)) {
            $this->fail("Shouldn't be here if there is an error message");
        }

        $this->assertNotEmpty($response);
        $this->assertCount(2, $response);

        $audits = $response->getArrayCopy();
        $this->assertEquals('question_text', $audits[0]['columnName']);
        $this->assertEquals('hi hi hi', $audits[0]['afterValue']);
        $this->assertEquals('feedback', $audits[1]['columnName']);
        $this->assertEquals('panda power', $audits[1]['afterValue']);
    }

    /**
     * @param $params
     * @param $errorMessage
     * @dataProvider dataProviderCreate
     */
    public function testCreate($params, $errorMessage)
    {
        try {
            $response = $this->questionService->save($params);
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertEquals($errorMessage, $e->getMessage());

            return;
        }

        // clean up before we start
        $this->getEntityManager()->clear();

        if (!empty($errorMessage)) {
            $this->fail("Shouldn't be here if there is an error message");
        }

        $this->assertNotEmpty($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertGreaterThan(0, $response['id']);
        $this->assertEquals($params['type'], $response['type']);
        $this->assertEquals($params['questionText'], $response['questionText']);
        $this->assertNotNull($response['created']);
        $this->assertEquals($params['active'], $response['active']);

        // test the answers
        /** @var Entity\Answer[] $answers */
        $answers = $this->getServiceManager()->get('answerRepository')->findByQuestionId($response['id']);
        $this->assertNotEmpty($answers);

        if ($params['type'] === Entity\Question::TYPE_VOCAB) {
            $this->assertCount(1, $answers);
        } else if ($params['type'] === Entity\Question::TYPE_MULTI) {
            $this->assertGreaterThan(1, count($answers));
            $this->assertLessThan(5, count($answers));
        } else {
            $this->fail("Shouldn't be in here for number of answers");
        }

        $correctAnswerFound = false;
        $currentAnswerIndex = 0;
        foreach ($answers as $answer) {
            $this->assertEquals($params['answers'][$currentAnswerIndex], $answer->getAnswerText());

            if ($params['correctAnswerIndex'] == $currentAnswerIndex) {
                $this->assertEquals(true, $answer->getCorrect());
                $correctAnswerFound = true;
            }
            $currentAnswerIndex++;
        }

        $this->assertTrue($correctAnswerFound);
    }

    /**
     * @param int $questionId
     * @param array $params
     * @param string $errorMessage
     * @dataProvider dataProviderUpdate
     */
    public function testUpdate($questionId, $params, $errorMessage)
    {
        try {
            /** @var \Hondros\Common\DoctrineCollection $response */
            $response = $this->questionService->update($questionId, $params);
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertEquals($errorMessage, $e->getMessage(), $e->getTraceAsString());

            return;
        }

        // clean up before we start
        $this->getEntityManager()->clear();

        if (!empty($errorMessage)) {
            $this->fail("Shouldn't be here if there is an error message");
        }

        $this->assertNotEmpty($response);
        $question = $response->getArrayCopy();

        /** @var Entity\Question $questionEntity */
        $questionEntity = $this->getServiceManager()->get('questionRepository')->findOneById($questionId);

        $this->assertEquals($questionEntity->getType(), $question['type']);
        $this->assertEquals($questionEntity->getQuestionBankId(), $question['questionBankId']);
        $this->assertEquals($questionEntity->getQuestionText(), $question['questionText']);
        $this->assertEquals($questionEntity->getAudioHash(), $question['audioHash']);
        $this->assertEquals($questionEntity->getAudioFile(), $question['audioFile']);

        $modifiedFormatted = (new \DateTime())->setTimestamp($question['modified'])->format('m-d-Y');
        $this->assertEquals($questionEntity->getModified()->format('m-d-Y'), $modifiedFormatted);
    }

    /**
     * @param int $questionBankId
     * @param array $params
     * @param string $errorMessage
     * @dataProvider dataProviderFindForQuestionBank
     */
    public function testFindForQuestionBank($questionBankId, $params, $errorMessage)
    {
        try {
            /** @var \Hondros\Common\DoctrineCollection $response */
            $response = $this->questionService->findForQuestionBank($questionBankId, $params);
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertEquals($errorMessage, $e->getMessage(), $e->getTraceAsString());

            return;
        }

        // clean up before we start
        $this->getEntityManager()->clear();

        if (!empty($errorMessage)) {
            $this->fail("Shouldn't be here if there is an error message");
        }

        $this->assertNotEmpty($response);
        $this->assertInstanceOf('Hondros\Common\DoctrineCollection', $response);

        /** @var Entity\Question[] $allQuestionsForBank */
        $allQuestionsForBank = $this->getServiceManager()->get('questionRepository')
            ->findByQuestionBankId($questionBankId);
        $questions = $response->getArrayCopy();

        if (empty($params['page']) && empty($params['pageSize'])) {
            $this->assertCount(count($allQuestionsForBank), $questions);
        } else if(!empty($params['page']) && !empty($params['pageSize'])) {
            $this->assertCount($params['pageSize'], $questions);
            $index = (int) $params['page'] * (int) $params['pageSize'] - 1;
            $this->assertEquals($allQuestionsForBank[$index]->getId(), $questions[0]['id']);
        } else if (!empty($params['pageSize'])) {
            $this->assertCount($params['pageSize'], $questions);
        }

        foreach ($questions as $question) {
            /** @var Entity\Question $questionEntity */
            $questionEntity = $this->getServiceManager()->get('questionRepository')->findOneById($question['id']);
            $this->assertEquals($questionEntity->getQuestionText(), $question['questionText']);
            $this->assertEquals($questionEntity->getType(), $question['type']);
            $this->assertEquals($questionEntity->getQuestionBankId(), $question['questionBankId']);
            $this->assertEquals($questionEntity->getFeedback(), $question['feedback']);

            // make sure timestamps are close enough
            $diff = abs($questionEntity->getCreated()->getTimestamp() - $question['created']);
            $this->assertLessThan(3, $diff);
            $diff = abs($questionEntity->getModified()->getTimestamp() - $question['modified']);
            $this->assertLessThan(3, $diff);

            $this->assertEquals($questionEntity->getActive(), $question['active']);
            $this->assertEquals($questionEntity->getAudioHash(), $question['audioHash']);
            $this->assertEquals($questionEntity->getAudioFile(), $question['audioFile']);

            $answers = $question['answers'];
            $entityAnswers = $questionEntity->getAnswers();

            $this->assertCount(count($entityAnswers), $answers);
            foreach ($answers as $answer) {
                /** @var Entity\Answer $entityAnswer */
                $entityAnswer = $this->getServiceManager()->get('answerRepository')->findOneById($answer['id']);
                $this->assertEquals($entityAnswer->getAnswerText(), $answer['answerText']);
                $this->assertEquals($entityAnswer->getCorrect(), $answer['correct']);
                $this->assertEquals($entityAnswer->getAudioHash(), $answer['audioHash']);
                $this->assertEquals($entityAnswer->getAudioFile(), $answer['audioFile']);

                // make sure timestamps are close enough
                $diff = abs($entityAnswer->getCreated()->getTimestamp() - $answer['created']);
                $this->assertLessThan(3, $diff);
                $diff = abs($entityAnswer->getModified()->getTimestamp() - $answer['modified']);
                $this->assertLessThan(3, $diff);

                $this->assertEquals($entityAnswer->getQuestionId(), $answer['questionId']);
            }
        }
    }

    /**
     * @param array $params
     * @param array $elasticsearchResponse
     * @param array $questions
     * @param string $errorMessage
     * @dataProvider dataProviderSearch
     */
    public function testSearch($params, $elasticsearchResponse, $questions, $errorMessage)
    {
        if (!is_null($elasticsearchResponse)) {
            $this->elasticsearchMock->shouldReceive('search')->andReturn($elasticsearchResponse);
        }

        try {
            /** @var \Hondros\Common\DoctrineCollection $response */
            $response = $this->questionService->search($params);
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertEquals($errorMessage, $e->getMessage(), $e->getTraceAsString());

            return;
        }

        // clean up before we start
        $this->getEntityManager()->clear();

        if (!empty($errorMessage)) {
            $this->fail("Shouldn't be here if there is an error message");
        }

        $this->assertInstanceOf('ArrayIterator', $response);

        if (empty($questions)) {
            $this->assertCount(0, $response);
            return;
        }

        $this->assertCount(count($questions), $response);
        $this->assertEquals(1, $response->getPagination()->total);
        $this->assertEquals(0, $response->getPagination()->offset);
        $this->assertEquals($questions[0]->getId(), $response[0]['id']);
        $this->assertEquals($questions[0]->getQuestionText(), $response[0]['questionText']);
        $this->assertEquals($questions[0]->getFeedback(), $response[0]['feedback']);
        $this->assertCount(count($questions[0]->getAnswers()), $response[0]['answers']);

        $hydrator = new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\Question(['answers']);
        $questionArray = (new \Hondros\Common\Doctrine())->cleanUp($hydrator->extract($questions[0]));

        $this->assertIsArray($questionArray);
        $this->assertArrayHasKey('answers', $questionArray);

        foreach ($questionArray['answers'] as $answer) {
            $found = false;
            for ($x = 0, $max = $response[0]['answers']; $x < $max; $x++) {
                $responseAnswer = $response[0]['answers'][$x];
                if ($answer['id'] !== $responseAnswer['id']) {
                    continue;
                }

                $found = true;
                $this->assertEquals($answer['answerText'], $responseAnswer['answerText']);
                $this->assertEquals($answer['correct'], $responseAnswer['correct']);
                $this->assertEquals($answer['created'], $responseAnswer['created']);
                $this->assertEquals($answer['audioFile'], $responseAnswer['audioFile']);
                $this->assertEquals($answer['audioHash'], $responseAnswer['audioHash']);

                break;
            }

            $this->assertTrue($found);
        }
    }

    /**
     * Create some questions to make sure we can find them. Mainly to test the caching process.
     * Need to add more logic in a question repository test so we can consolidate some of those method calls.
     *
     * @return array
     */
    public function dataProviderFindForQuestionBank()
    {
        $questionBank1 = $this->createQuestionBank();
        $objects = $this->createStudyQuestionBankWithQuestions();
        $questionBank2 = $objects['questionBank'];

        return [
            'invalid question bank id' => [null, [], 'Invalid question bank id.'],

            'no questions' => [$questionBank1->getId(), [], 'No questions found.'],

            'valid 2 before redis wipe' => [$questionBank2->getId(), [], null],

            'valid 2 after redis wipe' => [$questionBank2->getId(), [], null], // maybe a scenario where we wipe redis out and it all works

            'no questions because of params' => [$questionBank2->getId(), [
                'page' => 1000
            ], 'No questions found.'], // try a scenario where question id is in set but not found

            'valid only one first' => [$questionBank2->getId(), [
                'page' => 1,
                'pageSize' => 1
            ], null],

            'valid only one second' => [$questionBank2->getId(), [
                'page' => 2,
                'pageSize' => 1
            ], null]
        ];
    }

    /**
     * @return array
     */
    public function dataProviderCreate()
    {
        $studyQuestionBank = $this->createQuestionBank(Entity\QuestionBank::TYPE_STUDY);
        $practiceQuestionBank = $this->createQuestionBank(Entity\QuestionBank::TYPE_PRACTICE);
        $examQuestionBank = $this->createQuestionBank(Entity\QuestionBank::TYPE_EXAM);

        return [
            'invalid type' => [[
                'questionBankId' => 1
            ], 'Invalid type.'],

            'invalid question text' => [[
                'questionBankId' => 1,
                'type' => 1
            ], 'Invalid question text.'],

            'invalid active' => [[
                'questionBankId' => 1,
                'type' => 1,
                'questionText' => 'Baby do not hurt me',
            ], 'Invalid active.'],

            'invalid answers' => [[
                'questionBankId' => 1,
                'type' => 1,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
            ], 'Invalid answers.'],

            'invalid answer index' => [[
                'questionBankId' => 1,
                'type' => 1,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good', 'the bad']
            ], 'Invalid answer index.'],

            'invalid question bank not found' => [[
                'questionBankId' => -1,
                'type' => 1,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good', 'the bad'],
                'correctAnswerIndex' => 1
            ], 'Question bank not found.'],

            'invalid vocab wrong type for bank' => [[
                'questionBankId' => $studyQuestionBank->getId(),
                'type' => Entity\Question::TYPE_MULTI,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good', 'the bad'],
                'correctAnswerIndex' => 1
            ], 'Invalid question type for question bank type.'],

            'invalid vocab too many answers' => [[
                'questionBankId' => $studyQuestionBank->getId(),
                'type' => Entity\Question::TYPE_VOCAB,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good', 'the bad'],
                'correctAnswerIndex' => 0
            ], 'Wrong number of answers for question type.'],

            'invalid vocab empty answer' => [[
                'questionBankId' => $studyQuestionBank->getId(),
                'type' => Entity\Question::TYPE_VOCAB,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => [''],
                'correctAnswerIndex' => 0
            ], 'Answer text cannot be empty.'],

            'invalid vocab negative right answer' => [[
                'questionBankId' => $studyQuestionBank->getId(),
                'type' => Entity\Question::TYPE_VOCAB,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good'],
                'correctAnswerIndex' => -1
            ], 'Unable to identify correct answer choice index.'],

            'invalid vocab no right answer' => [[
                'questionBankId' => $studyQuestionBank->getId(),
                'type' => Entity\Question::TYPE_VOCAB,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good'],
                'correctAnswerIndex' => 5
            ], 'Unable to identify correct answer choice index.'],

            'valid vocab' => [[
                'questionBankId' => $studyQuestionBank->getId(),
                'type' => Entity\Question::TYPE_VOCAB,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good'],
                'correctAnswerIndex' => 0
            ], null],

            'invalid multi wrong type for bank' => [[
                'questionBankId' => $practiceQuestionBank->getId(),
                'type' => Entity\Question::TYPE_VOCAB,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good', 'the bad'],
                'correctAnswerIndex' => 1
            ], 'Invalid question type for question bank type.'],

            'invalid multi wrong number of answers too many' => [[
                'questionBankId' => $practiceQuestionBank->getId(),
                'type' => Entity\Question::TYPE_MULTI,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good', 'the bad', 'the ugly', 'the one', 'the wrong',
                    'the good2', 'the bad2', 'the ugly2', 'the one2', 'the wrong2', 'adsf'],
                'correctAnswerIndex' => 0
            ], 'Wrong number of answers for question type.'],

            'invalid multi wrong number of answers not enough' => [[
                'questionBankId' => $practiceQuestionBank->getId(),
                'type' => Entity\Question::TYPE_MULTI,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good'],
                'correctAnswerIndex' => 0
            ], 'Wrong number of answers for question type.'],

            'invalid multi negative right answer' => [[
                'questionBankId' => $practiceQuestionBank->getId(),
                'type' => Entity\Question::TYPE_MULTI,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good', 'the bad', 'the ugly', 'the one'],
                'correctAnswerIndex' => -1
            ], 'Unable to identify correct answer choice index.'],

            'invalid multi no right answer' => [[
                'questionBankId' => $practiceQuestionBank->getId(),
                'type' => Entity\Question::TYPE_MULTI,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good', 'the bad', 'the ugly', 'the one'],
                'correctAnswerIndex' => 5
            ], 'Unable to identify correct answer choice index.'],

            'valid multi' => [[
                'questionBankId' => $practiceQuestionBank->getId(),
                'type' => Entity\Question::TYPE_MULTI,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good', 'the bad', 'the ugly', 'the one'],
                'correctAnswerIndex' => 3
            ], null],

            'valid multi two answers for true and false' => [[
                'questionBankId' => $practiceQuestionBank->getId(),
                'type' => Entity\Question::TYPE_MULTI,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['true', 'false'],
                'correctAnswerIndex' => 0
            ], null],

            'valid multi two answers for true and false and * - should not happen though' => [[
                'questionBankId' => $practiceQuestionBank->getId(),
                'type' => Entity\Question::TYPE_MULTI,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['true', 'false', '*', '*'],
                'correctAnswerIndex' => 0
            ], null],

            'invalid multi exam wrong type for bank' => [[
                'questionBankId' => $examQuestionBank->getId(),
                'type' => Entity\Question::TYPE_VOCAB,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good', 'the bad'],
                'correctAnswerIndex' => 1
            ], 'Invalid question type for question bank type.'],

            'invalid multi exam wrong number of answers' => [[
                'questionBankId' => $examQuestionBank->getId(),
                'type' => Entity\Question::TYPE_MULTI,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good', 'the bad', 'the ugly', 'the one', 'the wrong',
                    'the good2', 'the bad2', 'the ugly2', 'the one2', 'the wrong2', 'asdf'],
                'correctAnswerIndex' => 0
            ], 'Wrong number of answers for question type.'],

            'invalid multi exam negative right answer' => [[
                'questionBankId' => $examQuestionBank->getId(),
                'type' => Entity\Question::TYPE_MULTI,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good', 'the bad', 'the ugly', 'the one'],
                'correctAnswerIndex' => -1
            ], 'Unable to identify correct answer choice index.'],

            'invalid multi exam no right answer' => [[
                'questionBankId' => $examQuestionBank->getId(),
                'type' => Entity\Question::TYPE_MULTI,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good', 'the bad', 'the ugly', 'the one'],
                'correctAnswerIndex' => 5
            ], 'Unable to identify correct answer choice index.'],

            'valid multi exam two answers' => [[
                'questionBankId' => $examQuestionBank->getId(),
                'type' => Entity\Question::TYPE_MULTI,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good', 'the bad'],
                'correctAnswerIndex' => 1
            ], null],

            'valid multi exam three answers' => [[
                'questionBankId' => $examQuestionBank->getId(),
                'type' => Entity\Question::TYPE_MULTI,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good', 'the bad', 'the ugly'],
                'correctAnswerIndex' => 0
            ], null],

            'valid multi exam 4 answers' => [[
                'questionBankId' => $examQuestionBank->getId(),
                'type' => Entity\Question::TYPE_MULTI,
                'questionText' => 'Baby do not hurt me',
                'active' => true,
                'answers' => ['the good', 'the bad', 'the ugly', 'the one'],
                'correctAnswerIndex' => 3
            ], null],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderUpdate()
    {
        $objects = $this->createStudyQuestionBankWithQuestions();
        $question1 = $objects['question1'];
        $question2 = $objects['question2'];

        return [
            'invalid id' => ['asfa', [], 'Invalid question id.'],

            'invalid question not found' => ['12312312123', [], 'Invalid question id.'],

            'valid question 1' => [$question1->getId(), [
                'questionText' => 'new text',
                'feedback' => 'some random feedback',
                'answers' => [
                    'answerText' => 'this should be ignored'
                ]
            ], null],

            'valid question 1 to inactive' => [$question2->getId(), [
                'active' => false
            ], null],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderAudit()
    {
        $objects = $this->createStudyQuestionBankWithQuestions();
        $question1 = $objects['question1'];
        $question2 = $objects['question2'];

        $question2->setQuestionText("hi hi hi");
        $question2->setFeedback("panda power");

        $this->getEntityManager()->merge($question2);
        $this->getEntityManager()->merge($question2);

        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        return [
            'invalid question id' => ['asdf', 'Invalid question id.'],

            'invalid question not found' => [123123123, 'Question not found.'],

            'not audits found' => [$question1->getId(), 'empty'],

            'valid question 2' => [$question2->getId(), null]
        ];
    }

    /**
     * @return array
     */
    public function dataProviderSearch()
    {
        $objects = $this->createStudyQuestionBankWithQuestions();
        $question1 = $objects['question1'];

        return [
            'invalid query' => [[], null, null, 'Invalid query.'],

            'valid no questions' => [[
                'query' => 'good stuff'
            ], [
                'hits' => [
                    'total' => 0
                ]
            ], null, null],

            'valid no questions in repo' => [[
                'query' => 'good stuff'
            ], [
                'hits' => [
                    'total' => [
                        'value' => 1
                    ],
                    'hits' => [
                        ['_id' => -1]
                    ]
                ]
            ], null, null],

            'valid found question' => [[
                'query' => 'good stuff'
            ], [
                'hits' => [
                    'total' => [
                        'value' => 1
                    ],
                    'hits' => [
                        ['_id' => $question1->getId()]
                    ]
                ]
            ], [$question1], null],
        ];
    }

    /**
     * @param $type
     * @return Entity\QuestionBank
     */
    protected function createQuestionBank($type = Entity\QuestionBank::TYPE_PRACTICE)
    {
        $entityManager = $this->getEntityManager();

        $questionBank = $this->generateQuestionBank();
        $questionBank->setType($type);

        $entityManager->persist($questionBank);
        $entityManager->flush();

        // clean up
        $this->getEntityManager()->clear();

        return $questionBank;
    }

    /**
     * quick sample data
     */
    protected function createStudyQuestionBankWithQuestions()
    {
        $questionBank = $this->createQuestionBank(Entity\QuestionBank::TYPE_STUDY);

        $studyQuestion1 = $this->generateQuestionWithAnswers();
        $studyQuestion1->setQuestionBank($questionBank);
        $this->getEntityManager()->persist($studyQuestion1);
        $this->getEntityManager()->persist($studyQuestion1->getAnswers()[0]);

        $studyQuestion2 = $this->generateQuestionWithAnswers();
        $studyQuestion2->setQuestionBank($questionBank);
        $this->getEntityManager()->persist($studyQuestion2);
        $this->getEntityManager()->persist($studyQuestion2->getAnswers()[0]);

        $this->getEntityManager()->persist($questionBank);
        $this->getEntityManager()->flush();

        // clean up
        $this->getEntityManager()->clear();

        return [
            'questionBank' => $questionBank,
            'question1' => $studyQuestion1,
            'question2' => $studyQuestion2
        ];
    }
}
