<?php

namespace SB\Entity;

use Spot\MapperInterface;
use Spot\EntityInterface;

/**
 * FIELDS
 * @property string $flags
 */
class Override extends AbstractOverride
{
    protected static $table = 'sb_overrides';

    public static function fields()
    {
        return parent::fields() + [
            'flags' =>  ['type' => 'string',    'required'  => true,    'default'   => ''],
        ];
    }
}