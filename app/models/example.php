<?php


namespace app\models;


use system\kernel\Database\Model;

class example extends Model
{
    protected string $db = 'default';
    protected string $tableName = 'test';
    protected bool $timestamp = false;

}