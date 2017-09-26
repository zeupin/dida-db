<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

use \PDO;
use \Exception;

/**
 * Builder
 */
abstract class Builder
{

    // Traits
    use Builder\CommonTrait,
        Builder\WhereTrait,
        Builder\SelectTrait,
        Builder\InsertTrait,
        Builder\UpdateTrait,
        Builder\DeleteTrait,
        Builder\TruncateTrait;


    /*
     * ------------------------------------------------------------
     * Class common variables
     * ------------------------------------------------------------
     */
    /* table and its defination */
    protected $table = null;
    protected $table_alias = null;
    protected $prefix = null; /* table prefix */
    protected $formal_prefix = '###_'; /* formal table prefix */
    protected $def = null;
    protected $def_columns = null;
    protected $def_basetype = [];

    /* prepare mode */
    protected $preparemode = true;  // default TRUE

    /*
     * ------------------------------------------------------------
     * Operation variables
     * ------------------------------------------------------------
     */

    /* build */
    protected $built = false;

    /* verb */
    public $verb = 'SELECT';


    /* final sql */
    public $sql = '';
    public $sql_parameters = [];

    /*
     * ------------------------------------------------------------
     * Execution result variables.
     * ------------------------------------------------------------
     */
    protected $query_result = null;
    protected $execute_result = null;

    /**
     * query() result
     *
     * @var \PDOStatement|null
     */
    public $recordset = null;

    /**
     * execute() result
     *
     * @var int|null
     */
    public $rowsAffected = null;

    /*
     * ------------------------------------------------------------
     * SQL template variables.
     * ------------------------------------------------------------
     */

    /* EXISTS statement template */
    protected $EXISTS_STMT = [
        0      => 'SELECT EXISTS (',
        'expr' => '',
        1      => ')',
    ];

    /*
     * ------------------------------------------------------------
     * Class constants.
     * ------------------------------------------------------------
     */

    /* constants used by UPDATE set***() */
    const SET_VALUE = 'set value';
    const SET_EXPRESSION = 'set statement';
    const SET_FROM_TABLE = 'set from table';


    abstract protected function quoteTableName($table);


    abstract protected function quoteColumnName($column);


    abstract protected function quoteString($value);


    abstract protected function quoteTime($value);


    /**
     * @param \Dida\Db\Db $db
     * @param string $table
     */
    public function __construct($db, $table, $prefix = '', $formal_prefix = '###_')
    {
        $this->db = $db;
        $this->pdo_default_fetch_mode = $this->db->pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);

        $this->table = $prefix . $table;
        $this->prefix = $prefix;
        $this->formal_prefix = $formal_prefix;

        $this->def = include($db->workdir . '~SCHEMA' . DIRECTORY_SEPARATOR . $this->table . '.php');
        $this->def_columns = array_keys($this->def['COLUMNS']);
        $this->def_basetype = array_column($this->def['COLUMNS'], 'BASE_TYPE', 'COLUMN_NAME');

