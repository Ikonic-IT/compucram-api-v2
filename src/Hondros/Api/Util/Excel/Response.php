<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 3/21/17
 * Time: 3:41 PM
 */

namespace Hondros\Api\Util\Excel;


class Response
{
    /**
     * @var boolean
     */
    protected $success = true;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var array
     */
    protected $errors = array();

    /**
     * @return bool
     */
    public function isValid()
    {
        return empty($this->errors) && $this->success;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @param string $filePath
     * @return $this
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;

        return $this;
    }

    /**
     * @param string $message
     * @return $this;
     */
    public function addError($message)
    {
        $this->errors[] = $message;

        return $this;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}