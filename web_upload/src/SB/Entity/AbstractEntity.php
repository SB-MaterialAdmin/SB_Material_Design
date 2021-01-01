<?php

namespace SB\Entity;

use Spot\EventEmitter;

abstract class AbstractEntity extends \Framework\Entity\AbstractEntity
{
    private $_validators = [];

    protected static function onListenerSetup()
    {
    }

    protected static function validateEnumValue($fieldName, array $allowedValues)
    {
        self::addValidator(function (Entity $entity, Mapper $mapper) use ($fieldName, $allowedValues)
        {
            $value = $entity->get($fieldName);
            
            if (!in_array($value, $allowedValues))
            {
                throw new \Exception('Invalid value for ' . $fieldName);
            }
        });
    }

    protected static function addValidator(\Closure $closure)
    {
        $_validators[] = $closure;
    }

    public static function events(EventEmitter $eventEmitter)
    {
        self::onListenerSetup();

        $eventEmitter->on('beforeValidate', function (Entity $entity, Mapper $mapper)
        {
            foreach ($this->_validators as $validator)
            {
                if ($validator($entity, $mapper) === FALSE)
                {
                    return FALSE;
                }
            }

            return TRUE;
        });

        return parent::events($eventEmitter);
    }
    
    /**
     * @return \Framework\Db\Mapper
     */
    protected function getMapper($entity)
    {
        return \Framework::container()->get('database')->mapper('\\SB\\Entity\\' . $entity);
    }
}