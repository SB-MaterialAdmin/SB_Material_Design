<?php

namespace SB\Entity;

use Framework\Entity\AbstractEntity;

/**
 * FIELDS
 * @property integer    $id
 * @property string     $type
 * @property string     $name
 */
abstract class AbstractOverride extends AbstractEntity
{
    public static function fields()
    {
        return [
            'id'    =>  ['type'     => 'integer',   'primary'   => true,    'autoincrement' => true],
            'type'  =>  ['type'     => 'string',    'required'  => true,    'default'       => 'command'],
            'name'  =>  ['type'     => 'string',    'required'  => true],
        ];
    }

    protected static function onListenerSetup()
    {
        self::validateEnumValue('type', ['command', 'group']);
    }
}