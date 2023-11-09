<?php


namespace SalesRender\Plugin\Components\Db\ModelTest;


use PHPUnit\Framework\TestCase;
use SalesRender\Plugin\Components\Db\Components\TestModelClass;
use SalesRender\Plugin\Components\Db\Components\TestPluginModelClass;
use SalesRender\Plugin\Components\Db\Components\TestSinglePluginModelClass;

class tableNameTest extends TestCase
{

    public function testTableNameTestModelClass()
    {
        $this->assertEquals('TestModelClass', TestModelClass::tableName());
    }

    public function testTableNameTestPluginModelClass()
    {
        $this->assertEquals('TestPluginModelClass', TestPluginModelClass::tableName());
    }

    public function testTableNameTestSinglePluginModelClass()
    {
        $this->assertEquals('TestSinglePluginModelClass', TestSinglePluginModelClass::tableName());
    }
}