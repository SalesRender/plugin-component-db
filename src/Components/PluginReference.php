<?php
/**
 * Created for plugin-component-db
 * Date: 17.11.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace SalesRender\Plugin\Components\Db\Components;


class PluginReference
{

    private string $companyId;

    private string $alias;

    private string $id;

    public function __construct(string $companyId, string $alias, string $id)
    {
        $this->companyId = $companyId;
        $this->alias = $alias;
        $this->id = $id;
    }

    public function getCompanyId(): string
    {
        return $this->companyId;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getId(): string
    {
        return $this->id;
    }

}