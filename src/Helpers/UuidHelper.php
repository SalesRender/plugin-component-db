<?php


namespace SalesRender\Plugin\Components\Db\Helpers;


use Ramsey\Uuid\Uuid;

class UuidHelper
{
    public static function getUuid(): string
    {
        return Uuid::uuid4();
    }
}