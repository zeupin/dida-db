<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

use \PDO;

/**
 * Builder
 */
abstract class Builder
{
    /**
     * @var \Dida\Db
     */
    protected $db = null;
    protected $pdo_default_fetch_mode = null;

    /* table and its defination */
    protected $table = null;
    protected $def = null;

    /* prepare mode */
    protected $prepare = false;

    /* SELECT template */
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
        'fields'  => [],
        'table'   => [],
        'join'    => [],
        'where'   => [],
        'group'   => [],
        'having'  => [],
        'orderby' => [],
        'limit'   => [],
        'union'   => [],
    ];

    /* INSERT template */
    protected $INSERT_expression = [
        0        => 'INSERT INTO ',
        'table'  => '',
        'fields' => '',
        1        => ' VALUES ',
        'data'   => '',
    ];
    protected $INSERT_parameters = [
        'table'  => [],
        'fields' => [],
        'data'   => [],
    ];

    /* UPDATE template */
    protected $UPDATE_expression = [
        0       => 'UPDATE ',
        'table' => '',
        1       => ' SET ',
        'data'  => '',
        'join'  => '',
        'where' => '',
    ];
    protected $UPDATE_parameters = [
        'table' => [],
        'data'  => [],
        'join'  => [],
        'where' => [],
    ];

    /* DELETE template */
    protected $DELETE_expression = [
        0       => 'DELETE FROM ',
        'table' => '',
        'join'  => '',
        'where' => '',
    ];
    protected $DELETE_parameters = [
        'table' => [],
        'join'  => [],
        'where' => [],
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

    /* build */
    protected $builded = false;

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
    public $sql = '';
    public $sql_parameters = [];


    abstract protected function quoteTable($table);


    abstract protected function quoteField($field);


    abstract protected function quoteString($value);


    abstract protected function quoteTime($value);


    /**
     * @param \Dida\Db $db
     * @param string $table
     */
    public function __construct($db, $table)
    {
        $this->db = $db;
        $this->pdo_default_fetch_mode = $this->db->pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);

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
        $this->whereChanged();

        // [string]
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
        $this->whereChanged();

        $items = [];

        foreach ($array as $field => $value) {
            $items[] = $this->cond_EQ($field, '=', $value);
        }

        $this->where_items[] = $this->combineWhereItems($items, 'AND');

        return $this;
    }


    public function whereSQL($sql)
    {
        $this->whereChanged();

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
        $this->whereChanged();

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
        $this->whereChanged();

        $items = [];
        foreach ($conditions as $condition) {
            $items[] = $this->cond($condition);
        }
        $result = $this->combineWhereItems($items, $logic);
        $result['expression'] = $this->bracket($result['expression']);
        $this->where_items[] = $result;

        return $this;
    }


    protected function whereChanged()
    {
        $this->where_changed = true;

        $this->buildChanged();
    }


    public function distinct($flag = true)
    {
        $this->buildChanged();

        $this->select_distinct = $flag;

        if ($flag) {
            $this->select_distinct_expression = 'DISTINCT ';
        } else {
            $this->select_distinct_expression = '';
        }

        return $this;
    }


    public function select($fields = ['*'])
    {
        $this->buildChanged();

        $this->verb = 'SELECT';
        $this->select_fields = $fields;

        return $this;
    }


    public function count($fields = '*', $alias = null)
    {
        $this->buildChanged();

        $this->verb = 'SELECT';

        if (is_string($alias)) {
            $this->select_fields = [$alias => "COUNT($fields)"];
        } else {
            $this->select_fields = ["COUNT($fields)"];
        }

        return $this;
    }


    public function build()
    {
        if ($this->builded) {
            return $this;
        }

        $this->build_WHERE();

        switch ($this->verb) {
            case 'SELECT':
                $this->build_SELECT();
                break;
        }

        $this->builded = true;
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
        $this->sql = implode('', $expression);

        $parameters = [
            'where' => $this->where_parameters,
        ];
        $parameters = array_merge($this->SELECT_parameters, $parameters);
        $this->sql_parameters = $this->combineParameterArray($parameters);
    }


    protected function build_SELECT_FIELDS()
    {
        if (empty($this->select_fields)) {
            $this->select_fields_expression = '*';
        } else {
            $this->select_fields_expression = $this->implodeFields($this->select_fields);
        }
    }


    protected function buildChanged()
    {
        $this->builded = false;
    }


    /**
     * Action: Fetches a record from a recordset.
     */
    public function fetch($fetch_style = null)
    {
        $this->build();

        if (is_null($fetch_style)) {
            $fetch_style = $this->pdo_default_fetch_mode;
        }
        if (count($this->sql_parameters) === 0) {
            $stmt = $this->db->pdo->query($this->sql);
            if ($stmt === false) {
                return false;
            } else {
                return $stmt->fetch($fetch_style);
            }
        } else {
            $stmt = $this->db->pdo->prepare($this->sql);
            if ($stmt === false) {
                return false;
            } else {
                $stmt->execute($this->sql_parameters);
                return $stmt->fetch($fetch_style);
            }
        }
    }


    /**
     * Action: Fetches all records from a recordset.
     */
    public function fetchAll($fetch_style = null)
    {
        $this->build();

        if (is_null($fetch_style)) {
            $fetch_style = $this->pdo_default_fetch_mode;
        }
        if (count($this->sql_parameters) === 0) {
            $stmt = $this->db->pdo->query($this->sql);
            if ($stmt === false) {
                return false;
            } else {
                return $stmt->fetchAll($fetch_style);
            }
        } else {
            $stmt = $this->db->pdo->prepare($this->sql);
            if ($stmt === false) {
                return false;
            } else {
                $stmt->execute($this->sql_parameters);
                return $stmt->fetchAll($fetch_style);
            }
        }
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
        $tpl = [
            '(',
            'field' => $this->quoteField($field),
            'op'    => " $op ",
            'value' => '',
            ')'
        ];

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
        if (is_array($data)) {
            return $this->cond_NOTIN($field, $op, $data);
        }

        return $this->cond_COMMON($field, '<>', $data);
    }


    protected function cond_IN($field, $op, $data)
    {
        if (empty($data)) {
            throw new Exception('An empty array not allowed use in a IN expression');
        }

        $tpl = [
            '(',
            'field' => $this->quoteField($field),
            'op'    => " $op ",
            '(',
            'list'  => '',
            '))'
        ];
        $expression = '';
        $parameters = [];

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


    protected function cond_NOTIN($field, $op, $data)
    {
        return $this->cond_IN($field, 'NOT IN', $data);
    }


    /**
     * Brackets a string value.
     *
     * @param string $string
     */
    public static function bracket($string)
    {
        return ($string === '') ? '' : "($string)";
    }


    protected function implodeFields($fields)
    {
        $return = [];
        foreach ($fields as $as => $field) {
            if (is_string($as)) {
                $return[] = $field . " AS " . $this->quoteField($as);
            } else {
                $return[] = $field;
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
