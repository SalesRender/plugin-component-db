<?php


namespace SalesRender\Plugin\Components\Db\ModelTest;


use Medoo\Medoo;
use PHPUnit\Framework\TestCase;
use SalesRender\Plugin\Components\Db\Components\Connector;
use SalesRender\Plugin\Components\Db\Components\PluginReference;
use SalesRender\Plugin\Components\Db\Components\TestModelClass;

class findTestModelClassTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        Connector::config(new Medoo([
            'database_type' => 'sqlite',
            'database_file' => __DIR__ . '/../../testDB.db'
        ]));

        Connector::setReference(new PluginReference(1, 'user', 2));
    }

    public function testFindByCondition()
    {
        $results = TestModelClass::findByCondition(['value_1' => '11']);
        $this->assertArrayHasKey('1', $results);
        $this->assertCount(1, $results);
        foreach ($results as $key => $result) {
            $this->assertInstanceOf('Leadvertex\Plugin\Components\Db\Components\TestModelClass', $result);
            $this->assertEquals(1, $result->getId());
            $this->assertEquals(11, $result->value_1);
            $this->assertEquals('Hello world 1', $result->value_2);
        }
    }

    public function testFindByConditionValue2()
    {
        $results = TestModelClass::findByCondition(['value_2' => 'Hello world']);
        $this->assertCount(2, $results);
        foreach ($results as $key => $result)
        {
            $this->assertArrayHasKey($key, $results);
            $this->assertInstanceOf('Leadvertex\Plugin\Components\Db\Components\TestModelClass', $result);
            $this->assertEquals($key, $result->getId());
            $this->assertEquals($key.$key, $result->value_1);
            $this->assertEquals('Hello world', $result->value_2);
        }
    }

    public function testFindByConditionNotFound()
    {
        $this->assertEmpty(TestModelClass::findByCondition(['value_1' => '111']));
    }

    public function testFindByConditionTwoFilter()
    {
        $this->assertEmpty(TestModelClass::findByCondition(['value_1' => '11', 'value_2' => 'Hello world']));
    }

    public function testFindByConditionAllModel()
    {
        $results = TestModelClass::findByCondition([]);
        $this->assertCount(3, $results);
        foreach ($results as $key => $result)
        {
            $this->assertArrayHasKey($key, $results);
            $this->assertInstanceOf('Leadvertex\Plugin\Components\Db\Components\TestModelClass', $result);
            $this->assertEquals($key, $result->getId());
            $this->assertEquals($key.$key, $result->value_1);
            if ($key === 1) {
                $this->assertEquals('Hello world 1', $result->value_2);
            } else {
                $this->assertEquals('Hello world', $result->value_2);
            }
        }
    }

    public function testFindByConditionNull()
    {
        $result = var_export(TestModelClass::findByCondition([null]), true);
        $expected = "array (
)";
        $this->assertSame(str_replace("\r", '', $expected), $result);
    }

    public function testFindByIds()
    {
        $results = TestModelClass::findByIds([2, 3]);
        $this->assertCount(2, $results);
        foreach ($results as $key => $result)
        {
            $this->assertArrayHasKey($key, $results);
            $this->assertInstanceOf('Leadvertex\Plugin\Components\Db\Components\TestModelClass', $result);
            $this->assertEquals($key, $result->getId());
            $this->assertEquals($key.$key, $result->value_1);
            $this->assertEquals('Hello world', $result->value_2);
        }
    }

    public function testFindByIdsWithNotExistId()
    {
        $results = TestModelClass::findByIds([2, 3, 11]);
        $this->assertCount(2, $results);
        foreach ($results as $key => $result)
        {
            $this->assertArrayHasKey($key, $results);
            $this->assertInstanceOf('Leadvertex\Plugin\Components\Db\Components\TestModelClass', $result);
            $this->assertEquals($key, $result->getId());
            $this->assertEquals($key.$key, $result->value_1);
            $this->assertEquals('Hello world', $result->value_2);
        }
    }

    public function testFindByIdsNotFound()
    {
        $this->assertEmpty(TestModelClass::findByIds([11]));
    }

    public function testFindByIdsEmpty()
    {
        $this->assertEmpty(TestModelClass::findByIds([]));
    }

    public function testFindByIdsNull()
    {
        $this->assertEmpty(TestModelClass::findByIds([null]));
    }

    public function testFindById()
    {
        $result = TestModelClass::findById( 1);
        $this->assertInstanceOf('Leadvertex\Plugin\Components\Db\Components\TestModelClass', $result);
        $this->assertEquals(1, $result->getId());
        $this->assertEquals(11, $result->value_1);
        $this->assertEquals('Hello world 1', $result->value_2);
    }

    public function testFindByIdNotFound()
    {
        $result = TestModelClass::findById( 11);
        $this->assertNull($result);
    }

}