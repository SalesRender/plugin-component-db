<?php


namespace SalesRender\Plugin\Components\Db\ModelTest;


use InvalidArgumentException;
use Medoo\Medoo;
use PHPUnit\Framework\TestCase;
use SalesRender\Plugin\Components\Db\Commands\CreateTablesCommand;
use SalesRender\Plugin\Components\Db\Components\Connector;
use SalesRender\Plugin\Components\Db\Components\PluginReference;
use SalesRender\Plugin\Components\Db\Components\TestAnotherPluginModelClass;
use SalesRender\Plugin\Components\Db\Components\TestAnotherSinglePluginModelClass;
use SalesRender\Plugin\Components\Db\Components\TestModelClass;
use SalesRender\Plugin\Components\Db\Components\TestModelWithAfterAndBeforeClass;
use SalesRender\Plugin\Components\Db\Components\TestModelWithArrayClass;
use SalesRender\Plugin\Components\Db\Components\TestModelWithSubclass;
use SalesRender\Plugin\Components\Db\Components\TestPluginModelClass;
use SalesRender\Plugin\Components\Db\Components\TestSinglePluginModelClass;
use SalesRender\Plugin\Components\Db\Components\TestSubclass;
use SalesRender\Plugin\Components\Db\Exceptions\DatabaseException;
use Symfony\Component\Console\Tester\CommandTester;

