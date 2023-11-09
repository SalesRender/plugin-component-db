<?php
/**
 * Created for plugin-component-db
 * Datetime: 07.02.2020 17:19
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace SalesRender\Plugin\Components\Db\Commands;

use Medoo\Medoo;
use PHPUnit\Framework\TestCase;
use SalesRender\Plugin\Components\Db\Components\Connector;
use SalesRender\Plugin\Components\Db\Components\PluginReference;
use SalesRender\Plugin\Components\Db\Components\TestModelClass;
use Symfony\Component\Console\Tester\CommandTester;

class TableCleanerCommandTest extends TestCase
{

    public function testExecute()
    {
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
        $model = new TestModelClass();
        $model->setId(11);
        $model->value_1 = 11;
        $model->value_2 = 'Hello world 11';
        $model->save();
        $command = new TableCleanerCommand();
        $tester = new CommandTester($command);
        $tester->execute(['table' => 'TestModelClass', 'by' => 'id', 'hours' => 1]);
        $result = var_export(TestModelClass::findByCondition(['value_2' => 'Hello world 11']), true);
        $expected = 'array (
)';
        $this->assertSame(str_replace("\r", '', $expected), $result);
    }

}
