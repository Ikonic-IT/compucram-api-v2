<?php
/**
 * Created by PhpStorm.
 * User: Joey Rivera
 * Date: 7/19/2015
 * Time: 1:04 PM
 */

namespace Hondros\Api\Util\Helper;

use Doctrine\ORM\EntityManager;
use Hondros\Api\Model\Entity;

trait EntityGeneratorUtil
{
    /**
     * @param string $name
     * @return Entity\Industry
     */
    function generateIndustry($name = null)
    {
        $name = is_null($name) ? 'Real Estate' . uniqid() : $name;

        $obj = new Entity\Industry();
        $obj->setName($name);

        return $obj;
    }

    /**
     * @param string $name
     * @param string $code
     * @return Entity\State
     */
    function generateState($name = null, $code = null)
    {
        $name = is_null($name) ? 'Alabama' . uniqid() : $name;
        $code = is_null($code) ? substr(uniqid(), -2) : $code;

        $obj = new Entity\State();
        $obj->setName($name)
            ->setCode($code);

        return $obj;
    }

    /**
     * @param string $name
     * @param string $code needs to be 10 chars
     * @param string $description
     * @param string $status
     * @return Entity\Module
     */
    function generateModule($name = null, $code = null, $description = null, $status = 'active')
    {
        $name = is_null($name) ? 'My first module' . uniqid() : $name;
        $code = is_null($code) ? 'MFM' . substr(uniqid(), 6) : $code;
        $description = is_null($description) ? 'This is a test module' . uniqid() : $description;
        $status = is_null($status) ? 'active' : $status;

        $obj = new Entity\Module();
        $obj->setName($name)
            ->setCode($code)
            ->setDescription($description)
            ->setStatus($status);

        return $obj;
    }

    /**
     * @param string $name
     * @param int $preassessmentQuestions
     * @param int $practiceQuestions
     * @param int $examQuestions
     * @param int $sort
     * @return Entity\ExamModule
     */
    function generateExamModule($name = null, $preassessmentQuestions = null, $practiceQuestions = null,
        $examQuestions = null, $sort = 0)
    {
        $name = is_null($name) ? 'Test exam module' . uniqid() : $name;
        $preassessmentQuestions = is_null($preassessmentQuestions) ? 5 : $preassessmentQuestions;
        $practiceQuestions = is_null($practiceQuestions) ? 10 : $practiceQuestions;
        $examQuestions = is_null($examQuestions) ? 20 : $examQuestions;
        $sort = is_null($sort) ? 0 : $sort;

        $obj = new Entity\ExamModule();
        $obj->setName($name)
            ->setPreassessmentQuestions($preassessmentQuestions)
            ->setPracticeQuestions($practiceQuestions)
            ->setExamQuestions($examQuestions)
            ->setSort($sort);

        return $obj;
    }

    /**
     * @param string $name
     * @param string $code
     * @param string $description
     * @param int $examTime
     * @param int $accessTime
     * @return Entity\Exam
     */
    function generateExam($name = null, $code = null, $description = null, $examTime = 1200, $accessTime = 180)
    {
        $name = is_null($name) ? 'My first exam' . uniqid() : $name;
        $code = is_null($code) ? 'MFM' . uniqid() : $code;
        $description = is_null($description) ? 'This is a test exam' . uniqid() : $description;
        $examTime = is_null($examTime) ? 1200 : $examTime;
        $accessTime = is_null($accessTime) ? 180 : $accessTime;

        $obj = new Entity\Exam();
        $obj->setName($name)
            ->setCode($code)
            ->setDescription($description)
            ->setExamTime($examTime)
            ->setAccessTime($accessTime);

        return $obj;
    }

    /**
     * @param int $type
     * @param int $status
     * @return Entity\Enrollment
     */
    function generateEnrollment($type = Entity\Enrollment::TYPE_FULL, $status = Entity\Enrollment::STATUS_ACTIVE)
    {
        $type = is_null($type) ? Entity\Enrollment::TYPE_FULL : $type;
        $status = is_null($status) ? Entity\Enrollment::STATUS_ACTIVE : $status;

        $obj = new Entity\Enrollment();
        $obj->setType($type)
            ->setStatus($status);

        return $obj;
    }

    /**
     * @param string $type
     * @param int $questionCount
     * @return Entity\Progress
     */
    function generateProgress($type = Entity\Progress::TYPE_STUDY, $questionCount = 10)
    {
        $type = is_null($type) ? Entity\Progress::TYPE_STUDY : $type;
        $questionCount = is_null($questionCount) ? 10 : $questionCount;

        $obj = new Entity\Progress();
        $obj->setType($type)
            ->setQuestionCount($questionCount);

        return $obj;
    }

