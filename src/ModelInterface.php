<?php
/**
 * Created for plugin-component-db
 * Date: 16.11.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace SalesRender\Plugin\Components\Db;


use Medoo\Medoo;

interface ModelInterface
{

    public function save(): void;

    public function delete(): void;

    public function isNewModel(): bool;

    public static function findById(string $id): ?self;

    public static function findByIds(array $ids): array;

    public static function findByCondition(array $where): array;

    public static function tableName(): string;

    /**
     * @link https://medoo.in/api/create
     * @return array[]
     */
    public static function schema(): array;

    /**
     * @link https://medoo.in/api/pdo
     * @param Medoo $db
     * @return void
     */
    public static function afterTableCreate(Medoo $db): void;

}