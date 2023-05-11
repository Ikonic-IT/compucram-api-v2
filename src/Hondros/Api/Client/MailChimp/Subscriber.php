<?php
/**
 * Created by PhpStorm.
 * User: Joey
 * Date: 8/16/2015
 * Time: 5:42 PM
 */

namespace Hondros\Api\Client\MailChimp;

/**
 * Class Subscriber
 * @package Hondros\Api\Client\MailChimp
 *
 * This represents a subscriber sent over to a mailchimp list. It takes care of mapping properties to
 * marge fields. The idea is to add all maps in the mergeFieldMapper array and any property that is filled
 * will automatically be sent over when using getMergeFields.
 */
class Subscriber
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var array these are the different merge variables created in mailchimp lists
     */
    protected $mergeFieldMapper = [
        'FNAME' => 'firstName',
        'LNAME' => 'lastName',
        'ENROLLDATE' => 'enrollmentDate',
        'PRODUCTID' => 'productId',
        'PRODUCTCOD' => 'productCode',
        'INDUSTRYNA' => 'industryName'
    ];

    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $firstName;

    /**
     * @var string
     */
    protected $lastName;

    /**
     * @var string
     */
    protected $enrollmentDate;

    /**
     * @var int magento production id ex: 12
     */
    protected $productId;

    /**
     * @var string the exam code ex: HINAT0215
     */
    protected $productCode;

    /**
     * @var string
     */
    protected $industryName;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return Subscriber
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     * @return Subscriber
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param mixed $firstName
     * @return Subscriber
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $lastName
     * @return Subscriber
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEnrollmentDate()
    {
        return $this->enrollmentDate;
    }

    /**
     * @param \DateTime $enrollmentDate
     * @return Subscriber
     */
    public function setEnrollmentDate($enrollmentDate)
    {
        $this->enrollmentDate = $enrollmentDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param mixed $productId
     * @return Subscriber
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProductCode()
    {
        return $this->productCode;
    }

    /**
     * @param mixed $productCode
     * @return Subscriber
     */
    public function setProductCode($productCode)
    {
        $this->productCode = $productCode;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIndustryName()
    {
        return $this->industryName;
    }

    /**
     * @param mixed $industryName
     * @return Subscriber
     */
    public function setIndustryName($industryName)
    {
        $this->industryName = $industryName;
        return $this;
    }

    /**
     * returns an array in the format mailchimp wants
     * @param bool $all
     * @return array
     */
    public function getMergeFields($all = false)
    {
        $mergeFields = [];
        foreach ($this->mergeFieldMapper as $key => $value) {
            if (!$all && is_null($this->$value)) {
                continue;
            }

            $mergeFields[$key] = $this->$value;
        }

        return $mergeFields;
    }
}