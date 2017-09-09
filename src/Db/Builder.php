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

    /* table and its defination */
    protected $table = null;
    protected $def = null;

    /* verb and its data */
    protected $verb = 'select';
    protected $verb_data = ['*'];

    /* 支持的SQL运算集 */
    protected static $opertor_set = [
        /* equal */
        'EQ'               => 'EQ',
        '='                => 'EQ',
        '=='               => 'EQ',
        /* not equal */
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

    /* WHERE */
    protected $where_changed = true;
    protected $where_items = [];
    protected $where_expression = '';
    protected $where_parameters = [];

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


    /**
     * Specify the WHERE condition(s).
     *
     * @param mixed $condition
     * @param mixed $type
     */
    public function where($condition, $type = null)
    {
        $this->where_changed = true;

        // treat $condition as a SQL statement
        if (is_string($condition)) {
            $this->whereSQL($condition);
            return $this;
        }
    }


    public function whereSQL($sql)
    {
        $this->where_changed = true;

        $this->where_items[] = [
            'expression' => self::bracket($sql),
            'parameters' => [],
        ];
    }


    public function whereONE($condition)
    {
        $this->where_changed = true;

        list($field, $op, $data) = $condition;

        // Checks whether $op is valid.
        if (!array_key_exists($op, self::$opertor_set)) {
            throw new Exception("Invalid operator \"$op\" in ". var_export($condition, true));
        }

        $method_name = 'op_'. $op;
        $this->where_items[] = $this->$method_name($field, $op, $data);
    }


    protected function build()
    {
        $this->build_WHERE();

        switch ($this->verb) {
            case 'count':
                $this->build_COUNT();
                break;
        }
    }


    /**
     * Builds the WHERE expression.
     */
    protected function build_WHERE()
    {
        // if not changed, do nothing
        if (!$this->where_changed) {
            return;
        }

        // links the where_items
        $expression = [];
        $parameters = [];
        foreach ($this->where_items as $item) {
            $expression[] = $item['expression'];
            $params=$item['parameters'];
            foreach($params as $param) {
                $parameters[] = $param;
            }
        }

        $where = implode(' AND ', $expression);

        if (empty($expression)) {
            $this->where_expression = '';
            $this->where_parameters = [];
        } else {
            $this->where_expression = " WHERE $where";
            $this->where_parameters = $parameters;
        }

        // build completed
        $this->where_changed = false;
    }


    public function count($fields = '*')
    {
        $this->verb = 'count';
        $this->verb_data = $fields;

        return $this;
    }


    /**
     * Action: Returns the SQL expression.
     */
    public function sql()
    {
        $this->action();

        return $this->sql_statement;
    }


    /**
     * Action: Gets a record from the recordset.
     */
    public function get()
    {
        $this->action();

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
        $this->action();

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


    protected function action()
    {
        $this->build();
    }


    protected function build_COUNT()
    {
        $fields = $this->verb_data;

        $data = [
            '%TABLE%'  => $this->quoteTable($this->table),
            "%FIELDS%" => $fields,
            '%WHERE%'  => $this->where_expression,
        ];

        $this->sql_statement = self::assemble(self::$tpl_COUNT, $data);
        $this->sql_parameters = $this->where_parameters;
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

        $keys = array_keys($array);
        $return = [
            'expression' => str_replace($keys, $array, $tpl),
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
