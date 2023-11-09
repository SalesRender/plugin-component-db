<?php


namespace SalesRender\Plugin\Components\Db\ComponentsTest;


use Medoo\Medoo;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SalesRender\Plugin\Components\Db\Components\Connector;
use SalesRender\Plugin\Components\Db\Components\PluginReference;

class ConnectorTest extends TestCase
{
    public function testGetDb()
    {
        $medoo = new Medoo([
            'database_type' => 'sqlite',
            'database_file' => ':memory:'
        ]);
        Connector::config($medoo);

        $pluginReference = new PluginReference(1, 'user', 2);
        Connector::setReference($pluginReference);
        $this->assertSame($medoo, Connector::db());
    }

    public function testConnectorSetReference()
    {
        Connector::config(
            new Medoo([
                'database_type' => 'sqlite',
                'database_file' => ':memory:'
            ])
        );

        $pluginReference = new PluginReference(1, 'user', 2);
        Connector::setReference($pluginReference);

        $this->assertSame($pluginReference, Connector::getReference());
    }

    public function testConnectorReferenceNotConfigured()
    {
        Connector::config(
            new Medoo([
                'database_type' => 'sqlite',
                'database_file' => ':memory:'
            ])
        );
        try {
            $actual = Connector::getReference();
        } catch (RuntimeException $e) {
            $actual = $e->getMessage();
        }

        $this->assertSame('Plugin reference is not configured', $actual);
    }

    public function testConnectorMedooNotConfigured()
    {
        try {
            $actual = Connector::db();
        } catch (RuntimeException $e) {
            $actual = $e->getMessage();
        }

        $this->assertSame('Medoo was not configured', $actual);
    }

    public function testGetReference()
    {
        Connector::config(
            new Medoo([
                'database_type' => 'sqlite',
                'database_file' => ':memory:'
            ])
        );
        $pluginReference = new PluginReference(1, 'user', 2);
        Connector::setReference($pluginReference);

        $this->assertEquals('user', $pluginReference->getAlias());
        $this->assertEquals(1, $pluginReference->getCompanyId());
        $this->assertEquals(2, $pluginReference->getId());
        $this->assertEquals($pluginReference, new PluginReference(1, 'user', 2));
    }

    public function testConnectorHasReference()
    {
        Connector::config(
            new Medoo([
                'database_type' => 'sqlite',
                'database_file' => ':memory:'
            ])
        );
        $actual = Connector::hasReference();

        $this->assertSame(false, $actual);

        $pluginReference = new PluginReference(1, 'user', 2);
        Connector::setReference($pluginReference);

        $actual = Connector::hasReference();

        $this->assertSame(true, $actual);
    }
}