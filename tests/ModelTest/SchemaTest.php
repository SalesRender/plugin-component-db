<?php


namespace SalesRender\Plugin\Components\Db\ModelTest;


use PHPUnit\Framework\TestCase;
use SalesRender\Plugin\Components\Db\Components\TestModelClass;
use SalesRender\Plugin\Components\Db\Components\TestPluginModelClass;
use SalesRender\Plugin\Components\Db\Components\TestSinglePluginModelClass;

class SchemaTest extends TestCase
{

    public function testSchemaTestModelClass()
    {
        $result = TestModelClass::schema();
        $expected = [
            'value_1' => ['INT'],
            'value_2' => ['VARCHAR(255)'],
        ];
        $this->assertSame($expected, $result);
    }

    public function testSchemaTestPluginModelClass()
    {
        $result = TestPluginModelClass::schema();
        $expected = [
            'value_1' => ['INT'],
            'value_2' => ['VARCHAR(255)'],
        ];
        $this->assertSame($expected, $result);
    }

    public function testSchemaTestSinglePluginModelClass()
    {
        $result = TestSinglePluginModelClass::schema();
        $expected = [
            'value_1' => ['INT'],
            'value_2' => ['VARCHAR(255)'],
        ];
        $this->assertSame($expected, $result);
    }
}