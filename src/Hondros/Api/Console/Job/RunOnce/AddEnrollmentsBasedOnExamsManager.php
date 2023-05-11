<?php
/**
 * Created by PhpStorm.
 * User: joeyrivera
 * Date: 5/26/18
 * Time: 10:14 AM
 */

namespace Hondros\Api\Console\Job\RunOnce;

use Hondros\Api\Model\Repository;
use Hondros\Api\Model\Entity;
use Hondros\Api\Service;
use DateTime;
use Doctrine\ORM\EntityManager;


class AddEnrollmentsBasedOnExamsManager
{
    /**
     * @var array map the before exam to the new exam code
     */
    protected $examCodeMapper = [
        'RESAZ0215' => 'RESAZ0916',
        'RESPVAZ0215' => 'RESPVAZ0916'
    ];

    /**
     * Ignore any enrollments before this date
     */
    const CUT_OFF_DATE = '2018-01-01';

    /** @var Repository\Exam */
    protected $examRepository;

    /** @var Repository\Enrollment */
    protected $enrollmentRepository;

    /** @var Service\Enrollment */
    protected $enrollmentService;

    /** @var EntityManager */
    protected $entityManager;

    public function __construct(Repository\Exam $examRepository, Repository\Enrollment $enrollmentRepository,
        Service\Enrollment $enrollmentService, EntityManager $entityManager)
    {
        $this->examRepository = $examRepository;
        $this->enrollmentRepository = $enrollmentRepository;
        $this->enrollmentService = $enrollmentService;
        $this->entityManager = $entityManager;
    }

    public function addEnrollments()
    {
        $results = [
            'users' => 0,
            'enrollmentsCreated' => 0,
            'mappedExams' => []
        ];

        $examMapper = $this->mapExamCodesToExamIds($this->examCodeMapper);
        $results['mappedExams'] = $examMapper;

        // first find out what users have one or all of the exams we are looking for
        $users = $this->getUsersWithExams(array_keys($examMapper));
        $results['users'] = count($users);

        if (empty($users)) {
            return $results;
        }

        $userIds = array_keys($users);
        unset($users);

        // foreach user, add the new enrollment(s)
        foreach ($userIds as $userId) {
            $enrollmentsAdded = $this->addEnrollmentsForUser($userId, $examMapper);
            $results['enrollmentsCreated'] += $enrollmentsAdded;
            $this->entityManager->clear();
        }

        return $results;
    }

    /**
     * Get exam ids from the exam codes
     *
     * @param array each row should be the before exam code (key) and the after exam code (value)
     * @return array
     */
    protected function mapExamCodesToExamIds($examCodeMapper)
    {
        $examMapper = [];
        foreach ($examCodeMapper as $beforeCode => $afterCode) {
            $beforeExam = $this->examRepository->findOneByCode($beforeCode);

            if (empty($beforeExam)) {
                throw new \InvalidArgumentException("No exam found for before code {$beforeCode}");
            }

            $afterExam = $this->examRepository->findOneByCode($afterCode);

            if (empty($afterExam)) {
                throw new \InvalidArgumentException("No exam found for after code {$afterCode}");
            }

            $examMapper[$beforeExam->getId()] = $afterExam->getId();
        }

        return $examMapper;
    }

    /**
     * Only get the users that meet the criteria
     *
     * @param array $examIds
     * @return Entity\User[]
     * @throws \Doctrine\ORM\ORMException
     */
    protected function getUsersWithExams($examIds)
    {
        $users = [];
        $cutOff = new DateTime(self::CUT_OFF_DATE);

        /** @var Entity\Enrollment[] $enrollments */
        $enrollments = $this->enrollmentRepository->findByExamId($examIds);

        foreach ($enrollments as $enrollment) {
            // make sure they fall into the time window
            if ($enrollment->getCreated() < $cutOff) {
                continue;
            }

            $user = $enrollment->getUser();
            $users[$user->getId()] = $user;
        }

        return $users;
    }

    /**
     * @param int $userId
     * @param array $examMapper
     * @return int
     * @throws \Doctrine\ORM\ORMException
     */
    protected function addEnrollmentsForUser($userId, $examMapper)
    {
        /** @var Entity\Enrollment[] $enrollments */
        $enrollments = $this->enrollmentRepository->findByUserId($userId);
        $enrollmentsAdded = 0;

        if (empty($enrollments)) {
            return $enrollmentsAdded;
        }

        $foundExamIds = array_map(function ($enrollment) { return $enrollment->getExamId(); }, $enrollments);

        if (empty($foundExamIds)) {
            return $enrollmentsAdded;
        }

        foreach ($examMapper as $beforeId => $afterId) {
            if (in_array($beforeId, $foundExamIds) && !in_array($afterId, $foundExamIds)) {
                $this->enrollmentService->save([
                    'userId' => $userId,
                    'examId' => $afterId,
                    'organizationId' => $enrollments[0]->getOrganizationId()
                ]);
                $enrollmentsAdded++;
            }
        }

        return $enrollmentsAdded;
    }
}