        $this->reset();
    }


    /**
     * Resets all operation variables.
     */
    public function reset()
    {
        /* build */
        $this->built = false;

        /* verb */
        $this->verb = 'SELECT';

        /* WHERE */
        $this->where_changed = true;
        $this->where_parts = [];
        $this->where_statement = '';
        $this->where_parameters = [];

        /* SELECT */
        $this->select_columnlist = [];
        $this->select_columnlist_statement = '';
        $this->select_distinct = false;
        $this->select_distinct_statement = '';
        $this->select_orderby_columns = '';
        $this->select_orderby_statement = '';
        $this->join = [];
        $this->join_statement = '';
        $this->groupby_columnlist = [];
        $this->groupby_statement = '';
        $this->having_conditions = [];
        $this->having_logic = '';
        $this->having_statement = '';

        /* INSERT */
        $this->insert_columns = [];
        $this->insert_record = [];
        $this->insert_statement = '';
        $this->insert_parameters = [];

        /* UPDATE */
        $this->update_set = [];
        $this->update_set_statement = '';
        $this->update_set_parameters = [];

        /* final sql */
        $this->sql = '';
        $this->sql_parameters = [];

        /* execute/query result */
        $this->query_result = null;
        $this->recordset = null;
        $this->execute_result = null;
        $this->rowsAffected = null;
    }


    public function prepare($flag = true)
    {
        $this->preparemode = $flag;

        return $this;
    }


    public function prefixConfig($prefix = '', $formal_prefix = '###_')
    {
        $this->prefix = $prefix;
        $this->formal_prefix = $formal_prefix;

        return $this;
    }


    public function alias($alias)
    {
        if ($alias && $this->isName($alias)) {
            $this->table_alias = $alias;
        } else {
            $this->table_alias = null;
        }

        return $this;
    }


    public function build()
    {
        if ($this->built) {
            return $this;
        }

        switch ($this->verb) {
            case 'SELECT':
                $this->build_SELECT();
                break;
            case 'UPDATE':
                $this->build_UPDATE();
                break;
            case 'INSERT':
                $this->build_INSERT();
                break;
            case 'DELETE':
                $this->build_DELETE();
                break;
            case 'TRUNCATE':
                $this->build_TRUNCATE();
                break;
            default:
                throw new Exception('Unknown verb "' . $this->verb . '"');
        }

        $this->built = true;
        return $this;
    }


    /**
     * Executes a SELECT statement.
     *
     * @return bool Returns true on success, false on failure.
     */
    public function query()
    {
        // check verb
        $this->checkVerbQuery();

        // check query_result
        if (is_bool($this->query_result)) {
            return $this->query_result;
        }

        // build
        $this->build();

        if (count($this->sql_parameters) === 0) {
            $stmt = $this->db->pdo->query($this->sql);
            if ($stmt === false) {
                $this->recordset = null;
                $this->query_result = false;
                return $this->query_result;
            } else {
                $this->recordset = $stmt;
                $this->query_result = true;
                return $this->query_result;
            }
        } else {
            $stmt = $this->db->pdo->prepare($this->sql);
            if ($stmt === false) {
                $this->recordset = null;
                $this->query_result = false;
                return $this->query_result;
            } else {
                $this->recordset = $stmt;
                $this->query_result = true;
                return $this->query_result;
            }
        }
    }


    /**
     * Executes a DELETE, INSERT, or UPDATE statement.
     *
     * @return bool Returns true on success, false on failure.
     */
    public function execute()
    {
        // pre-processing
        switch ($this->verb) {
            case 'INSERT':
            case 'UPDATE':
            case 'DELETE':
            case 'TRUNCATE':
                $this->rowsAffected = null;
                break;
            default:
                throw new Exception("Illegal verb type \"$this->verb\", expects INSERT/UPDATE/DELETE/TRUNCATE.");
        }

        // build
        $this->build();

        // execute
        try {
            if (count($this->sql_parameters)) {
                $stmt = $this->db->pdo->prepare($this->sql);
                if ($stmt->execute($this->sql_parameters)) {
                    $this->rowsAffected = $stmt->rowCount();
                } else {
                    return false;
                }
            } else {
                $result = $this->db->pdo->exec($this->sql);
                if ($result === false) {
                    return false;
                } else {
                    $this->rowsAffected = $result;
                }
            }
        } catch (Exception $ex) {
            $this->db->pdoexception = $ex;
            return false;
        }

        // return true on success
        return true;
    }


    /**
     * Fetches a record from the result set.
     */
    public function fetch($fetch_style = null, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
    {
        if ($this->query_result === null) {
            $this->query();
        }
        if ($this->query_result === false) {
            return false;
        }

        return $this->recordset->fetch($fetch_style, $cursor_orientation, $cursor_offset);
    }


    /**
     * Fetches all rest records from a result set.
     */
    public function fetchAll($fetch_style = null, $fetch_argument = null, array $ctor_args = null)
    {
        if ($this->query_result === null) {
            $this->query();
        }
        if ($this->query_result === false) {
            return false;
        }

        if ($ctor_args !== null) {
            return $this->recordset->fetchAll($fetch_style, $fetch_argument, $ctor_args);
        } elseif ($fetch_argument !== null) {
            return $this->recordset->fetchAll($fetch_style, $fetch_argument);
        } elseif ($fetch_style !== null) {
            return $this->recordset->fetchAll($fetch_style);
        } else {
            return $this->recordset->fetchAll();
        }
    }


    /**
     * Returns a column value of the next record.
     *
     * @param int $column_number
     */
    public function fetchColumn($column_number = 0)
    {
        if ($this->query_result === null) {
            $this->query();
        }
        if ($this->query_result === false) {
            return false;
        }

        return $this->recordset->fetchColumn($column_number);
    }


    public function exists()
    {
        // check verb
        $this->checkVerbQuery();

        // build
        $this->build();

        $tpl = [
            'SELECT EXISTS (',
            $this->sql,
            ')',
        ];
        $sql = implode('', $tpl);

        if (count($this->sql_parameters) === 0) {
            $stmt = $this->db->pdo->query($sql);
            if ($stmt === false) {
                return false;
            } else {
                $result = $stmt->fetchColumn();
            }
        } else {
            $stmt = $this->db->pdo->prepare($sql);
            if ($stmt === false) {
                return false;
            } else {
                $stmt->execute($this->sql_parameters);
                $result = $stmt->fetchColumn();
            }
        }

        return ($result === '1');
    }


    public function lastInsertId($name = null)
    {
        return $this->db->pdo->lastInsertId($name);
    }


    /**
     * Checks the verb is SELECT.
     *
     * @return boolean
     */
    protected function checkVerbQuery()
    {
        switch ($this->verb) {
            case 'SELECT':
                return true;
            default:
                throw new Exception("Illegal verb type \"$this->verb\" found, expects SELECT.");
        }
    }
}
