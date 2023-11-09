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
use Symfony\Component\Console\Tester\CommandTester;

class CreateTablesCommandTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        Connector::config(
            new Medoo([
                'database_type' => 'sqlite',
                'database_file' => ':memory:'
            ])
        );
    }

    public function testExecute()
    {
        $db = Connector::db();
        $command = new CreateTablesCommand();
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertSame(["00000", null, null], $db->error());
    }

}
