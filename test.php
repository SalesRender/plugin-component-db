<?php
/**
 * Created for plugin-component-db
 * Date: 17.11.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

require_once 'vendor/autoload.php';

use Medoo\Medoo;
use SalesRender\Plugin\Components\Db\Components\Connector;
use SalesRender\Plugin\Components\Db\Components\PluginReference;
use SalesRender\Plugin\Components\Db\Components\TestModelClass;
use SalesRender\Plugin\Components\Db\Components\TestPluginModelClass;
use SalesRender\Plugin\Components\Db\Components\TestSinglePluginModelClass;

Connector::config(new Medoo([
    'database_type' => 'sqlite',
    'database_file' => __DIR__ . '/testDB.db'
]));

Connector::setReference(new PluginReference(1, 'user', 3));

$model = new TestModelClass();
$model->setId(4);
$model->value_1 = 4;
$model->value_2 = 'Hello world 4';
$model->save();

$model2 = TestModelClass::findById(4);
$model2->value_1 = 44;
$model2->value_2 = 'Hello world 44';
$model2->setId(5);
$model2->save();
var_dump(TestModelClass::findByCondition(['value_1' => 11]));
var_dump(TestModelClass::findByCondition(['value_2' => 'Hello world 44']));
var_dump(TestModelClass::findById(4));
var_dump(TestModelClass::findByIds([3]));

$model = new TestPluginModelClass();
$model->setId(2);
$model->value_1 = 102;
$model->value_2 = 'He2llo world';
$model->save();

var_dump(TestPluginModelClass::findByCondition(['value_2' => 'Hello world']));
var_dump(TestPluginModelClass::findById(2));
var_dump(TestPluginModelClass::findByIds([2, 1]));

$model = new TestSinglePluginModelClass();
$model->value_1 = 10;
$model->value_2 = 'Hello world';
$model->save();

var_dump(TestSinglePluginModelClass::findByCondition(['value_2' => 'Hello world']));
var_dump(TestSinglePluginModelClass::findById(1));
var_dump(TestSinglePluginModelClass::findByIds([2, 1]));
var_dump(TestSinglePluginModelClass::find());
