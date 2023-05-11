<?php

namespace Hondros\ThirdParty\Doctrine\DBAL\Logging;

use Doctrine\DBAL\Logging\SQLLogger;

class ChromeSQLLogger implements SQLLogger
{
    /**
     *
     * @var \Monolog
     */
    protected $log;
    
    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
    	$this->log->debug("SQL: " . $sql);
        
        if (!is_array($params)) {
            $params[] = $params;
        }
        
        $paramsString = '';
        foreach ($params as $param) {
            if ($param === null) {
                $paramsString .= 'null';
            } elseif ($param instanceof \DateTime) {
                $paramsString .= $param->getTimestamp();
            } elseif (is_array($param)) {
                $paramsString .= serialize($param);
            } else {
                $paramsString .= (string)$param;
            }
            $paramsString .= '|';
        }
        
        if (!empty($paramsString)) {
            $this->log->debug('PARAMS: ' . substr($paramsString, 0, -1));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
    }
    
    public function setLog($log)
    {
        $this->log = $log;
    }
}
