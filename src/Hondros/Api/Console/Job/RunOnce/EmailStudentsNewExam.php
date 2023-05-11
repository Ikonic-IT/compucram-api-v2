<?php

namespace Hondros\Api\Console\Job\RunOnce;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class EmailStudentsNewExam
 * @package Hondros\Api\Console\Job\RunOnce
 *
 * We had an instance were an exam needed to be changed and we didn't have the functionality to add a new
 * category to an exam and instead decided to give all students the new exam and email telling them why.
 */
class EmailStudentsNewExam extends Command
{
    /**
     * @var \Laminas\ServiceManager\ServiceManager
     */
    protected $serviceManager;

    /**
     * @return \Laminas\ServiceManager\ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * @param mixed $serviceManager
     * @return EmailStudentsNewExam
     */
    public function setServiceManager($serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }

    protected function configure()
    {
        $this->setName("runOnce:emailStudentsNewExam")
            ->setDescription("Email list of students notification about their new exam enrollment.")
            ->addOption("examId", "e", InputOption::VALUE_REQUIRED,
                "Exam id you want to enroll each student in.", 0)
            ->addOption("wetRun", "w", InputOption::VALUE_OPTIONAL,
                "Pass this value when ready to do a wet run. Defaults to dry run.", false);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $wetRun = $input->getOption('wetRun');
        $examId = (int) $input->getOption('examId');

        if (!is_int($examId) || !($examId > 0)) {
            $output->writeln("Invalid exam id.");
            return false;
        }

        /**
         * 0 => id,
         * 1 => created,
         * 2 => started,
         * 3 => user_id,
         * 4 => first_name,
         * 5 => last_name,
         * 6 => email
         */
        $data = [];

        $file = fopen("tmp" . DIRECTORY_SEPARATOR . "migrate exams.csv","r");

        while(!feof($file)) {
            $data[] = fgetcsv($file);

            if (!$wetRun && count($data) === 2) {
                break;
            }
        }

        fclose($file);

        // remove heading row
        unset($data[0]);

        $from = 'support@compucram.com';
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=utf8' . "\r\n";
        $headers .= 'From: '.$from."\r\n".
            'Reply-To: '.$from."\r\n" .
            'X-Mailer: PHP/' . phpversion();

        $subject = 'Change in Illinois Broker Testing Provider';
        $html = "
            <html>
                <body>
                    <p>Hi [NAME],</p>
                    <p>This email is to notify you that on 12/1/2019, the Illinois Broker Licensing exam will change testing providers from AMP to PSI. This change could mean new topics for the national licensing exam, and that your current AMP National + Illinois CompuCram course may reflect some out-of-date content.</p>
                    <p>To ensure your success, in addition to your originally purchased AMP National + Illinois course, your CompuCram account also has been registered for the new PSI National + Illinois course to address these new topics, <strong>free of charge.</strong></p>
                    <p>Please note that your progress and content in the original CompuCram course is still available in your account. The new course is simply another resource to fortify your knowledge.</p>
                    <p>When <a href='https://app.compucram.com/#/login'>logging into your account</a>, you will now see the course options as shown below:</p>
                    <p><img width='450' src='https://www.compucram.com/media/content/landing/exams2.png'></p>
                    <p>If you have any questions or would like to know more, just call us at 1-877-812-3269 or email us at <a href='mailto:support@compucram.com'>Support@CompuCram.com.</p>
                    <p>Sincerely,</p>
                    <p>The CompuCram Customer Services Team</p>
                    <p>CompuCram</p>
                </body>
            </html>
        ";

        /** @var Hondros\Api\Service\Enrollment $service */
        $service = $this->getServiceManager()->get('enrollmentService');
        $params = [
            'userId' => 0,
            'examId' => $examId,
            'organizationId' => 1000
        ];

        /**
         * loop through all data,
         * for all, enroll in exam
         * for all active enrollments, send email
         */
        foreach ($data as $row) {
            $params['userId'] = $row[3];

            try {
                $service->save($params);
                $this->getServiceManager()->get('entityManager')->clear();
                $output->writeln("Added exam to user {$params['userId']}.");
            } catch (\Exception $e) {
                $output->writeln("Unable to add exam to user {$params['userId']} due to {$e->getMessage()}.");
                continue;
            }

            if ($row[2] === 'NULL') {
                $output->writeln("Skipping mailing {$row[0]} due to not started.");
                continue;
            }

            $html = str_replace('[NAME]', trim($row[4]), $html);

            if (mail($row[6], $subject, $html, $headers)) {
                $output->writeln("Sent email to {$row[6]}.");
            } else {
                $output->writeln("Unable to send email to {$row[6]}.");
            }
        }

        $output->writeln("Done.");
    }
}