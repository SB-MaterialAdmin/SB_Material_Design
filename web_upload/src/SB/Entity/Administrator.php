<?php

namespace SB\Entity;

use Framework\Entity\AbstractEntity;
use Spot\MapperInterface;
use Spot\EntityInterface;

/**
 * FIELDS
 * @property integer        $aid
 * @property string         $user
 * @property string         $authid
 * @property string         $password
 * @property integer        $gid
 * @property string         $email
 * @property string|null    $validate
 * @property integer        $extraflags
 * @property integer        $immunity
 * @property string|null    $srv_group
 * @property string|null    $srv_flags
 * @property string|null    $srv_password
 * @property integer|null   $lastvisit
 * @property integer|null   $expired
 * @property boolean|null   $support
 * @property string|null    $vk
 * @property string|null    $skype
 * @property string|null    $comment
 *
 * RELATIONS
 * 
 */
class Administrator extends AbstractEntity
{
    protected static $table = 'sb_admins';

    public static function fields()
    {
        return [
            'aid'               =>  ['type'     => 'integer',   'primary'   => true,    'autoincrement' => true],
            'user'              =>  ['type'     => 'string',    'required'  => true],
            'authid'            =>  ['type'     => 'string',    'required'  => true,    'default'       => ''],
            'password'          =>  ['type'     => 'string',    'required'  => true,    'default'       => ''],
            'gid'               =>  ['type'     => 'integer',   'required'  => true,    'default'       => -1],
            'email'             =>  ['type'     => 'string',    'required'  => true],
            'validate'          =>  ['type'     => 'string',    'notnull'   => false],
            'extraflags'        =>  ['type'     => 'integer',   'required'  => true,    'default'       => 0],
            'immunity'          =>  ['type'     => 'integer',   'required'  => true,    'default'       => 0],

            'srv_group'         =>  ['type'     => 'string',    'notnull'   => false],
            'srv_flags'         =>  ['type'     => 'string',    'notnull'   => false],
            'srv_password'      =>  ['type'     => 'string',    'notnull'   => false],

            'lastvisit'         =>  ['type'     => 'integer',   'notnull'   => false,   'default'       => 0],
            'expired'           =>  ['type'     => 'integer',   'notnull'   => false,   'default'       => 0],

            'support'           =>  ['type'     => 'boolean',   'notnull'   => false,   'default'       => false],

            'vk'                =>  ['type'     => 'string',    'notnull'   => false],
            'skype'             =>  ['type'     => 'string',    'notnull'   => false],
            'comment'           =>  ['type'     => 'string',    'notnull'   => false]
        ];
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'server_group'  => $mapper->belongsTo($entity, '\SB\Entity\Administrator', 'srv_group'),
        ];
    }

    public function updateLastVisit()
    {
        $this->lastvisit = \Framework::$time;
        $this->save();

        return $this;
    }

    public function isActive()
    {
        $expireTime = $this->expired;
        $time = \Framework::$time;

        return ($expireTime == null || $expireTime == 0) || ($expireTime >= $time);
    }
}