    /**
     * @param string $type
     * @param int $questionCount
     * @return Entity\AssessmentAttempt
     */
    function generateAssessmentAttempt($type = Entity\AssessmentAttempt::TYPE_EXAM, $questionCount = 10)
    {
        $type = is_null($type) ? Entity\AssessmentAttempt::TYPE_EXAM : $type;
        $questionCount = is_null($questionCount) ? 10 : $questionCount;

        $obj = new Entity\AssessmentAttempt();
        $obj->setType($type)
            ->setQuestionCount($questionCount);

        return $obj;
    }

    /**
     * @param string $type
     * @param int $questionCount
     * @return Entity\ModuleAttempt
     */
    function generateModuleAttempt($type = Entity\ModuleAttempt::TYPE_STUDY, $questionCount = 10)
    {
        $type = is_null($type) ? Entity\ModuleAttempt::TYPE_STUDY : $type;
        $questionCount = is_null($questionCount) ? 10 : $questionCount;

        $obj = new Entity\ModuleAttempt();
        $obj->setType($type)
            ->setQuestionCount($questionCount);

        return $obj;
    }

    /**
     * @param string $type
     * @param string $text
     * @return Entity\Question
     */
    public function generateQuestion($type = Entity\Question::TYPE_VOCAB, $text = null)
    {
        $type = is_null($type) ? Entity\Question::TYPE_VOCAB : $type;
        $text = is_null($text) ? 'What is greatness?' . uniqid() : $text;

        $obj = new Entity\Question();
        $obj->setQuestionText($text)
            ->setType($type);

        return $obj;
    }

    /**
     * @param string $type
     * @return Entity\Question
     */
    public function generateQuestionWithAnswers($type = Entity\Question::TYPE_VOCAB)
    {
        $question = $this->generateQuestion($type);
        $answerCount = $type == Entity\Question::TYPE_VOCAB ? 1 : 4;

        for ($x = 0; $x < $answerCount; $x++) {
            $answer = $this->generateAnswer(!$x);
            $answer->setQuestion($question);
            $question->addAnswer($answer);
        }

        return $question;
    }

    /**
     * @param bool $correct
     * @param string $text
     * @return Entity\Answer
     */
    public function generateAnswer($correct = true, $text = null)
    {
        $correct = is_null($correct) ? true : $correct;
        $text = is_null($text) ? 'panda' . uniqid() : $text;

        $obj = new Entity\Answer();
        $obj->setAnswerText($text)
            ->setCorrect($correct);

        return $obj;
    }

    /**
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param string $token
     * @param string $password
     * @param int $status
     * @param int $role
     * @return Entity\User
     */
    public function generateUser($email = null, $firstName = null, $lastName= null, $token = null, $password = null,
                                 $status = null, $role = Entity\User::ROLE_MEMBER)
    {
        $email = is_null($email) ? 'panda+' . uniqid() . '@powa.com' : $email;
        $firstName = is_null($firstName) ? 'panda' . uniqid() : $firstName;
        $lastName = is_null($lastName) ? 'panda' . uniqid() : $lastName;
        $token = is_null($token) ? uniqid('token') : $token;
        $password = is_null($password) ? 'panda' . uniqid() : $password;
        $status = is_null($status) ? Entity\User::STATUS_ACTIVE : $status;
        $role = is_null($role) ? Entity\User::ROLE_MEMBER : $role;

        $obj = new Entity\User();
        $obj->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setToken($token)
            ->setPassword($password)
            ->setStatus($status)
            ->setRole($role)
            ->setCreated(new \DateTime());

        return $obj;
    }

    /**
     * @param string $name
     * @param string $url
     * @return Entity\Organization
     */
    public function generateOrganization($name = null, $url = null)
    {
        $name = is_null($name) ? 'Panda Inc.' . uniqid() : $name;
        $url = is_null($url) ? 'http://panda.com/' . uniqid() : $url;

        $obj = new Entity\Organization();
        $obj->setName($name)
            ->setUrl($url);

        return $obj;
    }

    /**
     * @param string $type
     * @return Entity\QuestionBank
     */
    public function generateQuestionBank($type = Entity\QuestionBank::TYPE_STUDY)
    {
        $type = is_null($type) ? Entity\QuestionBank::TYPE_STUDY : $type;

        $obj = new Entity\QuestionBank();
        $obj->setType($type);

        return $obj;
    }

    /**
     * @param string $type
     * @return Entity\ModuleQuestion
     */
    public function generateModuleQuestion($type = Entity\ModuleQuestion::TYPE_STUDY)
    {
        $type = is_null($type) ? Entity\ModuleQuestion::TYPE_STUDY : $type;

        $obj = new Entity\ModuleQuestion();
        $obj->setType($type);

        return $obj;
    }
}