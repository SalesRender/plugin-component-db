<?php
/**
 * Created for plugin-component-db
 * Date: 17.11.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace SalesRender\Plugin\Components\Db\Components;


use SalesRender\Plugin\Components\Db\Model;
use SalesRender\Plugin\Components\Db\SinglePluginModelInterface;

class TestAnotherSinglePluginModelClass extends Model implements SinglePluginModelInterface
{
    public int $value_1;

    public string $value_2;

    public static function schema(): array
    {
        return [
            'value_1' => ['INT'],
            'value_2' => ['VARCHAR(255)'],
        ];
    }
}