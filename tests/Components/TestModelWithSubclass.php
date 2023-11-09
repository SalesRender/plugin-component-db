<?php

namespace SalesRender\Plugin\Components\Db\Components;


use SalesRender\Plugin\Components\Db\Model;

class TestModelWithSubclass extends Model
{
    public int $value_1;

    public string $value_2;

    public TestSubclass $value_3;

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
            'sub_1' => ['INT'],
            'sub_2' => ['INT'],
        ];
    }

    protected static function beforeWrite(array $data): array
    {
        /** @var TestSubclass $value */
        $value = $data['value_3'];
        $data['sub_1'] = $value->value_1;
        $data['sub_2'] = $value->value_2;
        unset($data['value_3']);
        return $data;
    }

    protected static function afterRead(array $data): array
    {
        $data['value_3'] = new TestSubclass($data['sub_1'], $data['sub_2']);
        unset($data['sub_1']);
        unset($data['sub_2']);
        return $data;
    }

    protected static function getSerializeFields(): array
    {
        $fields = parent::getSerializeFields();
        $fields[] = 'value_3';
        return array_filter($fields, function ($value) {
            return !in_array($value, ['sub_1', 'sub_2']);
        });
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