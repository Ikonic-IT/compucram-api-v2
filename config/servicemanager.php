<?php

return array(
    'abstract_factories' => array(

    ),
    'aliases' => array(

    ),
    'factories' => array(
        'entityManager' => 'Hondros\Api\ServiceProvider\Adapter\DoctrineFactory',
        'logger' => 'Hondros\Api\ServiceProvider\Adapter\MonologFactory',
        'redis' => 'Hondros\Api\ServiceProvider\Adapter\RedisFactory',
        'messageQueue' => 'Hondros\Api\ServiceProvider\Adapter\MessageQueueFactory',
        'elasticsearch' => 'Hondros\Api\ServiceProvider\Adapter\ElasticsearchFactory',
        's3Client' => 'Hondros\Api\ServiceProvider\Adapter\Aws\S3ClientFactory',

        'httpRequest' => function() {
            return \Symfony\Component\HttpFoundation\Request::createFromGlobals();
        },

        'rbac' => 'Hondros\Api\ServiceProvider\Auth\RbacFactory',
        'mailChimpClient' => 'Hondros\Api\ServiceProvider\Client\MailChimpFactory',

        'userListener' => 'Hondros\Api\ServiceProvider\EntityListener\UserFactory',
        'moduleQuestionListener' => 'Hondros\Api\ServiceProvider\EntityListener\ModuleQuestionFactory',
        'questionListener' => 'Hondros\Api\ServiceProvider\EntityListener\QuestionFactory',
        'answerListener' => 'Hondros\Api\ServiceProvider\EntityListener\AnswerFactory',

        'userRepository' => 'Hondros\Api\ServiceProvider\Repository\UserFactory',
        'userLogRepository' => 'Hondros\Api\ServiceProvider\Repository\UserLogFactory',
        'examRepository' => 'Hondros\Api\ServiceProvider\Repository\ExamFactory',
        'examModuleRepository' => 'Hondros\Api\ServiceProvider\Repository\ExamModuleFactory',
        'enrollmentRepository' => 'Hondros\Api\ServiceProvider\Repository\EnrollmentFactory',
        'industryRepository' => 'Hondros\Api\ServiceProvider\Repository\IndustryFactory',
        'stateRepository' => 'Hondros\Api\ServiceProvider\Repository\StateFactory',
        'assessmentAttemptRepository' => 'Hondros\Api\ServiceProvider\Repository\AssessmentAttemptFactory',
        'assessmentAttemptQuestionRepository' => 'Hondros\Api\ServiceProvider\Repository\AssessmentAttemptQuestionFactory',
        'moduleRepository' => 'Hondros\Api\ServiceProvider\Repository\ModuleFactory',
        'moduleQuestionRepository' => 'Hondros\Api\ServiceProvider\Repository\ModuleQuestionFactory',
        'moduleAttemptRepository' => 'Hondros\Api\ServiceProvider\Repository\ModuleAttemptFactory',
        'moduleAttemptQuestionRepository' => 'Hondros\Api\ServiceProvider\Repository\ModuleAttemptQuestionFactory',
        'questionRepository' => 'Hondros\Api\ServiceProvider\Repository\QuestionFactory',
        'questionBankRepository' => 'Hondros\Api\ServiceProvider\Repository\QuestionBankFactory',
        'questionAuditRepository' => 'Hondros\Api\ServiceProvider\Repository\QuestionAuditFactory',
        'answerRepository' => 'Hondros\Api\ServiceProvider\Repository\AnswerFactory',
        'answerAuditRepository' => 'Hondros\Api\ServiceProvider\Repository\AnswerAuditFactory',
        'progressRepository' => 'Hondros\Api\ServiceProvider\Repository\ProgressFactory',
        'progressQuestionRepository' => 'Hondros\Api\ServiceProvider\Repository\ProgressQuestionFactory',
        'organizationRepository' => 'Hondros\Api\ServiceProvider\Repository\OrganizationFactory',
        
        'userService' => 'Hondros\Api\ServiceProvider\Service\UserFactory',
        'userLogService' => 'Hondros\Api\ServiceProvider\Service\UserLogFactory',
        'examService' => 'Hondros\Api\ServiceProvider\Service\ExamFactory',
        'examModuleService' => 'Hondros\Api\ServiceProvider\Service\ExamModuleFactory',
        'exportContentService' => 'Hondros\Api\ServiceProvider\Service\Export\ContentFactory',
        'reportService' => 'Hondros\Api\ServiceProvider\Service\ReportFactory',
        'enrollmentService' => 'Hondros\Api\ServiceProvider\Service\EnrollmentFactory',
        'categoryAttemptService' => 'Hondros\Api\ServiceProvider\Service\CategoryAttemptFactory',
        'assessmentAttemptService' => 'Hondros\Api\ServiceProvider\Service\AssessmentAttemptFactory',
        'assessmentAttemptQuestionService' => 'Hondros\Api\ServiceProvider\Service\AssessmentAttemptQuestionFactory',
        'moduleService' => 'Hondros\Api\ServiceProvider\Service\ModuleFactory',
        'moduleQuestionService' => 'Hondros\Api\ServiceProvider\Service\ModuleQuestionFactory',
        'moduleAttemptService' => 'Hondros\Api\ServiceProvider\Service\ModuleAttemptFactory',
        'moduleAttemptQuestionService' => 'Hondros\Api\ServiceProvider\Service\ModuleAttemptQuestionFactory',
        'answerService' => 'Hondros\Api\ServiceProvider\Service\AnswerFactory',
        'questionService' => 'Hondros\Api\ServiceProvider\Service\QuestionFactory',
        'progressService' => 'Hondros\Api\ServiceProvider\Service\ProgressFactory',
        'progressQuestionService' => 'Hondros\Api\ServiceProvider\Service\ProgressQuestionFactory',
        'organizationService' => 'Hondros\Api\ServiceProvider\Service\OrganizationFactory',
        'stateService' => 'Hondros\Api\ServiceProvider\Service\StateFactory',
        'industryService' => 'Hondros\Api\ServiceProvider\Service\IndustryFactory',

        'contentImporter' => 'Hondros\Api\ServiceProvider\Util\ContentImporterFactory',
        'userImporter' => 'Hondros\Api\ServiceProvider\Util\UserImporterFactory',
        'excelValidator' => 'Hondros\Api\ServiceProvider\Util\Excel\ValidatorFactory',

        'audioMessageQueue' => 'Hondros\Api\ServiceProvider\MessageQueue\AudioFactory',
        'progressMessageQueue' => 'Hondros\Api\ServiceProvider\MessageQueue\ProgressFactory',
        'questionMessageQueue' => 'Hondros\Api\ServiceProvider\MessageQueue\QuestionFactory'
    ),
    'invokables' => array(
        'questionHydratorStrategy' => 'Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\Question',
        'answerHydratorStrategy' => 'Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\Answer',
        'userHydratorStrategy' => 'Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\User',
        'examToExcel' => 'Hondros\Api\Util\Excel\ExamToExcel',
    ),
    'services' => array(

    ),
    'shared' => array(
        
    ),
    'initializers' => array(
        
    )            
        
);