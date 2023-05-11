<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 3/29/17
 * Time: 6:24 PM
 */

use Hondros\Api\Model\Entity;
use Hondros\Api\Util\Helper\StringUtil;
use Hondros\Api\Util\Helper\EntityGeneratorUtil;
use Zend\Config\Config;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;

class ContentImporterTest extends \Hondros\Test\FunctionalAbstract
{
    /**
     * Use String util helper
     */
    use StringUtil { convertStringToUtf8 as protected; }

    /**
     * to create some quick modules
     */
    use EntityGeneratorUtil {}

    /**
     * @var string
     */
    protected $validModuleFilePath = 'tests/unit/assets/import/Module_valid1.xlsx';

    /**
     * @var string
     */
    protected $validModuleUpdateFilePath = 'tests/unit/assets/import/ModuleUpdate_valid1.xlsx';

    /**
     * @var string
     */
    protected $validExamFilePath = 'tests/unit/assets/import/Exam_valid2.xlsx';

    /**
     * @var \Hondros\Api\Util\ContentImporter
     */
    protected $contentImporter;

    protected function setUp(): void
    {
        parent::setUp();

        // turn off the entity listeners
        $this->disableEntityListeners();

        $this->contentImporter = $this->getServiceManager()->get('contentImporter');
    }

    protected function tearDown(): void
    {
        $this->contentImporter = null;

        // turn back on the entity listeners
        $this->enableEntityListeners();

        parent::tearDown();
    }

