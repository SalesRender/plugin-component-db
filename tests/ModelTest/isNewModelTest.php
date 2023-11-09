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

class isNewModelTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUpBeforeClass();
        Connector::config(
            new Medoo([
                'database_type' => 'sqlite',
                'database_file' => ':memory:'
            ])
        );

        Connector::setReference(new PluginReference(1, 'user', 2));
    }

    public function testIsNewModelTestModelClass()
    {
        $command = new CreateTablesCommand();
        $tester = new CommandTester($command);
        $tester->execute([]);
        $id = 11;
        $model = new TestModelClass();
        $model->setId($id);
        $model->value_1 = 1;
        $model->value_2 = "2";
        $this->assertTrue($model->isNewModel());
        $model->save();
        TestModelClass::freeUpMemory();
        $model = TestModelClass::findById($id);
        $this->assertFalse($model->isNewModel());
    }

    public function testIsNewModelTestPluginModelClass()
    {
        $command = new CreateTablesCommand();
        $tester = new CommandTester($command);
        $tester->execute([]);
        $id = 11;
        $model = new TestPluginModelClass();
        $model->setId($id);
        $model->value_1 = 1;
        $model->value_2 = "2";
        $this->assertTrue($model->isNewModel());
        $model->save();
        TestPluginModelClass::freeUpMemory();
        $model = TestPluginModelClass::findById($id);
        $this->assertFalse($model->isNewModel());
    }

    public function testIsNewModelTestSinglePluginModelClass()
    {
        $command = new CreateTablesCommand();
        $tester = new CommandTester($command);
        $tester->execute([]);
        $model = new TestSinglePluginModelClass();
        $model->value_1 = 1;
        $model->value_2 = "2";
        $this->assertTrue($model->isNewModel());
        $model->save();
        TestSinglePluginModelClass::freeUpMemory();
        $model = TestSinglePluginModelClass::find();
        $this->assertFalse($model->isNewModel());
    }
}