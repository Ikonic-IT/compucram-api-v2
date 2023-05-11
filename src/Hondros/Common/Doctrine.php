<?php
namespace Hondros\Common;

class Doctrine
{
    /**
     * To remove proxy stuff we don't need
     * 
     * @param array $data
     * @return array
     */
    public function cleanUp($data)
    {
        // if we find a proxy that's not initialized, null it out
        foreach ($data as $key => &$value) {
            if (!is_object($value) && !is_array($value)) {
                continue;
            }

            if (is_array($value)) {
                $value = $this->cleanUp($value);
            } else if (0 === strpos(get_class($value), 'DoctrineProxies')) {
                $value = null;
            } else if (get_class($value) == 'Doctrine\ORM\PersistentCollection') {
                $value = $value->isInitialized() ? $this->cleanUp($value) : null;
            } else if (method_exists($value, '__isInitialized')) {
                $value = $value->__isInitialized() ? $this->cleanUp($value) : null;
            } else if (strpos(get_class($value), 'Entity') !== false) {
                $value = $this->cleanUp($value);
            }
        }
        
        return $data;
    }
}