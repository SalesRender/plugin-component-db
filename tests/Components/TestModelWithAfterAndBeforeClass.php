<?php

namespace SalesRender\Plugin\Components\Db\Components;


use SalesRender\Plugin\Components\Db\Model;

class TestModelWithAfterAndBeforeClass extends Model
{
    public int $value_1;

    public string $value_2;

    public static string $message = '';

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

    protected static function beforeWrite(array $data): array
    {
        $data['value_2'] = serialize([$data['value_2'], 'new string']);
        return $data;
    }

    protected static function afterRead(array $data): array
    {
        $data['value_2'] = unserialize($data['value_2'])[0];
        return $data;
    }

    protected function beforeSave(bool $isNew): void
    {
        self::$message = 'Start save';
    }

    protected function afterFind(): void
    {
        self::$message = 'Find complete';
    }

}