    public function testImportModuleInvalidFile()
    {
        $message = null;

        try {
            $this->contentImporter->importModule('asdf');
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        $this->assertEquals("Unable to load excel module file asdf due to File \"asdf\" does not exist.",
            $message);
    }

    public function testImportModuleDuplicateModule()
    {
        $message = null;
        $code = substr(uniqid(), 3);
        $filePath = $this->createTempModuleFromValid($code);
        $module = $this->generateModule();
        $industry = $this->generateIndustry();

        $module->setCode($code)->setIndustry($industry);
        $this->getEntityManager()->persist($industry);
        $this->getEntityManager()->persist($module);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        try {
            $this->contentImporter->importModule($filePath);
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        $this->assertEquals("Module for {$code} already exists.", $message);
    }

    /**
     * make sure we can import a module
     */
    public function testImportModuleValid()
    {
        // unique id is 13 chars, only keep 10
        $code = substr(uniqid(), 3);

        $industry = $this->generateIndustry();
        $this->getEntityManager()->persist($industry);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $filePath = $this->createTempModuleFromValid($code, $industry->getName());

        /** @var \Hondros\Api\Util\Excel\Response $response */
        $response = $this->getServiceManager()->get('excelValidator')->validateModuleFile($filePath);
        $this->assertTrue($response->isValid(), implode(PHP_EOL,$response->getErrors()));

        try {
            $response = $this->contentImporter->importModule($filePath);
        } catch (\Exception $e) {
            $this->fail("Should not be in here. " . $e->getMessage());
        }

        // clean up
        $this->getEntityManager()->clear();

        $this->assertTrue($response);

        /** @var Entity\Module $module */
        $module = $this->getServiceManager()->get('moduleRepository')->findOneByCode($code);
        $this->assertNotEmpty($module);
        $this->assertEquals(substr('Evaluates customers financial information', 0, 100), $module->getName());
        $this->assertEquals($industry->getName(), $module->getIndustry()->getName());
        $this->assertEmpty($module->getState());

        /** @var Entity\Question[] $studyQuestions */
        $studyQuestions = $this->getServiceManager()->get('questionRepository')
            ->findForModule($module->getId(), Entity\ModuleQuestion::TYPE_STUDY);
        $this->assertCount(8, $studyQuestions);

        $terms = [
            'Capital Loss' => 'result of selling an investment at less than the purchase price or adjusted basis. Any expenses from the sale are deducted from the proceeds and added to the loss.',
            'Cash Transaction' => 'the opposite of a futures contract, which involves the exchange of an asset at a later date and at a set price.',
            'Common Stock' => 'shares entitling their holder to dividends that vary in amount and may even be missed, depending on the fortunes of the company.',
            'Constructive Receipt' => 'used to determine when a cash-basis taxpayer has received gross income. A taxpayer is subject to tax in the current year if he or she has unfettered control in determining when items of income will or should be paid.',
            'Convertible Bond' => 'type of bondthat the holder can convert into a specified number of shares of common stock in the issuing company or cash of equal value.',
            'Nominal Yield' => 'the interest rate (to par value) that the bond issuer promises to pay bond purchasers.',
            'Discretionary Account' => 'An account that allows a broker to buy and sell securities without the client\'s consent.',
            'Earned Income' => 'income derived from active participation in a trade or business, including wages, salary, tips, commissions and bonuses.'
        ];

        foreach ($studyQuestions as $question) {
            $this->assertArrayHasKey($question->getQuestionText(), $terms);
            $excelToImportableTest = $this->convertStringToUtf8($terms[$question->getQuestionText()]);
            $this->assertEquals($question->getAnswers()[0]->getAnswerText(), $excelToImportableTest);
        }

        /** @var Entity\Question[] $practiceQuestions */
        $practiceQuestions = $this->getServiceManager()->get('questionRepository')
            ->findForModule($module->getId(), Entity\ModuleQuestion::TYPE_PRACTICE);
        $this->assertCount(9, $practiceQuestions);

        $excelQuestions = [
            'Sales presentation materials must be filed with the FINRA within ______ days of first use for all but new members.' => [
                'correct' => '70',
                'answerCount' => 5,
                'feedback' => 'Ten (10) days is the requirement for sales presentations. In the case of a new broker-dealer, the requirement is 10 days prior to use of the presentation.'
            ],
            'Sales presentation materials must be filed with the FINRA within ___ days of first use for all but new members.' => [
                'correct' => '10',
                'answerCount' => 2,
                'feedback' => 'Ten (10) days is the requirement for sales presentations. In the case of a new broker-dealer, the requirement is 10 days prior to use of the presentation.'
            ],
            'Advertising must be filed with FINRA within ___ days of first use for any member firm.' => [
                'correct' => '10',
                'answerCount' => 4,
                'feedback' => 'This 10-day rule applies to established firms. For new firms, the rule states that the advertising must be filed ten days prior to use. Part of the filing includes the actual or anticipated date of first use.'
            ],
            'Bonds usually have a face, or par value of:' => [
                'correct' => '$1000',
                'answerCount' => 5,
                'feedback' => 'the usual face amount is $1000. The face amount is also called the par value of the bond, once issued, may sell on the resale(secondary) market act, above, or below the par value.'
            ],
            'A client has purchased $10,000 of a Class B fund, with a CDSC of 5%. After several months have passed, the client discovers an urgent need for all of the cash in the account and surrenders the account. Assuming the account value at time of surrender is $10,200, what amount of money will the client receive?' => [
                'correct' => '$9,690',
                'answerCount' => 4,
                'feedback' => ''
            ],
            'If a mutual fund has a right of accumulation, what is the duration of the LOI?' => [
                'correct' => '13 months',
                'answerCount' => 4,
                'feedback' => 'The rights of accumulation have to do with obtaining a lower commission rate for a client if he purchases a certain amount of additional funds over a 13-month period. This is outlined by a LOI (letter of intent); if the additional funds are not received during the 13-month period, the client will be charged the commission for the breakpoint he actually reached.'
            ],
            'Publications distributed by a broker-dealer with no control over who reads or receives the material are known as' => [
                'correct' => 'advertising.',
                'answerCount' => 2,
                'feedback' => 'While literature can be directed, advertising is so general that there can be no specificity as to who sees it.'
            ],
            'When may a broker-dealer run a "tombstone ad?"' => [
                'correct' => 'during the cooling-off period',
                'answerCount' => 4,
                'feedback' => 'A tombstone ad is intended to aid the broker-dealer in getting additional indications of interest for a new issue (IPO) that is about to be cleared for sale. A is the correct answer, since the issue is not actually on the market at this time.'
            ],
            'You have a balance sheet for a client and are reviewing the client\'s net worth. How is the net worth determined?' => [
                'correct' => 'Assets - Liabilities',
                'answerCount' => 4,
                'feedback' => 'Net worth is determined by subtracting total liabilities from total assets. The remaining amount is "total net worth."'
            ],
        ];

        // cleanup array
        foreach ($excelQuestions as $key => &$value) {
            $key = $this->convertStringToUtf8($key);
            $value['correct'] = $this->convertStringToUtf8($value['correct']);
            $value['feedback'] = $this->convertStringToUtf8($value['feedback']);
        }

        foreach ($practiceQuestions as $question) {
            $this->assertArrayHasKey($question->getQuestionText(), $excelQuestions);
            $this->assertEquals($excelQuestions[$question->getQuestionText()]['feedback'], $question->getFeedback());
            $this->assertCount($excelQuestions[$question->getQuestionText()]['answerCount'], $question->getAnswers());

            $foundCorrect = false;
            /** @var Entity\Answer $answer */
            foreach ($question->getAnswers() as $answer) {
                if ($answer->getAnswerText() === $excelQuestions[$question->getQuestionText()]['correct']) {
                    $this->assertTrue($answer->getCorrect());
                    $foundCorrect = true;
                } else {
                    $this->assertFalse($answer->getCorrect());
                }
            }
            $this->assertTrue($foundCorrect);
        }

        /** @var Entity\Question[] $examQuestions */
        $examQuestions = $this->getServiceManager()->get('questionRepository')
            ->findForModule($module->getId(), Entity\ModuleQuestion::TYPE_EXAM);
        $this->assertCount(10, $examQuestions);

        $excelQuestions = [
            'The risk an investor takes when investing in a debt obligation is called' => [
                'correct' => 'all of the choices',
                'answerCount' => 4,
                'feedback' => 'Credit risk entails the company failing and not being able to repay its debts. Inflation creates purchase power risk. Interest rate risk is concerned with changes in rates that adversely affect the investment.'
            ],
            'The risk that a law will adversely affect &frac12; an investment is called' => [
                'correct' => 'legislative risk.',
                'answerCount' => 4,
                'feedback' => 'Legislative risk occurs when there is a change in government policy that adversely affects an investment. For example, a new clean air requirement may create new expenses in certain industries that will affect growth. Social risk occurs when there is a change in social acceptance. For example, the change in public attitudes on the use of tobacco has drastically affected cigarette makers. Political risk usually involves the actions of a foreign country, as when an industry is nationalized.'
            ],
            'Whenever there is an offer to sell a new security issue, a ____________ must be provided to the client.' => [
                'correct' => 'prospectus',
                'answerCount' => 4,
                'feedback' => 'The key word is "new;" this is an IPO, which always requires a prospectus.'
            ],
            'A client of yours, age 58, once an investment with the highest possible monthly income. You have discussed risk versus reward in the possibility of losing principle. The client is in agreement that loss of principal for this investment would be acceptable. Your recommendation would be:' => [
                'correct' => 'Low-grade corporate bond fund',
                'answerCount' => 4,
                'feedback' => 'The low-grade corporate bond fund(also called a high-yield bond fund) would be the best choice from the choices listed in the question. Remember lower bond ratings mean higher yields.'
            ],
            'A DPP is considered to be' => [
                'correct' => 'highly illiquid.',
                'answerCount' => 4,
                'feedback' => 'A direct participation program is not only highly illiquid; it is not for the novice investor.'
            ],
            'You have a 42-year-old client, married with no children. Your review indicates both the husband and wife file a joint tax return and are in the 37% tax bracket. They will take limited risk but want their money in a high quality investment. Which of the following investment choices may be appropriate:' => [
                'correct' => 'Municipal bond fund',
                'answerCount' => 4,
                'feedback' => 'The factors relating to the municipal bond fund is the investment of choice for this example due to the need for a high quality investment in the high 37% tax bracket'
            ],
            'What is required on an illustration for variable universal life?' => [
                'correct' => 'The maximum investment return that can be shown is a gross rate of 12% provided a gross rate of 0% is shown.',
                'answerCount' => 4,
                'feedback' => 'Even though the maximum rate that can be shown is 12%, it must be reasonable considering the market conditions and the available investment options.'
            ],
            'Under what conditions may a registered representative do business under a fictitious name?<ol class="upper-roman"><li> never</li><li> when permitted by SIPC</li><li>when registered with the SEC</li><li> when registered with the appropriate SRO</li></ol>' => [
                'correct' => 'III, IV',
                'answerCount' => 4,
                'feedback' => 'An example of a fictitious name would be a DBA (doing business as) name. FINRA would be the appropriate SRO.'
            ],
            'An investment profile for a client consists of<ol class="upper-roman"><li> financial status.</li><li> non-financial status.</li><li> risk tolerance.</li><li> investment objectives.</li></ol>' => [
                'correct' => 'I, II, III, IV',
                'answerCount' => 4,
                'feedback' => 'An investment profile for a client includes all of the areas listed in the question. The net result is that you know enough about the client to make suitable recommendations.'
            ],
            'Most open-end funds offer breakpoints for increasing the amount invested over a designated period of time. Who or what may usually be used to combine investment amounts to reach a breakpoint?<ol class="upper-roman"><li>married couples</li><li> parents and their adult children</li><li>parents and their minor children</li><li> investment clubs</li></ol>' => [
                'correct' => 'false',
                'answerCount' => 2,
                'feedback' => 'Adult children have passed the age of majority and are considered to be on their own. Investment clubs are excluded, since they are composed of a group of adults who buy funds individually. That leaves the correct answers: payment &divide;market price = current yield.'
            ],
        ];

        // cleanup array
        foreach ($excelQuestions as $key => &$value) {
            $key = $this->convertStringToUtf8($key);
            $value['correct'] = $this->convertStringToUtf8($value['correct']);
            $value['feedback'] = $this->convertStringToUtf8($value['feedback']);
        }

        foreach ($examQuestions as $question) {
            $this->assertArrayHasKey($question->getQuestionText(), $excelQuestions);
            $this->assertEquals($excelQuestions[$question->getQuestionText()]['feedback'], $question->getFeedback());
            $this->assertCount($excelQuestions[$question->getQuestionText()]['answerCount'], $question->getAnswers());

            $foundCorrect = false;
            /** @var Entity\Answer $answer */
            foreach ($question->getAnswers() as $answer) {
                if ($answer->getAnswerText() === $excelQuestions[$question->getQuestionText()]['correct']) {
                    $this->assertTrue($answer->getCorrect(), $answer->getAnswerText());
                    $foundCorrect = true;
                } else {
                    $this->assertFalse($answer->getCorrect(), $answer->getAnswerText());
                }
            }
            $this->assertTrue($foundCorrect);
        }
    }

    /**
     * import module twice and make sure there are no duplicate questions
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testImportModuleValidNoDupQuestions()
    {
        $questionQuery = $this->getEntityManager()
            ->createQuery("SELECT COUNT(q) FROM Hondros\\Api\\Model\\Entity\\Question q");

        $moduleQuestionQuery = $this->getEntityManager()
            ->createQuery("SELECT COUNT(mq) FROM Hondros\\Api\\Model\\Entity\\ModuleQuestion mq");

        $moduleQuestionCountBefore = $moduleQuestionQuery->getSingleScalarResult();

        // unique id is 13 chars, only keep 10
        $code = substr(uniqid(), 3);

        $industry = $this->generateIndustry();
        $this->getEntityManager()->persist($industry);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $filePath = $this->createTempModuleFromValid($code, $industry->getName());

        /** @var \Hondros\Api\Util\Excel\Response $response */
        $response = $this->getServiceManager()->get('excelValidator')->validateModuleFile($filePath);
        $this->assertTrue($response->isValid(), implode(PHP_EOL,$response->getErrors()));

        try {
            $response = $this->contentImporter->importModule($filePath);
        } catch (\Exception $e) {
            $this->fail("Should not be in here. " . $e->getMessage());
        }

        // clean up
        $this->getEntityManager()->clear();
        $this->assertTrue($response);

        // track questions
        $questionCount = $questionQuery->getSingleScalarResult();
        $moduleQuestionCount = $moduleQuestionQuery->getSingleScalarResult();

        // unique id is 13 chars, only keep 10
        $code = substr(uniqid(), 3);

        $industry = $this->generateIndustry();
        $this->getEntityManager()->persist($industry);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $filePath = $this->createTempModuleFromValid($code, $industry->getName());

        /** @var \Hondros\Api\Util\Excel\Response $response */
        $response = $this->getServiceManager()->get('excelValidator')->validateModuleFile($filePath);
        $this->assertTrue($response->isValid(), implode(PHP_EOL,$response->getErrors()));

        try {
            $response = $this->contentImporter->importModule($filePath);
        } catch (\Exception $e) {
            $this->fail("Should not be in here. " . $e->getMessage());
        }

        // clean up
        $this->getEntityManager()->clear();
        $this->assertTrue($response);

        // track questions
        $questionCount2 = $questionQuery->getSingleScalarResult();
        $moduleQuestionCount2 = $moduleQuestionQuery->getSingleScalarResult();

        $this->assertEquals($questionCount, $questionCount2, "Shouldn't have added more questions.");

        $expectedModuleQuestions = $moduleQuestionCountBefore + ($moduleQuestionCount - $moduleQuestionCountBefore) * 2;
        $this->assertEquals($expectedModuleQuestions, $moduleQuestionCount2);
    }

    public function testImportModuleUpdateInvalidFile()
    {
        $message = null;

        try {
            $this->contentImporter->updateModule('asdf');
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        $this->assertEquals("Unable to load excel update module file asdf due to File \"asdf\" does not exist.",
            $message);
    }

    public function testImportModuleUpdateValid()
    {
        // unique id is 13 chars, only keep 10
        $code = substr(uniqid(), 3);

        $industry = $this->generateIndustry();
        $this->getEntityManager()->persist($industry);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $filePath = $this->createTempModuleFromValid($code, $industry->getName());

        /** @var \Hondros\Api\Util\Excel\Response $response */
        $response = $this->getServiceManager()->get('excelValidator')->validateModuleFile($filePath);
        $this->assertTrue($response->isValid(), implode(PHP_EOL,$response->getErrors()));

        try {
            $response = $this->contentImporter->importModule($filePath);
        } catch (\Exception $e) {
            $this->fail("Should not be in here. " . $e->getMessage());
        }

        // clean up
        $this->getEntityManager()->clear();
        $this->assertTrue($response);

        // now update
        $filePath = $this->createTempModuleUpdateFromValid($code, $industry->getName());
        $response = $this->getServiceManager()->get('excelValidator')->validateModuleFile($filePath);
        $this->assertTrue($response->isValid(), implode(PHP_EOL,$response->getErrors()));

        try {
            $response = $this->contentImporter->updateModule($filePath);
        } catch (\Exception $e) {
            $this->fail("Should not be in here. " . $e->getMessage() . $e->getTraceAsString());
        }

        // clean up
        $this->getEntityManager()->clear();
        $this->assertTrue($response);

        /** @var Entity\Module $module */
        $module = $this->getServiceManager()->get('moduleRepository')->findOneByCode($code);
        $this->assertNotEmpty($module);

        /** @var Entity\Question[] $studyQuestions */
        $studyQuestions = $this->getServiceManager()->get('questionRepository')
            ->findForModule($module->getId(), Entity\ModuleQuestion::TYPE_STUDY);

        $this->assertCount(9, $studyQuestions);
        $excelQuestions = [
            'Capital Loss' => [
                'answer' => 'result of selling an investment at less than the purchase price or adjusted basis. Any expenses from the sale are deducted from the proceeds and added to the loss.',
                'active' => 1
            ],
            'Cash Transaction' => [
                'answer' => 'the opposite of a futures contract, which involves the exchange of an asset at a later date and at a set price.',
                'active' => 0
            ],
            'Common Stock' => [
                'answer' => 'shares entitling their holder to dividends that vary in amount and may even be missed, depending on the fortunes of the company.',
                'active' => 0
            ],
            'Constructive Receipts' => [
                'answer' => 'use to determine when a cash-basis taxpayer has received gross income. A taxpayer is subject to tax in the current year if he or she has unfettered control in determining when items of income will or should be paid.',
                'active' => 1
            ],
            'Convertible Bond' => [
                'answer' => 'type of bondthat the holder can convert into a specified number of shares of common stock in the issuing company or cash of equal value.',
                'active' => 0
            ],
            'Nominal Yielding' => [
                'answer' => 'the interest rate (to par value) that the bond issuer promises to pay bond purchasers.',
                'active' => 1
            ],
            'Discretionary Account' => [
                'answer' => 'The account that allows a broker to buy and sell securities without the client\'s consent.',
                'active' => 1
            ],
            'Earned Income' => [
                'answer' => 'income derived from active participation in a trade or business, including wages, salary, tips, commissions and bonuses.',
                'active' => 1
            ],
            'A new one' => [
                'answer' => 'panda power',
                'active' => 1
            ]
        ];

        // cleanup array
        foreach ($excelQuestions as $key => &$value) {
            $key = $this->convertStringToUtf8($key);
            $value['answer'] = $this->convertStringToUtf8($value['answer']);
        }

        foreach ($studyQuestions as $question) {
            $this->assertArrayHasKey($question->getQuestionText(), $excelQuestions);
            $this->assertEquals($excelQuestions[$question->getQuestionText()]['active'], $question->getActive());
            $this->assertEquals($excelQuestions[$question->getQuestionText()]['answer'],
                $question->getAnswers()[0]->getAnswerText());
        }

        /** @var Entity\Question[] $practiceQuestions */
        $practiceQuestions = $this->getServiceManager()->get('questionRepository')
            ->findForModule($module->getId(), Entity\ModuleQuestion::TYPE_PRACTICE);
        $this->assertCount(10, $practiceQuestions);

        $excelQuestions = [
            'Sales presentation materials must be filed with the FINRA within ______ days of first use for all but new members.' => [
                'correct' => '70',
                'answerCount' => 5,
                'feedback' => 'Ten (10) days is the requirement for sales presentations. In the case of a new broker-dealer, the requirement is 10 days prior to use of the presentation.',
                'active' => 0
            ],
            'Sales presentation materials must be filed with the FINRA within ___ days of first use for all but new members.' => [
                'correct' => '10',
                'answerCount' => 2,
                'feedback' => 'Ten (10) days is the requirement for sales presentations. In the case of a new broker-dealer, the requirement is 10 days prior to use of the presentation.',
                'active' => 0
            ],
            'Advertising must be filed with FINRA within ___ days of first use for any member firm.' => [
                'correct' => '10',
                'answerCount' => 4,
                'feedback' => 'This 10-day rule applies to established firms. For new firms, the rule states that the advertising must be filed ten days prior to use. Part of the filing includes the actual or anticipated date of first use.',
                'active' => 0
            ],
            'Bonds usually have a face, or par value of:' => [
                'correct' => '$1000',
                'answerCount' => 5,
                'feedback' => 'the usual face amount is $1000. The face amount is also called the par value of the bond, once issued, may sell on the resale(secondary) market act, above, or below the par value.',
                'active' => 0
            ],
            'A client has purchased $10,000 of a Class B fund, with a CDSC of 5%. After several months have passed, the client discovers an urgent need for all of the cash in the account and surrenders the account. Assuming the account value at time of surrender is $10,200, what amount of money will the client receive?' => [
                'correct' => '$9,690',
                'answerCount' => 4,
                'feedback' => '',
                'active' => 0
            ],
            'If a mutual fund has a right of accumulation, what is the duration of the LOI?' => [
                'correct' => '13 months',
                'answerCount' => 4,
                'feedback' => 'The rights of accumulation have to do with obtaining a lower commission rate for a client if he purchases a certain amount of additional funds over a 13-month period. This is outlined by a LOI (letter of intent); if the additional funds are not received during the 13-month period, the client will be charged the commission for the breakpoint he actually reached.',
                'active' => 0
            ],
            'one new one' => [
                'correct' => 'panda',
                'answerCount' => 4,
                'feedback' => 'this is good stuff',
                'active' => 1
            ],
            'Publications distributed by a broker-dealer with no control over who reads or receives the material are known as' => [
                'correct' => 'advertising.',
                'answerCount' => 2,
                'feedback' => 'While literature can be directed, advertising is so general that there can be no specificity as to who sees it.',
                'active' => 1
            ],
            'When may a broker-dealer run a "tombstone ad?" really?' => [
                'correct' => 'during the cooling-off period',
                'answerCount' => 4,
                'feedback' => 'A tombstone ad is intended to aid the broker-dealer in getting additional indications of interest for a new issue (IPO) that is about to be cleared for sale. A is the correct answer, since the issue is not actually on the market at this time.',
                'active' => 1
            ],
            'You have a balance sheet for a client and are reviewing the client\'s net worth. How is the net worth determined?' => [
                'correct' => 'Assets - Liabilities',
                'answerCount' => 4,
                'feedback' => 'The remaining amount is "total net worth."',
                'active' => 1
            ],
        ];

        // cleanup array
        foreach ($excelQuestions as $key => &$value) {
            $key = $this->convertStringToUtf8($key);
            $value['correct'] = $this->convertStringToUtf8($value['correct']);
            $value['feedback'] = $this->convertStringToUtf8($value['feedback']);
        }

        foreach ($practiceQuestions as $question) {
            $this->assertArrayHasKey($question->getQuestionText(), $excelQuestions);
            $this->assertEquals($excelQuestions[$question->getQuestionText()]['feedback'], $question->getFeedback());
            $this->assertEquals($excelQuestions[$question->getQuestionText()]['active'], $question->getActive());
            $this->assertCount($excelQuestions[$question->getQuestionText()]['answerCount'], $question->getAnswers());

            $foundCorrect = false;
            /** @var Entity\Answer $answer */
            foreach ($question->getAnswers() as $answer) {
                if ($answer->getAnswerText() === $excelQuestions[$question->getQuestionText()]['correct']) {
                    $this->assertTrue($answer->getCorrect());
                    $foundCorrect = true;
                } else {
                    $this->assertFalse($answer->getCorrect());
                }
            }
            $this->assertTrue($foundCorrect);
        }

        /** @var Entity\Question[] $examQuestions */
        $examQuestions = $this->getServiceManager()->get('questionRepository')
            ->findForModule($module->getId(), Entity\ModuleQuestion::TYPE_EXAM);
        $this->assertCount(10, $examQuestions);

        $excelQuestions = [
            'The risk an investor takes when investing in a debt obligation is called' => [
                'correct' => 'all of the choices',
                'answerCount' => 4,
                'feedback' => 'Credit risk entails the company failing and not being able to repay its debts. Inflation creates purchase power risk. Interest rate risk is concerned with changes in rates that adversely affect the investment.',
                'active' => 1
            ],
            'The risk that a law will adversely affect &frac12; an investment is called' => [
                'correct' => 'legislative risk.',
                'answerCount' => 4,
                'feedback' => 'Legislative risk occurs when there is a change in government policy that adversely affects an investment. For example, a new clean air requirement may create new expenses in certain industries that will affect growth. Social risk occurs when there is a change in social acceptance. For example, the change in public attitudes on the use of tobacco has drastically affected cigarette makers. Political risk usually involves the actions of a foreign country, as when an industry is nationalized.',
                'active' => 0
            ],
            'Whenever there is an offer to sell a new security issue, a ____________ must be provided to the client.' => [
                'correct' => 'prospectus',
                'answerCount' => 4,
                'feedback' => 'The key word is "new;" this is an IPO, which always requires a prospectus.',
                'active' => 1
            ],
            'A client of yours, age 58, once an investment with the highest possible monthly income. You have discussed risk versus reward in the possibility of losing principle. The client is in agreement that loss of principal for this investment would be acceptable. Your recommendation would be:' => [
                'correct' => 'Low-grade corporate bond fund',
                'answerCount' => 4,
                'feedback' => 'The low-grade corporate bond fund(also called a high-yield bond fund) would be the best choice from the choices listed in the question. Remember lower bond ratings mean higher yields.',
                'active' => 1
            ],
            'A DPP is considered to be' => [
                'correct' => 'highly illiquid.',
                'answerCount' => 4,
                'feedback' => 'A direct participation program is not only highly illiquid; it is not for the novice investor.',
                'active' => 1
            ],
            'You have a 43-year-old client, married with no children. Your review indicates both the husband and wife file a joint tax return and are in the 37% tax bracket. They will take limited risk but want their money in a high quality investment. Which of the following investment choices may be appropriate:' => [
                'correct' => 'Municipal bond fund',
                'answerCount' => 4,
                'feedback' => 'The factors relating to the municipal bond fund is the investment of choice for this example due to the need for a high quality investment in the high 39% tax bracket',
                'active' => 1
            ],
            'What is required on an illustration for variable universal life?' => [
                'correct' => 'The maximum investment return that can be shown is a gross rate of 12% provided a gross rate of 0% is shown.',
                'answerCount' => 4,
                'feedback' => 'Even though the maximum rate that can be shown is 12%, it must be reasonable considering the market conditions and the available investment options.',
                'active' => 1
            ],
            'Under what conditions may a registered representative do business under a fictitious name?<ol class="upper-roman"><li> never</li><li> when permitted by SIPC</li><li>when registered with the SEC</li><li> when registered with the appropriate SRO</li></ol>' => [
                'correct' => 'cat',
                'answerCount' => 4,
                'feedback' => 'An example of a fictitious name would be a DBA (doing business as) name. FINRA would be the appropriate SRO.',
                'active' => 1
            ],
            'An investment profile for a client consists of<ol class="upper-roman"><li> financial status.</li><li> non-financial status.</li><li> risk tolerance.</li><li> investment objectives.</li></ol>' => [
                'correct' => 'I, II, III, IV',
                'answerCount' => 4,
                'feedback' => 'An investment profile for a client includes all of the areas listed in the question. The net result is that you know enough about the client to make suitable recommendations.',
                'active' => 1
            ],
            'Most open-end funds offer breakpoints for increasing the amount invested over a designated period of time. Who or what may usually be used to combine investment amounts to reach a breakpoint?<ol class="upper-roman"><li>married couples</li><li> parents and their adult children</li><li>parents and their minor children</li><li> investment clubs</li></ol>' => [
                'correct' => 'false',
                'answerCount' => 2,
                'feedback' => 'Adult children have passed the age of majority and are considered to be on their own. Investment clubs are excluded, since they are composed of a group of adults who buy funds individually. That leaves the correct answers: payment &divide;market price = current yield.',
                'active' => 1
            ],
        ];

        // cleanup array
        foreach ($excelQuestions as $key => &$value) {
            $key = $this->convertStringToUtf8($key);
            $value['correct'] = $this->convertStringToUtf8($value['correct']);
            $value['feedback'] = $this->convertStringToUtf8($value['feedback']);
        }

        foreach ($examQuestions as $question) {
            $this->assertArrayHasKey($question->getQuestionText(), $excelQuestions);
            $this->assertEquals($excelQuestions[$question->getQuestionText()]['feedback'], $question->getFeedback());
            $this->assertEquals($excelQuestions[$question->getQuestionText()]['active'], $question->getActive());
            $this->assertCount($excelQuestions[$question->getQuestionText()]['answerCount'], $question->getAnswers());

            $foundCorrect = false;
            /** @var Entity\Answer $answer */
            foreach ($question->getAnswers() as $answer) {
                if ($answer->getAnswerText() === $excelQuestions[$question->getQuestionText()]['correct']) {
                    $this->assertTrue($answer->getCorrect(), $answer->getAnswerText());
                    $foundCorrect = true;
                } else {
                    $this->assertFalse($answer->getCorrect(), $answer->getAnswerText());
                }
            }
            $this->assertTrue($foundCorrect);
        }
    }

    public function testImportExamInvalidFile()
    {
        $message = null;

        try {
            $this->contentImporter->importExam('asdf');
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        $this->assertEquals("Unable to load excel exam file asdf due to File \"asdf\" does not exist.",
            $message);
    }

    public function testImportExamInvalidState()
    {
        $message = null;
        $code = uniqid();

        $industry = $this->generateIndustry();
        $this->getEntityManager()->persist($industry);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        try {
            $this->contentImporter->importExam($this->createTempExamFromValid($code, $industry->getName()));
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        $this->assertEquals("Invalid state LL.", $message);
    }

    public function testImportExamInvalidDuplicate()
    {
        $message = null;
        $industry = $this->generateIndustry();
        $exam = $this->generateExam();
        $exam->setIndustry($industry);
        $this->getEntityManager()->persist($industry);
        $this->getEntityManager()->persist($exam);
        $this->getEntityManager()->flush($exam);
        $this->getEntityManager()->clear();

        try {
            $this->contentImporter->importExam($this->createTempExamFromValid($exam->getCode(), $industry->getName()));
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        $this->assertEquals("Exam for {$exam->getCode()} already exists.", $message);
    }

    public function testImportExamValid()
    {
        $message = null;
        $response = null;
        $code = uniqid();
        $moduleCode1 = 'AA';
        $moduleCode2 = 'BB';
        $moduleCodes = [$moduleCode1, $moduleCode2];

        $industry = $this->generateIndustry();
        $state = $this->generateState();
        $module1 = $this->generateModule()->setCode($moduleCode1)->setIndustry($industry);
        $module2 = $this->generateModule()->setCode($moduleCode2)->setIndustry($industry);
        $this->getEntityManager()->persist($state);
        $this->getEntityManager()->persist($industry);
        $this->getEntityManager()->persist($module1);
        $this->getEntityManager()->persist($module2);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        try {
            $response = $this->contentImporter->importExam($this->createTempExamFromValid(
                    $code,
                    $industry->getName(),
                    $state->getCode(),
                    $moduleCodes
            ));
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        $this->assertNull($message);
        $this->assertTrue($response);

        /** @var Entity\Exam $exam */
        $exam = $this->getServiceManager()->get('examRepository')->findOneByCode($code);
        $this->assertEquals('Securities Series 6', $exam->getName());
        $this->assertEquals('Securities Series 6 goodies', $exam->getDescription());
        $this->assertEquals($industry->getName(), $exam->getIndustry()->getName());
        $this->assertEquals(135 * 60, $exam->getExamTime()); // minutes to seconds

        /** @var Entity\ExamModule[] $examModules */
        $examModules = $this->getServiceManager()->get('examModuleRepository')->findByExamId($exam->getId());
        $this->assertCount(2, $examModules);

        if ($examModules[0]->getName() ==='Regulatory fundamentals and business development') {
            $examModuleEntity1 = $examModules[0];
            $examModuleEntity2 = $examModules[1];
        } else {
            $examModuleEntity1 = $examModules[1];
            $examModuleEntity2 = $examModules[0];
        }

        $this->assertEquals(20, $examModuleEntity1->getPracticeQuestions());
        $this->assertEquals(22, $examModuleEntity1->getExamQuestions());
        $this->assertEquals(7, $examModuleEntity1->getPreassessmentQuestions());
        $this->assertEquals($moduleCode1, $examModuleEntity1->getModule()->getCode());

        $this->assertEquals(20, $examModuleEntity2->getPracticeQuestions());
        $this->assertEquals(47, $examModuleEntity2->getExamQuestions());
        $this->assertEquals(15, $examModuleEntity2->getPreassessmentQuestions());
        $this->assertEquals($moduleCode2, $examModuleEntity2->getModule()->getCode());
    }

    /**
     * @return string
     */
    protected function getValidModuleFilePath()
    {
        return getcwd() . DIRECTORY_SEPARATOR . $this->validModuleFilePath;
    }

    /**
     * @return string
     */
    protected function getValidModuleUpdateFilePath()
    {
        return getcwd() . DIRECTORY_SEPARATOR . $this->validModuleUpdateFilePath;
    }

    /**
     * @return string
     */
    protected function getValidExamFilePath()
    {
        return getcwd() . DIRECTORY_SEPARATOR . $this->validExamFilePath;
    }

    /**
     * use a valid module template to create a copy with a unique code
     *
     * @param string $moduleCode
     * @param string $industryName
     * @param string $stateName
     * @return mixed
     */
    protected function createTempModuleFromValid($moduleCode, $industryName = null, $stateName = null)
    {
        return $this->createTempExcelFileFromValidFile('module', $this->getValidModuleFilePath(), $moduleCode,
            $industryName, $stateName);
    }

    /**
     * use a valid module update template to create a copy with a unique code
     *
     * @param string $moduleCode
     * @param string $industryName
     * @param string $stateName
     * @return mixed
     */
    protected function createTempModuleUpdateFromValid($moduleCode, $industryName = null, $stateName = null)
    {
        return $this->createTempExcelFileFromValidFile('module', $this->getValidModuleUpdateFilePath(), $moduleCode,
            $industryName, $stateName);
    }

    /**
     * use a valid exam template to create a copy with a unique code
     *
     * @param string $examCode
     * @param string $industryName
     * @param string $stateCode
     * @param string[] $moduleCodes
     * @return mixed
     */
    protected function createTempExamFromValid($examCode, $industryName = null, $stateCode = null, $moduleCodes = [])
    {
        return $this->createTempExcelFileFromValidFile(
            'exam',
            $this->getValidExamFilePath(),
                $examCode,
                $industryName,
                $stateCode,
                $moduleCodes
        );
    }

    /**
     * @param string $type
     * @param string $filePath
     * @param string $code
     * @param string $industryName
     * @param string $stateCode
     * @param string[] $moduleCodes if passed, assume it's an exam excel
     * @return mixed
     */
    protected function createTempExcelFileFromValidFile($type, $filePath, $code, $industryName = null, $stateCode = null,
                                                        $moduleCodes = [])
    {
        $tempFile = tmpfile();
        $tempFilePath = stream_get_meta_data($tempFile)['uri'];
        fclose($tempFile);

        copy($filePath, $tempFilePath);

        /** @var Spreadsheet $excel */
        $excel = IOFactory::load($tempFilePath);

        /** @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $moduleSheet */
        $moduleSheet = $excel->getSheet(0);
        $moduleSheet->setCellValue('B1', $code);

        $industryPos = null;
        $statePos = null;

        switch ($type) {
            case 'exam':
                $industryPos = 'B4';
                $statePos = 'B5';
                break;

            case 'module':
                $industryPos = 'B3';
                $statePos = 'B4';
                break;
        }

        if (!empty($industryName) && !empty($industryPos)) {
            $moduleSheet->setCellValue($industryPos, $industryName);
        }

        if (!empty($stateCode) && !empty($statePos)) {
            $moduleSheet->setCellValue($statePos, $stateCode);
        }

        // in exam info page, update the module codes
        if ($type == 'exam' && !empty($moduleCodes)) {
            $startIndex = 9;
            foreach ($moduleCodes as $moduleCode) {
                $moduleSheet->setCellValue("A{$startIndex}", $moduleCode);
                $startIndex++;
            }
        }

        /** @var IWriter $writer */
        $writer = IOFactory::createWriter($excel, 'Xlsx');
        $writer->save($tempFilePath);

        return $tempFilePath;
    }

    /**
     * make sure we disable some extra stuff like caching for performance
     */
    protected function disableEntityListeners()
    {
        $this->updateEntityListenersConfig(false);
    }

    /**
     * enable what we disabled
     */
    protected function enableEntityListeners()
    {
        $this->updateEntityListenersConfig(true);
    }

    /**
     * update config
     * @param bool $status
     */
    protected function updateEntityListenersConfig($status = true)
    {
        $config = $this->getServiceManager()->get('config');

        $newConfig = new Config([
            'doctrine' => [
                'listeners' => [
                    'moduleQuestionListener' => $status,
                    'questionListener' => $status,
                    'answerListener' => $status
                ]
            ]
        ]);

        $config->merge($newConfig);
    }
}
