<?php


namespace SalesRender\Plugin\Components\Db\ModelTest;


use Medoo\Medoo;
use PHPUnit\Framework\TestCase;
use SalesRender\Plugin\Components\Db\Commands\CreateTablesCommand;
use SalesRender\Plugin\Components\Db\Components\Connector;
use SalesRender\Plugin\Components\Db\Components\PluginReference;
use SalesRender\Plugin\Components\Db\Components\TestModelClass;
use SalesRender\Plugin\Components\Db\Components\TestPluginModelClass;
use SalesRender\Plugin\Components\Db\Components\TestSinglePluginModelClass;
use Symfony\Component\Console\Tester\CommandTester;

class deleteModelsTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        Connector::config(
            new Medoo([
                'database_type' => 'sqlite',
                'database_file' => ':memory:'
            ])
        );

        Connector::setReference(new PluginReference(1, 'user', 2));

        $command = new CreateTablesCommand();
        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    public function testDeleteTestModelClass()
    {
        $model = new TestModelClass();
        $model->setId(11);
        $model->value_1 = 11;
        $model->value_2 = 'Hello world 11';
        $model->save();
        TestPluginModelClass::freeUpMemory();
        $modelNew = new TestModelClass();
        $modelNew->setId(12);
        $modelNew->value_1 = 11;
        $modelNew->value_2 = 'Hello world 11';
        $modelNew->save();
        $model->delete();
        $result = TestModelClass::findByCondition(['value_2' => 'Hello world 11']);
        $this->assertArrayHasKey('12', $result);
        $this->assertEquals($modelNew, $result[12]);
    }

    public function testDeleteTestPluginModelClass()
    {
        $model = new TestPluginModelClass();
        $model->setId(11);
        $model->value_1 = 11;
        $model->value_2 = 'Hello world 11';
        $model->save();
        TestPluginModelClass::freeUpMemory();
        $modelNew = new TestPluginModelClass();
        $modelNew->setId(12);
        $modelNew->value_1 = 11;
        $modelNew->value_2 = 'Hello world 11';
        $modelNew->save();
        $model->delete();
        $result = TestPluginModelClass::findByCondition(['value_2' => 'Hello world 11']);
        $this->assertArrayHasKey('12', $result);
        $this->assertEquals($modelNew, $result[12]);
    }

    public function testDeleteTestSinglePluginModelClass()
    {
        $model = new TestSinglePluginModelClass();
        $model->value_1 = 11;
        $model->value_2 = 'Hello world 11';
        $model->save();
        TestSinglePluginModelClass::freeUpMemory();
        $modelNew = TestSinglePluginModelClass::find();
        $modelNew->delete();
        $result = TestSinglePluginModelClass::find();
        $this->assertNull($result);
    }

}