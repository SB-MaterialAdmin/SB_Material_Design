<?php

namespace SB\Entity;

/**
 * FIELDS
 * @property string|null    $ip
 * @property string|null    $country
 */
class Ban extends AbstractPunishment
{
    protected static $table = 'sb_bans';

    public static function fields()
    {
        return parent::fields() + [
            'ip'        =>  ['type' => 'string',    'required'  => false,   'notnull'   => false],
            'country'   =>  ['type' => 'string',    'required'  => false,   'notnull'   => false],
        ];
    }
}