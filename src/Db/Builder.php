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
    /**
     * @var \Dida\Db
     */
    protected $db = null;

    /* table & its defination */
    protected $table = null;
    protected $def = null;

    /* 支持的SQL运算集 */
    protected static $opertor_set = [
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

    /* sql templates */
    protected static $tpl_COUNT = 'SELECT COUNT(%FIELDS%) FROM %TABLE%%WHERE%';

    /* conditions */
    protected $conditions = [];
    protected $conditions_changed = false;
    protected $conditions_expression = '';
    protected $conditions_parameters = [];

    /* final sql */
    protected $sql_statement = '';
    protected $sql_parameters = [];


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


    /**
     * Brackets a string value.
     * 
     * @param string $string
     * @return string
     */
    public static function bracket($string)
    {
        if ($string === '') {
            return '';
        }

        if (substr($string, 0, 1) === '(' && substr($string, -1, 1) === ')') {
            return $string;
        }

        return "($string)";
    }


    public function where($condition)
    {
        $this->conditions_changed = true;

        if (is_string($condition)) {
            $this->where_SQL($condition);
            return $this;
        }
    }


    public function where_SQL($sql)
    {
        $this->conditions[] = self::bracket($sql);
    }


    public function count($fields = '*')
    {
        $this->build_WHERE();
        $this->build_COUNT();
        return $this;
    }


    /**
     * Action: Returns the SQL expression.
     */
    public function sql()
    {
        return $this->sql_statement;
    }


    /**
     * Action: Gets a record from the recordset.
     */
    public function get()
    {
        if (count($this->sql_parameters) === 0) {
            $stmt = $this->db->pdo->query($this->sql_statement);
            if ($stmt === false) {
                return false;
            } else {
                return $stmt->fetch();
            }
        } else {
            $stmt = $this->db->pdo->prepare($this->sql_statement);
            if ($stmt === false) {
                return false;
            } else {
                $stmt->execute($this->sql_parameters);
                return $stmt->fetch();
            }
        }
    }


    /**
     * Action: Gets all records from the recordset.
     */
    public function getAll()
    {
        if (count($this->sql_parameters) === 0) {
            $stmt = $this->db->pdo->query($this->sql_statement);
            if ($stmt === false) {
                return false;
            } else {
                return $stmt->fetchAll();
            }
        } else {
            $stmt = $this->db->pdo->prepare($this->sql_statement);
            if ($stmt === false) {
                return false;
            } else {
                $stmt->execute($this->sql_parameters);
                return $stmt->fetchAll();
            }
        }
    }


    protected function build_COUNT($fields = '*')
    {
        $data = [
            '%TABLE%'  => $this->quoteTable($this->table),
            "%FIELDS%" => $fields,
            '%WHERE%'  => $this->conditions_expression,
        ];
        $this->sql_statement = self::assemble(self::$tpl_COUNT, $data);
    }


    protected static function assemble($tpl, $data)
    {
        $vars = array_keys($data);
        try {
            $result = str_replace($vars, $data, $tpl);
            return $result;
        } catch (Exception $ex) {
            return false;
        }
    }


    protected function build_WHERE()
    {
        if (!$this->conditions_changed) {
            return;
        }

        $where = implode(' AND ', $this->conditions);
        if ($where === '') {
            $this->conditions_expression = '';
        } else {
            $this->conditions_expression = " WHERE $where";
        }

        $this->conditions_changed = false;
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


    protected function op_RAW($field, $op, $data)
    {
        return [
            'expression' => $data,
            'parameters' => [],
        ];
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

            case 'time':
                $array = [
                    '%FIELD%' => $field,
                    '%OP%'    => $op,
                    '%VALUE%' => $this->quoteTime($value),
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
}
