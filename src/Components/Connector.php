<?php
/**
 * Created for plugin-component-db
 * Datetime: 06.02.2020 14:36
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace SalesRender\Plugin\Components\Db\Components;


use Medoo\Medoo;
use RuntimeException;

class Connector
{

    protected static ?Medoo $db = null;

    protected static PluginReference $reference;

    private function __construct() {}

    public static function config(Medoo $medoo): void
    {
        self::$db = $medoo;
    }

    public static function db(): Medoo
    {
        if (is_null(static::$db)) {
            throw new RuntimeException('Medoo was not configured', 1000);
        }

        return static::$db;
    }

    public static function hasReference(): bool
    {
        return isset(self::$reference);
    }

    public static function getReference(): PluginReference
    {
        if (!isset(self::$reference)) {
            throw new RuntimeException('Plugin reference is not configured');
        }
        return static::$reference;
    }

    public static function setReference(PluginReference $reference)
    {
        static::$reference = $reference;
    }



}