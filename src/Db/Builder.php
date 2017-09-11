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

    /* prepare mode */
    protected $prepare = false;

    /* sql templates */
    protected static $SELECT_TMPL = 'SELECT%distinct% %fields% FROM %table%%join%%where%%group%%having%%order%%limit%%union%';
    protected static $INSERT_TMPL = 'INSERT INTO %table% (%fields%) VALUES %data%';
    protected static $UPDATE_TMPL = 'UPDATE %table% SET %data%%join%%where%';
    protected static $DELETE_TMPL = 'DELETE FROM %table%%using%%join%%where%';
    protected $SELECT_expression = [
        0          => 'SELECT ',
        'distinct' => '',
        'fields'   => '',
        1          => ' FROM ',
        'table'    => '',
        'join'     => '',
        'where'    => '',
        'group'    => '',
        'having'   => '',
        'orderby'  => '',
        'limit'    => '',
        'union'    => '',
    ];
    protected $SELECT_parameters = [
        'distinct' => [],
        'fields'   => [],
        'table'    => [],
        'join'     => [],
        'where'    => [],
        'group'    => [],
        'having'   => [],
        'orderby'  => [],
        'limit'    => [],
        'union'    => [],
    ];


    /* sql template keys */
    protected static $SELECT_KEYS = [
        '%distinct%' => '',
        '%fields%'   => '',
        '%table%'    => '',
        '%join%'     => '',
        '%where%'    => '',
        '%group%'    => '',
        '%having%'   => '',
        '%order%'    => '',
        '%limit%'    => '',
        '%union%'    => '',
    ];
    protected static $INSERT_KEYS = [
        '%table%'  => '',
        '%fields%' => '',
        '%data%'   => '',
    ];
    protected static $UPDATE_KEYS = [
        '%table%' => '',
        '%data%'  => '',
        '%join%'  => '',
        '%where%' => '',
    ];
    protected static $DELETE_KEYS = [
        '%table%' => '',
        '%join%'  => '',
        '%where%' => '',
    ];

    /* 支持的SQL条件运算集 */
    protected static $opertor_set = [
        /* Raw SQL */
        'SQL'              => 'SQL',
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
        /* 时间类型字段的运算 */
        'TIME >'           => 'TIME_GT',
        'TIME <'           => 'TIME_LT',
        'TIME >='          => 'TIME_EGT',
        'TIME <='          => 'TIME_ELT',
        'TIME BETWEEN'     => 'TIME_BETWEEN',
        'TIME NOT BETWEEN' => 'TIME_NOTBETWEEN',
    ];

    /* verb */
    protected $verb = 'SELECT';

    /* SELECT */
    protected $select_fields = ['*'];
    protected $select_fields_expression = '';
    protected $select_distinct = false;
    protected $select_distinct_expression = '';

    /* WHERE */
    protected $where_changed = true;
    protected $where_items = [];
    protected $where_expression = '';
    protected $where_parameters = [];

    /* final sql */
    public $sql_expression = '';
    public $sql_parameters = [];


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


    public function prepare($flag = true)
    {
        $this->prepare = $flag;
        return $this;
    }


    /**
     * Specify the WHERE condition(s).
     */
    public function where($condition)
    {
        $this->where_changed = true;

        // [string]
        // treats it as a WHERE expression directly
        if (is_string($condition)) {
            return $this->whereSQL($condition);
        }

        // [array]
        if (is_array($condition)) {
            return $this->whereEQ($condition);
        }
    }


    public function whereEQ($array)
    {
        $this->where_changed = true;

        $items = [];

        foreach ($array as $field => $value) {
            $items[] = $this->cond_EQ($field, '=', $value);
        }

        $this->where_items[] = $this->combineWhereItems($items, 'AND');

        return $this;
    }


    public function whereSQL($sql)
    {
        $this->where_changed = true;

        $this->where_items[] = [
            'expression' => self::bracket($sql),
            'parameters' => [],
        ];

        return $this;
    }


    /**
     * Set one WHERE condition.
     *
     * @param array $condition  [$field, $op, $data]
     */
    public function whereONE(array $condition)
    {
        $this->where_changed = true;

        $result = $this->cond($condition);
        $this->where_items[] = $result;

        return $this;
    }


    /**
     * Sets many WHERE conditions at one time.
     *
     * @param array $conditions  The conditions array like:
     *      [
     *          [$field, $op, $data],
     *          [$field, $op, $data],
     *          [$field, $op, $data],
     *      ]
     * @param string $logic
     */
    public function whereMANY(array $conditions, $logic = 'AND')
    {
        $items = [];
        foreach ($conditions as $condition) {
            $items[] = $this->cond($condition);
        }
        $result = $this->combineWhereItems($items, $logic);
        $result['expression'] = $this->bracket($result['expression']);
        $this->where_items[] = $result;

        return $this;
    }


    public function distinct($flag = true)
    {
        $this->select_distinct = $flag;

        if ($flag) {
            $this->select_distinct_expression = 'DISTINCT ';
        } else {
            $this->select_distinct_expression = '';
        }

        return $this;
    }


    public function build()
    {
        $this->build_WHERE();

        switch ($this->verb) {
            case 'SELECT':
                $this->build_SELECT();
                break;
        }

        return $this;
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

        // combine the where_items
        $where = $this->combineWhereItems($this->where_items, 'AND');

        if ($where['expression'] === '') {
            $this->where_expression = '';
            $this->where_parameters = [];
        } else {
            $this->where_expression = " WHERE " . $where['expression'];
            $this->where_parameters = $where['parameters'];
        }

        // build completed
        $this->where_changed = false;
    }


    protected function build_SELECT()
    {
        $this->build_WHERE();
        $this->build_SELECT_FIELDS();

        $expression = [
            'table'    => $this->quoteTable($this->table),
            'distinct' => $this->select_distinct_expression,
            "fields"   => $this->select_fields_expression,
            'where'    => $this->where_expression,
        ];
        $expression = array_merge($this->SELECT_expression, $expression);
        $this->sql_expression = implode('', $expression);

        $parameters = [
            'where' => $this->where_parameters,
        ];
        $parameters = array_merge($this->SELECT_parameters, $parameters);
        $this->sql_parameters = $this->combineParameterArray($parameters);
    }


    protected function build_SELECT_FIELDS()
    {
        $this->select_fields_expression = $this->calc_FIELDS($this->select_fields);
    }


    /**
     * Action: Returns the SQL expression.
     */
    public function sql()
    {
        $this->build();

        return $this->sql_expression;
    }


    public function select($fields = ['*'])
    {
        $this->verb = 'SELECT';
        $this->select_fields = $fields;

        return $this;
    }


    public function count($fields = '*')
    {
        $this->verb = 'SELECT';
        $this->select_fields = ["COUNT($fields)"];

        return $this;
    }


    /**
     * Action: Gets a record from the recordset.
     */
    public function get()
    {
        $this->build();

        if (count($this->sql_parameters) === 0) {
            $stmt = $this->db->pdo->query($this->sql_expression);
            if ($stmt === false) {
                return false;
            } else {
                return $stmt->fetch();
            }
        } else {
            $stmt = $this->db->pdo->prepare($this->sql_expression);
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
        $this->build();


        if (count($this->sql_parameters) === 0) {
            $stmt = $this->db->pdo->query($this->sql_expression);
            if ($stmt === false) {
                return false;
            } else {
                return $stmt->fetchAll();
            }
        } else {
            $stmt = $this->db->pdo->prepare($this->sql_expression);
            if ($stmt === false) {
                return false;
            } else {
                $stmt->execute($this->sql_parameters);
                return $stmt->fetchAll();
            }
        }
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


    protected function cond($condition)
    {
        // check condition is valid
        $cnt = count($condition);
        if ($cnt === 3) {
            list($field, $op, $data) = $condition;
        } elseif ($cnt === 2) {
            list($field, $op) = $condition;
            $data = null;
        } else {
            throw new Exception("Invalid condition as " . var_export($condition, true));
        }

        // Checks whether $op is valid.
        $op = strtoupper($op);
        if (!array_key_exists($op, self::$opertor_set)) {
            throw new Exception("Invalid operator \"$op\" in condition " . var_export($condition, true));
        }

        // calls the 'cond_*' function
        $method_name = 'cond_' . self::$opertor_set[$op];
        return $this->$method_name($field, $op, $data);
    }


    protected function cond_SQL($field, $op, $data)
    {
        return [
            'expression' => $data,
            'parameters' => [],
        ];
    }


    protected function cond_COMMON($field, $op, $data)
    {
        $tpl = ['(', 'field' => '', ' ', 'op' => '', ' ', 'value' => '', ')'];
        $tpl['field'] = $this->quoteField($field);
        $tpl['op'] = $op;

        $expression = '';
        $parameters = [];

        if ($this->prepare) {
            $tpl['value'] = '?';
            $expression = implode('', $tpl);
            $parameters[] = $data;
            $return = [
                'expression' => $expression,
                'parameters' => $parameters,
            ];
            return $return;
        }

        switch ($this->def['COLUMNS'][$field]['BASE_TYPE']) {
            case 'string':
                $tpl['value'] = $this->quoteString($data);
                break;
            case 'time':
                $tpl['value'] = $this->quoteTime($data);
                break;
            default:
                $tpl['value'] = $data;
        }

        $expression = implode('', $tpl);
        $return = [
            'expression' => $expression,
            'parameters' => [],
        ];
        return $return;
    }


    protected function cond_EQ($field, $op, $data)
    {
        if (is_array($data)) {
            return $this->cond_IN($field, 'IN', $data);
        }
        
        return $this->cond_COMMON($field, '=', $data);
    }


    protected function cond_GT($field, $op, $data)
    {
        return $this->cond_COMMON($field, '>', $data);
    }


    protected function cond_LT($field, $op, $data)
    {
        return $this->cond_COMMON($field, '<', $data);
    }


    protected function cond_EGT($field, $op, $data)
    {
        return $this->cond_COMMON($field, '>=', $data);
    }


    protected function cond_ELT($field, $op, $data)
    {
        return $this->cond_COMMON($field, '<=', $data);
    }


    protected function cond_NEQ($field, $op, $data)
    {
        $result = $this->cond_COMMON($field, '=', $data);
        return [
            'expression' => 'NOT ' . $result['expression'],
            'parameters' => [],
        ];
    }


    protected function cond_IN($field, $op, $data)
    {
        if (empty($data)) {
            throw new Exception('An empty array not allowed use in a IN expression');
        }

        $tpl = [ '(', 'field' => '', ' IN (', 'list' => '', '))'];
        $expression = '';
        $parameters = [];

        $tpl['field'] = $this->quoteField($field);

        if ($this->prepare) {
            $marks = array_fill(0, count($data), '?');
            $tpl['list'] = implode(',', $marks);
            $expression = implode('', $tpl);
            $parameters = array_values($data);

            $return = [
                'expression' => $expression,
                'parameters' => $parameters,
            ];
            return $return;
        }

        $base_type = $this->def['COLUMNS'][$field]['BASE_TYPE'];
        switch ($base_type) {
            case 'string':
                foreach ($data as $key => $value) {
                    $data[$key] = $this->quoteString($value);
                }
                break;
            case 'time':
                foreach ($data as $key => $value) {
                    $data[$key] = $this->quoteTime($value);
                }
                break;
        }
        $tpl['list'] = implode(',', $data);
        $expression = implode('', $tpl);

        return [
            'expression' => $expression,
            'parameters' => [],
        ];
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

        return "($string)";
    }


    public static function replaceTemplate($tpl, $data)
    {
        $vars = array_keys($data);
        try {
            $result = str_replace($vars, $data, $tpl);
            return $result;
        } catch (Exception $ex) {
            return false;
        }
    }


    protected function calc_FIELDS($fields)
    {
        $return = [];
        foreach ($fields as $as => $name) {
            if (is_string($as)) {
                $return[] = $name . " AS " . $this->quoteField($as);
            } else {
                $return[] = $name;
            }
        }
        return implode(',', $return);
    }


    protected function combineWhereItems($items, $logic = 'AND')
    {
        $expression = array_column($items, 'expression');
        $parameters = array_column($items, 'parameters');

        return [
            'expression' => implode(" $logic ", $expression),
            'parameters' => $this->combineParameterArray($parameters),
        ];
    }


    protected function combineParameterArray($array)
    {
        $ret = [];
        foreach ($array as $parameters) {
            $ret = array_merge($ret, array_values($parameters));
        }
        return $ret;
    }
}