class addModelsTest extends TestCase
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

        $command = new CreateTablesCommand();
        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    public static function tearDownAfterClass(): void
    {
        $filename = 'OnSaveHandler';
        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    public function testAddTestModelClassAndFindById()
    {
        $model = new TestModelClass();
        $model->setId(11);
        $model->value_1 = 11;
        $model->value_2 = 'Hello world 11';
        $model->save();

        TestModelClass::freeUpMemory();
        $result = TestModelClass::findById( 11);

        $this->assertInstanceOf('SalesRender\Plugin\Components\Db\Components\TestModelClass', $result);
        $this->assertEquals(11, $result->getId());
        $this->assertEquals(11, $result->value_1);
        $this->assertEquals('Hello world 11', $result->value_2);
    }

    public function testAddTestModelClassWithAddOnSaveHandlerAndFindById()
    {
        $model = new TestModelClass();
        $model->setId(11);
        $model->value_1 = 11;
        $model->value_2 = 'Hello world 11';
        $value = 11;
        $model->value_1 = $value;
        TestModelClass::addOnSaveHandler(function () use (&$value) {
            $value++;
        });
        $model->save();
        $model->value_1 = $value;
        $model->save();
        TestModelClass::freeUpMemory();
        $result = TestModelClass::findById( 11);
        $this->assertInstanceOf('SalesRender\Plugin\Components\Db\Components\TestModelClass', $result);
        $this->assertEquals(11, $result->getId());
        $this->assertEquals(12, $result->value_1);
        $this->assertEquals('Hello world 11', $result->value_2);
        $this->assertEquals(13, $value);
    }

    public function testAddTestModelClassWithAddOnSaveHandlerOnAnotherModelAndFindById()
    {
        $model = new TestModelClass();
        $model->setId(11);
        $model->value_1 = 11;
        $model->value_2 = 'Hello world 11';
        $value = 11;
        $model->value_1 = $value;
        TestAnotherPluginModelClass::addOnSaveHandler(function () use (&$value) {
            $value = 12;
        });
        $model->save();
        TestModelClass::freeUpMemory();
        $result = TestModelClass::findById( 11);
        $this->assertInstanceOf('SalesRender\Plugin\Components\Db\Components\TestModelClass', $result);
        $this->assertEquals(11, $result->getId());
        $this->assertEquals(11, $result->value_1);
        $this->assertEquals('Hello world 11', $result->value_2);
        $this->assertEquals(11, $value);
    }

    public function testAddTestModelClassWithRemoveOnSaveHandlerAndFindById()
    {
        $model = new TestModelClass();
        $model->setId(11);
        $model->value_1 = 11;
        $model->value_2 = 'Hello world 11';
        $value = 11;
        $model->value_1 = $value;
        TestModelClass::addOnSaveHandler(
            function () use (&$value) {
                $value++;
            },
            'changeValue'
        );
        $model->save();
        $model->value_1 = $value;
        TestModelClass::removeOnSaveHandler('changeValue');
        $model->save();
        TestModelClass::freeUpMemory();
        $result = TestModelClass::findById( 11);
        $this->assertInstanceOf('SalesRender\Plugin\Components\Db\Components\TestModelClass', $result);
        $this->assertEquals(11, $result->getId());
        $this->assertEquals(12, $result->value_1);
        $this->assertEquals('Hello world 11', $result->value_2);
        $this->assertEquals(12, $value);
    }

    public function testAddTestPluginModelClassAndFindById()
    {
        $model = new TestPluginModelClass();
        $model->setId(11);
        $model->value_1 = 11;
        $model->value_2 = 'Hello world 11';
        $model->save();

        TestPluginModelClass::freeUpMemory();
        $result = TestPluginModelClass::findById( 11);

        $this->assertInstanceOf('SalesRender\Plugin\Components\Db\Components\TestPluginModelClass', $result);
        $this->assertEquals(11, $result->getId());
        $this->assertEquals(11, $result->value_1);
        $this->assertEquals('Hello world 11', $result->value_2);
    }

    public function testAddTestSinglePluginModelClassAndFind()
    {
        $model = new TestSinglePluginModelClass();
        $model->value_1 = 11;
        $model->value_2 = 'Hello world 11';
        $model->save();

        TestSinglePluginModelClass::freeUpMemory();
        $result = TestSinglePluginModelClass::find();

        $this->assertInstanceOf('SalesRender\Plugin\Components\Db\Components\TestSinglePluginModelClass', $result);
        $this->assertEquals(2, $result->getId());
        $this->assertEquals(11, $result->value_1);
        $this->assertEquals('Hello world 11', $result->value_2);
    }

    public function testAddTestModelWithArrayClass()
    {
        $model = new TestModelWithArrayClass();
        $model->setId(11);
        $model->value_1 = 11;
        $model->value_2 = 'hello';

        $result = '';
        TestModelWithArrayClass::freeUpMemory();
        try {
            $model->save();
        } catch (InvalidArgumentException $e) {
            $result = $e->getMessage();
        }

        $expected = "Field 'value_2' of 'SalesRender\Plugin\Components\Db\Components\TestModelWithArrayClass' should be scalar or null";
        $this->assertEquals($expected, $result);
    }

    public function testAddTestModelClassGetId()
    {
        $id = 11;
        $model = new TestModelClass();
        $model->setId($id);
        $this->assertSame('11', $model->getId());
    }

    public function testAddTestModelWithAfterAndBeforeClass()
    {
        $id = 11;
        $model = new TestModelWithAfterAndBeforeClass();
        $model->setId($id);
        $model->value_1 = 11;
        $model->value_2 = 'Hello world 11';
        $model->save();
        $this->assertEquals('Start save', TestModelWithAfterAndBeforeClass::$message);
        TestModelWithAfterAndBeforeClass::freeUpMemory();
        $result = TestModelWithAfterAndBeforeClass::findById($id);
        $this->assertEquals('Find complete', TestModelWithAfterAndBeforeClass::$message);
        $this->assertInstanceOf('SalesRender\Plugin\Components\Db\Components\TestModelWithAfterAndBeforeClass', $result);
        $this->assertEquals(11, $result->getId());
        $this->assertEquals(11, $result->value_1);
        $this->assertEquals('Hello world 11', $result->value_2);
    }

    public function testAddTestModelWithSubclass()
    {
        $id = 11;
        $subclass = new TestSubclass(10, 20);
        $model = new TestModelWithSubclass();
        $model->setId($id);
        $model->value_1 = 11;
        $model->value_2 = 'Hello world 11';
        $model->value_3 = $subclass;
        $model->save();
        $this->assertEquals('Start save', TestModelWithSubclass::$message);
        TestModelWithSubclass::freeUpMemory();
        $result = TestModelWithSubclass::findById($id);
        $this->assertEquals('Find complete', TestModelWithSubclass::$message);
        $this->assertInstanceOf('SalesRender\Plugin\Components\Db\Components\TestModelWithSubclass', $result);
        $this->assertEquals(11, $result->getId());
        $this->assertEquals(11, $result->value_1);
        $this->assertEquals('Hello world 11', $result->value_2);
        $this->assertEquals($subclass, $result->value_3);
    }

    public function testAddTestModelClassWithNotUniqueId()
    {
        $model = new TestModelClass();
        $model->setId(22);
        $model->value_1 = 22;
        $model->value_2 = 'Hello world 22';
        $model->save();

        TestModelClass::freeUpMemory();
        $newModel = new TestModelClass();
        $newModel->setId(22);
        $newModel->value_1 = 1;
        $newModel->value_2 = 'Hello world 1';

        $result = '';
        try {
            $newModel->save();
        } catch (DatabaseException $e) {
            $result = $e->getMessage();
        }
        $expected = str_replace("\r", '', '23000: UNIQUE constraint failed: TestModelClass.id
INSERT INTO "TestModelClass" ("value_1", "value_2", "id") VALUES (1, \'Hello world 1\', \'22\')');
        $this->assertSame($expected, $result);
    }

    public function testAddTestPluginModelClassWithNotUniqueId()
    {
        $model = new TestPluginModelClass();
        $model->setId(22);
        $model->value_1 = 22;
        $model->value_2 = 'Hello world 22';
        $model->save();

        TestPluginModelClass::freeUpMemory();
        $newModel = new TestPluginModelClass();
        $newModel->setId(22);
        $newModel->value_1 = 1;
        $newModel->value_2 = 'Hello world 1';

        $result = '';
        try {
            $newModel->save();
        } catch (DatabaseException $e) {
            $result = $e->getMessage();
        }
        $expected = '23000: UNIQUE constraint failed: TestPluginModelClass.companyId, TestPluginModelClass.pluginAlias, TestPluginModelClass.pluginId, TestPluginModelClass.id
INSERT INTO "TestPluginModelClass" ("value_1", "value_2", "id", "companyId", "pluginAlias", "pluginId") VALUES (1, \'Hello world 1\', \'22\', \'1\', \'user\', \'2\')';
        $this->assertSame(str_replace("\r", '', $expected), $result);
    }

    public function testAddTestSinglePluginModelClassWithNotUniqueId()
    {
        $model = new TestSinglePluginModelClass();
        $model->value_1 = 6;
        $model->value_2 = 'Hello world 7';
        $model->save();

        TestPluginModelClass::freeUpMemory();
        $newModel = new TestSinglePluginModelClass();
        $newModel->value_1 = 1;
        $newModel->value_2 = 'Hello world 1';

        $result = '';
        try {
            $newModel->save();
        } catch (DatabaseException $e) {
            $result = $e->getMessage();
        }
        $expected = '23000: UNIQUE constraint failed: TestSinglePluginModelClass.companyId, TestSinglePluginModelClass.pluginAlias, TestSinglePluginModelClass.pluginId, TestSinglePluginModelClass.id
INSERT INTO "TestSinglePluginModelClass" ("value_1", "value_2", "id", "companyId", "pluginAlias", "pluginId") VALUES (1, \'Hello world 1\', \'2\', \'1\', \'user\', \'2\')';
        $this->assertSame(str_replace("\r", '', $expected), $result);
    }

    public function testAddTwoModelAndFind()
    {
        $model_1 = new TestModelClass();
        $model_1->setId(1);
        $model_1->value_1 = 1;
        $model_1->value_2 = '1';
        $model_1->save();

        $model_2 = new TestModelWithAfterAndBeforeClass();
        $model_2->setId(1);
        $model_2->value_1 = 2;
        $model_2->value_2 = '2';
        $model_2->save();

        TestModelClass::freeUpMemory();

        $findModel_1 = TestModelClass::findById(1);
        $findModel_2 = TestModelWithAfterAndBeforeClass::findById(1);

        $this->assertInstanceOf('SalesRender\Plugin\Components\Db\Components\TestModelClass', $findModel_1);
        $this->assertEquals(1, $findModel_1->getId());
        $this->assertEquals(1, $findModel_1->value_1);
        $this->assertEquals('1', $findModel_1->value_2);

        $this->assertInstanceOf('SalesRender\Plugin\Components\Db\Components\TestModelWithAfterAndBeforeClass', $findModel_2);
        $this->assertEquals(1, $findModel_2->getId());
        $this->assertEquals(2, $findModel_2->value_1);
        $this->assertEquals('2', $findModel_2->value_2);
    }

    public function testAddTwoPluginModelAndFind()
    {
        $model_1 = new TestPluginModelClass();
        $model_1->setId(1);
        $model_1->value_1 = 1;
        $model_1->value_2 = '1';
        $model_1->save();

        $model_2 = new TestAnotherPluginModelClass();
        $model_2->setId(1);
        $model_2->value_1 = 2;
        $model_2->value_2 = '2';
        $model_2->save();

        TestModelClass::freeUpMemory();

        $findModel_1 = TestPluginModelClass::findById(1);
        $findModel_2 = TestAnotherPluginModelClass::findById(1);

        $this->assertInstanceOf('SalesRender\Plugin\Components\Db\Components\TestPluginModelClass', $findModel_1);
        $this->assertEquals(1, $findModel_1->getId());
        $this->assertEquals(1, $findModel_1->value_1);
        $this->assertEquals('1', $findModel_1->value_2);

        $this->assertInstanceOf('SalesRender\Plugin\Components\Db\Components\TestAnotherPluginModelClass', $findModel_2);
        $this->assertEquals(1, $findModel_2->getId());
        $this->assertEquals(2, $findModel_2->value_1);
        $this->assertEquals('2', $findModel_2->value_2);
    }

    public function testAddTwoSinglePluginModelAndFind()
    {
        $model_1 = new TestSinglePluginModelClass();
        $model_1->value_1 = 1;
        $model_1->value_2 = '1';
        $model_1->save();

        $model_2 = new TestAnotherSinglePluginModelClass();
        $model_2->value_1 = 2;
        $model_2->value_2 = '2';
        $model_2->save();

        TestModelClass::freeUpMemory();

        $findModel_1 = TestSinglePluginModelClass::find();
        $findModel_2 = TestAnotherSinglePluginModelClass::find();

        $this->assertInstanceOf('SalesRender\Plugin\Components\Db\Components\TestSinglePluginModelClass', $findModel_1);
        $this->assertEquals(2, $findModel_1->getId());
        $this->assertEquals(1, $findModel_1->value_1);
        $this->assertEquals('1', $findModel_1->value_2);

        $this->assertInstanceOf('SalesRender\Plugin\Components\Db\Components\TestAnotherSinglePluginModelClass', $findModel_2);
        $this->assertEquals(2, $findModel_2->getId());
        $this->assertEquals(2, $findModel_2->value_1);
        $this->assertEquals('2', $findModel_2->value_2);
    }

}