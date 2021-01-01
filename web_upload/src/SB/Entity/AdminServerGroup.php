<?php


namespace SB\Entity;

use Spot\EntityInterface;
use Spot\MapperInterface;

/**
 * FIELDS
 * @property integer        $permission_combination_id
 * @property integer        $admin_id
 * @property integer|null   $group_id
 * @property integer|null   $srv_group_id
 * @property integer|null   $server_id
 *
 * RELATIONS
 * @property \SB\Entity\Administrator|null  $administrator
 * @property \SB\Entity\GroupServer|null    $srv_group
 * @property \SB\Entity\Server|null         $server
 */
class AdminServerGroup extends AbstractEntity
{
    protected static $table = 'sb_admins_servers_groups';

    public static function fields()
    {
        return [
            'permission_combination_id' =>  ['type' => 'integer',   'required'  => true,    'primary'   => true,    'autoincrement' => true],
            'admin_id'                  =>  ['type' => 'integer',   'required'  => true],
            'group_id'                  =>  ['type' => 'integer',   'notnull'   => false],
            'srv_group_id'              =>  ['type' => 'integer',   'notnull'   => false],
            'server_id'                 =>  ['type' => 'integer',   'notnull'   => false],
        ];
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'administrator' =>  $mapper->belongsTo($entity, '\SB\Entity\Administrator', 'admin_id'),
            'server'        =>  $mapper->belongsTo($entity, '\SB\Entity\Server', 'server_id'),
            //'srv_group'     =>  $mapper->belongsTo($entity, '\SB\Entity\GroupServer', 'srv_group_id'),
            // TODO: add custom groups for servers (column `group_id`)
        ];
    }
}