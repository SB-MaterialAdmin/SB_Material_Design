<?php

namespace SB\Entity;

use Framework\Entity\AbstractEntity;
use Spot\MapperInterface;
use Spot\EntityInterface;

/**
 * FIELDS
 * @property integer                    $bid
 *
 * FIELDS
 * @property string                     $authid
 * @property string                     $name
 * @property integer                    $created
 * @property integer                    $ends
 * @property integer                    $length
 * @property string                     $reason
 * @property integer                    $aid
 * @property string                     $adminIp
 * @property integer                    $sid
 * @property integer                    $unban_type
 * @property integer                    $ends
 * @property integer|null               $ban_closed
 *
 * FIELDS
 * @property integer|null               $RemovedBy
 * @property string|null                $RemoveType
 * @property integer|null               $RemovedOn
 * @property integer|null               $type
 * @property string|null                $ureason
 *
 * RELATIONS
 * @property \SB\Entity\Administrator   $admin
 * @property \SB\Entity\Administrator   $unbanned_by
 */
abstract class AbstractPunishment extends AbstractEntity
{
    public static function fields()
    {
        return [
            'bid'               =>  ['type'     => 'integer',   'primary'   => true,    'autoincrement' => true],

            'authid'            =>  ['type'     => 'string',    'required'  => true,    'default'       => ''],
            'name'              =>  ['type'     => 'string',    'required'  => true,    'default'       => 'unnamed'],
            'created'           =>  ['type'     => 'integer',   'required'  => true,    'default'       => \Framework::$time,   'unsigned'  => true],
            'ends'              =>  ['type'     => 'integer',   'required'  => true,    'default'       => \Framework::$time,   'unsigned'  => true],
            'length'            =>  ['type'     => 'integer',   'required'  => true,    'default'       => 0,                   'unsigned'  => true],
            'reason'            =>  ['type'     => 'string',    'required'  => true,    'default'       => ''],
            'aid'               =>  ['type'     => 'integer',   'required'  => true,    'default'       => 0,                   'unsigned'  => true],
            'adminIp'           =>  ['type'     => 'string',    'required'  => true],
            'sid'               =>  ['type'     => 'integer',   'required'  => true,    'default'       => 0,                   'unsigned'  => true],
            'unban_type'        =>  ['type'     => 'integer',   'required'  => true,    'default'       => 0,                   'unsigned'  => true,    'notnull'  => false],
            'ban_closed'        =>  ['type'     => 'integer',   'required'  => true,    'unsigned'      => true,                'notnull'   => false],

            'RemovedBy'         =>  ['type'     => 'integer',   'required'  => true,    'unsigned'      => true,                'notnull'   => false],
            'RemovedType'       =>  ['type'     => 'string',    'required'  => true,    'notnull'       => false],
            'RemovedOn'         =>  ['type'     => 'integer',   'required'  => true,    'unsigned'      => true,                'notnull'   => false],
            'type'              =>  ['type'     => 'smallint',  'required'  => true,    'unsigned'      => true,                'notnull'   => false],
            'ureason'           =>  ['type'     => 'string',    'required'  => true,    'notnull'       => false]
        ];
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'admin'             =>  $mapper->belongsTo($entity, '\SB\Entity\Administrator', 'aid'),
            'unbanned_by'       =>  $mapper->belongsTo($entity, '\SB\Entity\Administrator', 'RemovedBy'),
        ];
    }

    public function isActive()
    {
        return $this->RemovedOn != null && ($this->length == 0 || ($this->created + $this->length <= $this->ends));
    }
}