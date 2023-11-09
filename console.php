<?php
/**
 * Created for plugin-component-db
 * Date: 17.11.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

require_once 'vendor/autoload.php';

use Medoo\Medoo;
use SalesRender\Plugin\Components\Db\Commands\CreateTablesCommand;
use SalesRender\Plugin\Components\Db\Components\Connector;
use Symfony\Component\Console\Application;

Connector::config(new Medoo([
    'database_type' => 'sqlite',
    'database_file' => __DIR__ . '/testDB.db'
]));

$application = new Application();
$application->add(new CreateTablesCommand());
$application->run();