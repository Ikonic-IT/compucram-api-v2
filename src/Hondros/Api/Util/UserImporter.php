<?php

namespace Hondros\Api\Util;

use Hondros\Api\Model\Entity;
use Hondros\Api\Service;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Laminas\Config\Config as LaminasConfig;
use InvalidArgumentException;
use Exception;
use DateTime;

class UserImporter
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;
    
    /**
     * @var \Monolog\Logger
     */
    protected $logger;
    
    /**
     * @var \Hondros\Api\Service\User
     */
    protected $userService;
    
    /**
     * @var \Hondros\Api\Service\Enrollment
     */
    protected $enrollmentService;
    
    /**
     * @var \Laminas\Config\Config
     */
    protected $config;
    
    public function __construct(EntityManager $entityManager, Logger $logger, Service\User $userService, 
        Service\Enrollment $enrollmentService, LaminasConfig $config)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->userService = $userService;
        $this->enrollmentService = $enrollmentService;
        $this->config = $config;
    }
    
    public function importDefaults()
    {
        $path = realpath(getcwd() . $this->config->import->userPath);
        $file = realpath($path . '/defaults.xlsx');
        
        $items = [];
        $errors = [];
        $excel = IOFactory::load($file);
        $date = new DateTime();
        
        // sheets
        $userSheet = $excel->getSheet(0);
        
        // loop and start adding
        for ($x = 2; $x <= $userSheet->getHighestRow(); $x++) {
            // if nothing then we are done
            if (empty($userSheet->getCell("A{$x}")->getValue())) {
                break;
            }
            
            // params
            $email = trim($userSheet->getCell("A{$x}"));
            $firstName = trim($userSheet->getCell("B{$x}"));
            $lastName = trim($userSheet->getCell("C{$x}"));
            $password = trim($userSheet->getCell("D{$x}"));
            $examCodes = explode(',', trim($userSheet->getCell("E{$x}")));
            $perpetual = (boolean) trim($userSheet->getCell("F{$x}"));
            
            // is user in the system?
            try {
                $user = $this->userService->findByEmail($email);
            } catch (Exception $e) {
                // if 404 then create
                if ($e->getCode() == 404) {
                    $user = $this->userService->save([
                        'email' => $email,
                        'firstName' => $firstName, 
                        'lastName' => $lastName,
                        'password' => $password
                    ]);
                    
                    $items[] = $email;
                } else {
                    $errors[] = "Error adding user {$email} with code " . $e->getCode() . " " . $e->getMessage();
                }
            }
            
            // enroll in all exams
            foreach ($examCodes as $examCode) {
                try {
                    $enrollment = $this->enrollmentService->save([
                        'userId' => $user['id'],
                        'examCode' => $examCode,
                        'organizationId' => 1000,
                        'full' => true,
                        'perpetual' => $perpetual
                    ]);
                    
                    $enrollments[] = $enrollment['id'];
                } catch (Exception $e) {
                    $errors[] = "Error enrolling user {$email} to exam code {$examCode} " . $e->getCode() . " " . $e->getMessage();
                }
            }
            
            // clean up
            unset($user);
            unset($enrollment);
            
            // clear up uof
            $this->entityManager->getUnitOfWork()->clear();
        }
        
        // clean up
        unset($excel);

        return [
            'sucess' => true,
            'itemsAdded' => count($items),
            'errors' => $errors
        ];
    }
}