<?php

namespace SB\Entity;

use Framework\Entity\AbstractEntity;

/**
 * FIELDS
 * @property integer    $mid
 * @property string     $name
 * @property string     $icon
 * @property string     $modfolder
 * @property integer    $steam_universe
 * @property integer    $enabled
 */
class Mod extends AbstractEntity
{
    protected static $table = 'sb_mods';

    public static function fields()
    {
        return [
            'mid'               =>  ['type'     => 'integer',   'primary'   => true,    'autoincrement' => true],
            'name'              =>  ['type'     => 'string',    'required'  => true],
            'icon'              =>  ['type'     => 'string',    'required'  => true],
            'modfolder'         =>  ['type'     => 'string',    'required'  => true],
            'steam_universe'    =>  ['type'     => 'integer',   'required'  => true],
            'enabled'           =>  ['type'     => 'boolean',   'required'  => true,    'default'       => true,    'unsigned'  => true],
        ];
    }
}