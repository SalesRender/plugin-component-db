<?php
/**
 * Created for LeadVertex
 * Date: 10/14/21 8:05 PM
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace SalesRender\Plugin\Components\Db\Components;

class TestSubclass
{

    public int $value_1;
    public int $value_2;

    public function __construct(int $value_1, int $value_2)
    {
        $this->value_1 = $value_1;
        $this->value_2 = $value_2;
    }

}