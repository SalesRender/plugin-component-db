<?php
/**
 * Created for plugin-component-db
 * Date: 17.11.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace SalesRender\Plugin\Components\Db;


interface SinglePluginModelInterface extends PluginModelInterface
{

    public static function find(): ?Model;

}