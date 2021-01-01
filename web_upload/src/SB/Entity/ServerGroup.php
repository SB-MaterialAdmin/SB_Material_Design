<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace SB\Entity;

use Framework\Entity\AbstractEntity;
use Spot\MapperInterface;
use Spot\EntityInterface;

/**
 * FIELDS
 * @property integer        $id
 * @property string         $name
 * @property string         $flags
 * @property integer        $immunity
 * @property string         $groups_immune
 * @property integer        $maxbantime
 * @property integer        $maxmutetime
 */
class ServerGroup extends AbstractEntity
{
    protected static $table = 'sb_srvgroups';

    public static function fields()
    {
        return [
            'id'            =>  ['type'     => 'integer',   'primary'   => true,    'autoincrement' => true],
            'name'          =>  ['type'     => 'string',    'required'  => true],

            'flags'         =>  ['type'     => 'string',    'required'  => true,    'default'       => ''],
            'immunity'      =>  ['type'     => 'integer',   'required'  => true,    'default'       => 0],

            // TODO: maybe drop this columns?
            'groups_immune' =>  ['type'     => 'string',    'required'  => true,    'default'       => ''],
            'maxbantime'    =>  ['type'     => 'integer',   'required'  => true,    'default'       => -1],
            'maxmutetime'   =>  ['type'     => 'integer',   'required'  => true,    'default'       => -1],
        ];
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'overrides' =>  $mapper->hasMany($entity, '\SB\Entity\ServerGroupOverride', 'group_id')->order(['access'    => 'ASC']),
        ];
    }
}