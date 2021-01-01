<?php

namespace SB\Entity;

use Spot\MapperInterface;
use Spot\EntityInterface;

/**
 * FIELDS
 * @property integer                $group_id
 * @property string                 $access
 * 
 * RELATIONS
 * @property \SB\Entity\ServerGroup $group
 */
class ServerGroupOverride extends AbstractOverride
{
    protected static $table = 'sb_srvgroups_overrides';

    public static function fields()
    {
        return parent::fields() + [
            'group_id'  =>  ['type' => 'integer',   'required'  => true],
            'access'    =>  ['type' => 'string',    'required'  => true,    'default'   => 'allow']
        ];
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'group' => $mapper->belongsTo($entity, '\SB\Entity\ServerGroup', 'group_id'),
        ];
    }

    public static function onListenerSetup()
    {
        self::validateEnumValue('access',   ['allow', 'deny']);
    }
}