<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

/**
 * Builder
 */
abstract class Builder
{
    /*
     * 支持的SQL运算集
     */
    public static $opertor_set = [
        /* ==,!= */
        'EQ'               => 'EQ',
        '='                => 'EQ',
        '=='               => 'EQ',
        'NEQ'              => 'NEQ',
        '<>'               => 'NEQ',
        '!='               => 'NEQ',
        /* <,>,<=,>= */
        'GT'               => 'GT',
        '>'                => 'GT',
        'EGT'              => 'EGT',
        '>='               => 'EGT',
        'LT'               => 'LT',
        '<'                => 'LT',
        'ELT'              => 'ELT',
        '<='               => 'ELT',
        /* LIKE */
        'LIKE'             => 'LIKE',
        'NOT LIKE'         => 'NOTLIKE',
        'NOTLIKE'          => 'NOTLIKE',
        /* IN */
        'IN'               => 'IN',
        'NOT IN'           => 'NOTIN',
        'NOTIN'            => 'NOTIN',
        /* BETWEEN */
        'BETWEEN'          => 'BETWEEN',
        'NOT BETWEEN'      => 'NOTBETWEEN',
        'NOTBETWEEN'       => 'NOTBETWEEN',
        /* EXISTS */
        'EXISTS'           => 'EXISTS',
        'NOT EXISTS'       => 'NOTEXISTS',
        'NOTEXISTS'        => 'NOTEXISTS',
        /* NULL */
        'NULL'             => 'NULL',
        'NOT NULL'         => 'NOTNULL',
        'NOTNULL'          => 'NOTNULL',
        /* RAW */
        'RAW'              => 'RAW',
        /* 时间类型字段的运算 */
        'TIME >'           => 'TIME_GT',
        'TIME <'           => 'TIME_LT',
        'TIME >='          => 'TIME_EGT',
        'TIME <='          => 'TIME_ELT',
        'TIME BETWEEN'     => 'TIME_BETWEEN',
        'TIME NOT BETWEEN' => 'TIME_NOTBETWEEN',
    ];
    /**
     * @var \Dida\Db
     */
    protected $db = null;
    protected $table = null;
    protected $def = null;


    /**
     * @param \Dida\Db $db
     * @param string $table
     */
    public function __construct($db, $table)
    {
        $this->db = $db;
        $this->table = $table;
        $this->def = include($db->workdir . '~SCHEMA' . DIRECTORY_SEPARATOR . $table . '.php');
    }


    protected function quoteTable($table)
    {
        return '"' . $table . '"';
    }


    protected function quoteField($field)
    {
        return '"' . $field . '"';
    }


    protected function quoteString($value, $charlist = "\"\'\\")
    {
        /*
         * Returns FALSE if the PDO driver does not support the quote() method (notably PDO_ODBC).
         */
        $result = $this->db->pdo->quote($value);

        /*
         * Replaces with the addcslashes() function.
         */
        if ($result === false) {
            return '"' . addcslashes($value, $charlist) . '"';
        } else {
            return $result;
        }
    }


    protected function quoteTime($value)
    {
        return '"' . $value . '"';
    }


    protected function op_COMMON($field, $op, $data)
    {
        $expression = '';

        $tpl = '(%FIELD% %OP% %VALUE%)';

        switch ($this->def['COLUMNS'][$field]['BASE_TYPE']) {
            case 'string':
                $array = [
                    '%FIELD%' => $field,
                    '%OP%'    => $op,
                    '%VALUE%' => $this->quoteString($value),
                ];
                break;

            default:
                $array = [
                    '%FIELD%' => $field,
                    '%OP%'    => $op,
                    '%VALUE%' => $value
                ];
        }

        $vars = array_keys($array);
        $return = [
            'expression' => str_replace($vars, $array, $tpl),
            'parameters' => [],
        ];
        return $return;
    }


    protected function op_EQ($field, $op, $data)
    {
        return $this->op_COMMON($field, '=', $data);
    }


    protected function op_GT($field, $op, $data)
    {
        return $this->op_COMMON($field, '>', $data);
    }


    protected function op_LT($field, $op, $data)
    {
        return $this->op_COMMON($field, '<', $data);
    }


    protected function op_EGT($field, $op, $data)
    {
        return $this->op_COMMON($field, '>=', $data);
    }


    protected function op_ELT($field, $op, $data)
    {
        return $this->op_COMMON($field, '<=', $data);
    }


    protected function op_NEQ($field, $op, $data)
    {
        $result = $this->op_COMMON($field, '=', $data);
        return [
            'expression' => 'NOT ' . $result['expression'],
            'parameters' => [],
        ];
    }


    protected function op_RAW($field, $op, $data)
    {
        return [
            'expression' => $data,
            'parameters' => [],
        ];
    }
}
