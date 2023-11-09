<?php


namespace SalesRender\Plugin\Components\Db\ExceptionsTest;


use BadMethodCallException;
use Medoo\Medoo;
use PHPUnit\Framework\TestCase;
use SalesRender\Plugin\Components\Db\Commands\CreateTablesCommand;
use SalesRender\Plugin\Components\Db\Components\Connector;
use SalesRender\Plugin\Components\Db\Components\PluginReference;
use SalesRender\Plugin\Components\Db\Components\TestModelClass;
use SalesRender\Plugin\Components\Db\Exceptions\DatabaseException;
use Symfony\Component\Console\Tester\CommandTester;

class ExceptionTest extends TestCase
{

    public function setUp(): void
    {
        Connector::config(
            new Medoo([
                'database_type' => 'sqlite',
                'database_file' => ':memory:'
            ])
        );

        Connector::setReference(new PluginReference(1, 'user', 2));
    }

    public function testExceptionNotUniqueId()
    {
        $command = new CreateTablesCommand();
        $tester = new CommandTester($command);
        $tester->execute([]);
        $model = new TestModelClass();
        $model->setId(11);
        $model->value_1 = 11;
        $model->value_2 = 'Hello world 11';
        $model->save();
        $newModel = new TestModelClass();
        $newModel->setId(11);
        $newModel->value_1 = 1;
        $newModel->value_2 = 'Hello world 1';
        $result = '';
        try {
            $newModel->save();
        } catch (DatabaseException $e) {
            $result = $e->getMessage();
        }
        $expected = '23000: UNIQUE constraint failed: TestModelClass.id
INSERT INTO "TestModelClass" ("value_1", "value_2", "id") VALUES (1, \'Hello world 1\', \'11\')';
        $this->assertSame(str_replace("\r", '', $expected), $result);
    }

    public function testExceptionNoTable()
    {
        $model = new TestModelClass();
        $model->setId(11);
        $model->value_1 = 11;
        $model->value_2 = 'Hello world 11';
        $result = '';
        try {
            $model->save();
        } catch (DatabaseException $e) {
            $result = $e->getMessage();
        }
        $expected = 'HY000: no such table: TestModelClass
INSERT INTO "TestModelClass" ("value_1", "value_2", "id") VALUES (11, \'Hello world 11\', \'11\')';
        $this->assertSame(str_replace("\r", '', $expected), $result);
    }

    public function testExceptionTableIncorrectScheme()
    {
        Connector::db()->create('TestModelClass', ['value_1' => ['INT']]);

        $model = new TestModelClass();
        $model->setId(11);
        $model->value_1 = 11;
        $model->value_2 = 'Hello world 11';

        $result = '';
        TestModelClass::freeUpMemory();
        try {
            $model->save();
        } catch (DatabaseException $e) {
            $result = $e->getMessage();
        }
        $expected = 'HY000: table TestModelClass has no column named value_2
INSERT INTO "TestModelClass" ("value_1", "value_2", "id") VALUES (11, \'Hello world 11\', \'11\')';
        $this->assertSame(str_replace("\r", '', $expected), $result);
    }

    public function testExceptionFindWorkOnlyWithSingle()
    {
        $command = new CreateTablesCommand();
        $tester = new CommandTester($command);
        $tester->execute([]);
        $model = new TestModelClass();
        $model->setId(11);
        $model->value_1 = 11;
        $model->value_2 = 'Hello world 11';
        $model->save();
        try {
            $result = TestModelClass::find();
        } catch (BadMethodCallException $e) {
            $result = $e->getMessage();
        }
        $expected = 'Model::find() can work only with interface Leadvertex\Plugin\Components\Db\SinglePluginModelInterface';
        $this->assertSame(str_replace("\r", '', $expected), $result);
    }
}