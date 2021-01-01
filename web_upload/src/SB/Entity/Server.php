<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace SB\Entity;

use Spot\MapperInterface;
use Spot\EntityInterface;

/**
 * FIELDS
 * @property integer        $sid
 * @property integer        $priority
 * @property string         $ip
 * @property integer        $port
 * @property integer        $modid
 * @property integer        $enabled
 *
 * RELATIONS
 * @property \SB\Entity\Mod $mod
 */
class Server extends AbstractEntity
{
    protected static $table = 'sb_servers';

    public static function fields()
    {
        return [
            'sid'       =>  ['type'     => 'integer',   'primary'   => true,    'autoincrement' => true],
            'priority'  =>  ['type'     => 'integer',   'required'  => true,    'default'       => 0],
            'ip'        =>  ['type'     => 'string',    'required'  => true],
            'port'      =>  ['type'     => 'integer',   'required'  => true,    'default'       => 27015,   'unsigned'  => true],
            'modid'     =>  ['type'     => 'integer',   'required'  => true],
            'enabled'   =>  ['type'     => 'boolean',   'required'  => true,    'default'       => true,    'unsigned'  => true],

            'token'     =>  ['type'     => 'string',    'required'  => true,    'notnull'       => false,   'default'       => ''],
        ];
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'mod'   => $mapper->belongsTo($entity, '\SB\Entity\Mod', 'modid'),
        ];
    }

    public function regenerateToken(&$token = null)
    {
        $token = base64_encode(random_bytes(48));

        $this->token = $token;
        $this->save();
    }

    public function getAdministrators()
    {
        return $this->getMapper('AdminServerGroup')
            ->where(['server_id' => $this->sid])
            ->with(['administrator'])
            ->all();
    }// 322776
}