<?php
/**
 * Created for plugin-component-db
 * Date: 18.11.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace SalesRender\Plugin\Components\Db\Exceptions;


use Exception;
use Medoo\Medoo;

class DatabaseException extends Exception
{

    public function __construct(Medoo $db)
    {
        $error = $db->error();
        $query = $db->last();
        parent::__construct("{$error[0]}: {$error[2]}\n{$query}", $error[1]);
    }

    /**
     * @param Medoo $db
     * @throws DatabaseException
     */
    public static function guard(Medoo $db): void
    {
        $error = $db->error();
        if (!is_null($error[1]) || !is_null($error[2])) {
            throw new self($db);
        }
    }

}