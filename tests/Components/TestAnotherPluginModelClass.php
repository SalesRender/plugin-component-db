<?php
/**
 * Created for plugin-component-db
 * Date: 17.11.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace SalesRender\Plugin\Components\Db\Components;


use SalesRender\Plugin\Components\Db\Model;
use SalesRender\Plugin\Components\Db\PluginModelInterface;

class TestAnotherPluginModelClass extends Model implements PluginModelInterface
{
    public int $value_1;

    public string $value_2;

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public static function schema(): array
    {
        return [
            'value_1' => ['INT'],
            'value_2' => ['VARCHAR(255)'],
        ];
    }
}