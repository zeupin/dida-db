<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License.
 * Redistributions of files MUST retain the above copyright notice.
 */

namespace Dida\Db;

use \Exception;

class Query
{
    const VERSION = '20171113';

    const INSERT_RETURN_COUNT = 1;
    const INSERT_RETURN_ID = 2;

    const INSERT_MANY_RETURN_SUCC_COUNT = 1;
    const INSERT_MANY_RETURN_SUCC_LIST = 2;

    const INSERT_MANY_RETURN_FAIL_COUNT = -1;
    const INSERT_MANY_RETURN_FAIL_LIST = -2;
    const INSERT_MANY_RETURN_FAIL_REPORT = -3;

    protected $db = null;

    protected $builder = null;

    protected $schemainfo = null;

    protected $tasklist = [];

    protected $taskbase = [
        'verb'        => 'SELECT',
        'prefix'      => '',
        'swap_prefix' => '###_',
    ];

    protected $whereActive = null;

    protected $whereDict = [];

    protected $havingActive = null;

    protected $havingDict = [];


    public function __construct(&$db)
    {
        $this->db = $db;

        $cfg = $this->db->getConfig();
        $this->taskbase = array_merge($this->taskbase, [
            'driver'      => $cfg['db.driver'],
            'prefix'      => $cfg['db.prefix'],
            'swap_prefix' => $cfg['db.swap_prefix'],
        ]);

        $this->init();
    }


    private function _________________________INIT()
    {
    }


    public function init()
    {
        $this->tasklist = $this->taskbase;

        return $this;
    }


    public function clear()
    {
        $table = $this->tasklist['table'];
        $this->table($table['name'], $table['prefix']);

        return $this;
    }


    private function _________________________BUILD()
    {
    }


    public function build($verb = null)
    {
        $builder = $this->db->getBuilder();
        if ($builder === null) {
            throw new \Dida\Db\Exceptions\InvalidBuilderException;
        }

        if (is_string($verb)) {
            $verb = trim($verb);
            $verb = strtoupper($verb);
            $this->tasklist['verb'] = $verb;
        }

        return $builder->build($this->tasklist);
    }


    private function _________________________TABLE()
    {
    }


    public function table($name_as_alias, $prefix = null)
    {
        $this->init();

        $this->tasklist['table'] = [
            'name'   => $name_as_alias,
            'prefix' => $prefix,
        ];

        return $this;
    }


    private function _________________________COLUMNLIST()
    {
    }


    public function columnlist($columnlist = null)
    {
        $this->initArrayItem('columnlist');

        if (is_string($columnlist)) {
            $this->tasklist['columnlist'][] = ['raw', $columnlist];
        } elseif (is_array($columnlist)) {
            $this->tasklist['columnlist'][] = ['array', $columnlist];
        }

        return $this;
    }


    public function distinct()
    {
        $this->initArrayItem('columnlist');

        $this->tasklist['columnlist'][] = ['distinct'];

        return $this;
    }


    public function count(array $columns = null, $alias = null)
    {
        $this->initArrayItem('columnlist');

        $this->tasklist['columnlist'][] = ['count', $columns, $alias];

        return $this;
    }


    private function _________________________WHERE()
    {
    }


    protected function initWhere()
    {
        if (isset($this->tasklist['where'])) {
            return;
        }

        $this->tasklist['where'] = new ConditionTree('AND');
        $this->whereDict = [];
        $this->whereDict[''] = &$this->tasklist['where'];
        $this->whereActive = &$this->tasklist['where'];
    }


    public function where()
    {
        $this->initWhere();

        $cnt = func_num_args();
        switch ($cnt) {
            case 4:
                $arg4 = func_get_arg(3);
            case 3:
                $arg3 = func_get_arg(2);
            case 2:
                $arg2 = func_get_arg(1);
                $arg2_is_array = is_array($arg2);
                $arg2_is_string = is_string($arg2);
            case 1:
                $arg1 = func_get_arg(0);
                $arg1_is_array = is_array($arg1);
                $arg1_is_string = is_string($arg1);
                if ($arg1_is_array) {
                    $arg1_array_type = $this->getArrayType($arg1);
                }
        }

        if ($cnt > 1 && $arg1_is_string && $arg2_is_string) {
            switch ($cnt) {
                case 2:
                    $this->whereActive->items[] = [$arg1, $arg2];
                    return $this;
                case 3:
                    $this->whereActive->items[] = [$arg1, $arg2, $arg3];
                    return $this;
                case 4:
                    $this->whereActive->items[] = [$arg1, $arg2, $arg3, $arg4];
                    return $this;
            }
        }

        if ($arg1_is_string) {
            if ($cnt == 1) {
                $this->whereActive->items[] = [$arg1, 'RAW', []];
                return $this;
            } elseif ($cnt == 2 && is_array($arg2)) {
                $this->whereActive->items[] = [$arg1, 'RAW', $arg2];
                return $this;
            }
        }

        if ($arg1_is_array && ($arg1_array_type == 2)) {
            if ($cnt == 1) {
                return $this->whereMatch($arg1);
            } elseif ($cnt == 2 && $arg2_is_string) {
                return $this->whereMatch($arg1, $arg2);
            } elseif ($cnt == 3 && $arg2_is_string && is_string($arg3)) {
                return $this->whereMatch($arg1, $arg2, $arg3);
            }
        }

        if ($arg1_is_array && ($arg1_array_type == -1)) {
            $this->whereActive->items[] = $arg1;
            return $this;
        }

        throw new Exception('非法的where条件');
    }


    public function whereGroup(array $conditions = [], $logic = 'AND', $name = null)
    {
        $this->initWhere();

        if (is_string($name)) {
            if (array_key_exists($name, $this->whereDict)) {
                throw new Exception("重复定义 where 命名组");
            }
        }

        $group = new ConditionTree($logic);
        $group->name = $name;
        $group->items = $conditions;

        $this->whereActive->items[] = &$group;
        $this->whereActive = &$group;

        if (is_string($name)) {
            $this->whereDict[$name] = &$group;
        }

        return $this;
    }


    public function whereLogic($logic)
    {
        $this->initWhere();

        $this->whereActive->logic = $logic;

        return $this;
    }


    public function whereMatch(array $array, $logic = 'AND', $name = null)
    {
        $this->initWhere();

        $conditions = [];
        foreach ($array as $key => $value) {
            $conditions[] = [$key, '=', $value];
        }

        $this->whereGroup($conditions, $logic, $name);

        return $this;
    }


    public function whereGoto($name)
    {
        if (!array_key_exists($name, $this->whereDict)) {
            throw new Exception("指定的节点不存在 $name");
        }

        $this->whereActive = &$this->whereDict[$name];

        return $this;
    }


    private function _________________________HAVING()
    {
    }


    protected function initHaving()
    {
        if (isset($this->tasklist['having'])) {
            return;
        }

        $this->tasklist['having'] = new ConditionTree('AND');
        $this->havingDict = [];
        $this->havingDict[''] = &$this->tasklist['having'];
        $this->havingActive = &$this->tasklist['having'];
    }


    public function having()
    {
        $this->initHaving();

        $cnt = func_num_args();
        switch ($cnt) {
            case 4:
                $arg4 = func_get_arg(3);
            case 3:
                $arg3 = func_get_arg(2);
            case 2:
                $arg2 = func_get_arg(1);
                $arg2_is_array = is_array($arg2);
                $arg2_is_string = is_string($arg2);
            case 1:
                $arg1 = func_get_arg(0);
                $arg1_is_array = is_array($arg1);
                $arg1_is_string = is_string($arg1);
                if ($arg1_is_array) {
                    $arg1_array_type = $this->getArrayType($arg1);
                }
        }

        if ($cnt > 2 && $arg1_is_string && $arg2_is_string) {
            switch ($cnt) {
                case 2:
                    $this->havingActive->items[] = [$arg1, $arg2];
                    return $this;
                case 3:
                    $this->havingActive->items[] = [$arg1, $arg2, $arg3];
                    return $this;
                case 4:
                    $this->havingActive->items[] = [$arg1, $arg2, $arg3, $arg4];
                    return $this;
            }
        }

        if ($arg1_is_string) {
            if ($cnt == 1) {
                $this->havingActive->items[] = [$arg1, 'RAW', []];
                return $this;
            } elseif ($cnt == 2 && is_array($arg2)) {
                $this->havingActive->items[] = [$arg1, 'RAW', $arg2];
                return $this;
            }
        }

        if ($arg1_is_array && ($arg1_array_type == 2)) {
            if ($cnt == 1) {
                return $this->havingMatch($arg1);
            } elseif ($cnt == 2 && $arg2_is_string) {
                return $this->havingMatch($arg1, $arg2);
            } elseif ($cnt == 3 && $arg2_is_string && is_string($arg3)) {
                return $this->havingMatch($arg1, $arg2, $arg3);
            }
        }

        if ($arg1_is_array && ($arg1_array_type == -1)) {
            $this->havingActive->items[] = $arg1;
            return $this;
        }

        throw new Exception('非法的having条件');
    }


    public function havingGroup(array $conditions = [], $logic = 'AND', $name = null)
    {
        $this->initHaving();

        if (is_string($name)) {
            if (array_key_exists($name, $this->havingDict)) {
                throw new Exception("重复定义HAVING命名组");
            }
        }

        $group = new ConditionTree($logic);
        $group->name = $name;
        $group->items = $conditions;

        $this->havingActive->items[] = &$group;
        $this->havingActive = &$group;

        if (is_string($name)) {
            $this->havingDict[$name] = &$group;
        }

        return $this;
    }


    public function havingLogic($logic)
    {
        $this->initHaving();

        $this->havingActive->logic = $logic;

        return $this;
    }


    public function havingMatch(array $array, $logic = 'AND', $name = null)
    {
        $this->initHaving();

        $conditions = [];
        foreach ($array as $key => $value) {
            $conditions[] = [$key, '=', $value];
        }

        $this->havingGroup($conditions, $logic, $name);

        return $this;
    }


    public function havingGoto($name)
    {
        if (!array_key_exists($name, $this->havingDict)) {
            throw new Exception("指定的节点不存在 $name");
        }

        $this->havingActive = &$this->havingDict[$name];

        return $this;
    }


    private function _________________________JOINS()
    {
    }


    public function join($tableB, $on, array $parameters = [])
    {
        $this->initArrayItem('join');

        $this->tasklist['join'][] = ['JOIN', $tableB, $on, $parameters];

        return $this;
    }


    public function innerJoin($tableB, $on, array $parameters = [])
    {
        $this->initArrayItem('join');

        $this->tasklist['join'][] = ['INNER JOIN', $tableB, $on, $parameters];

        return $this;
    }


    public function leftJoin($tableB, $on, array $parameters = [])
    {
        $this->initArrayItem('join');

        $this->tasklist['join'][] = ['LEFT JOIN', $tableB, $on, $parameters];

        return $this;
    }


    public function rightJoin($tableB, $on, array $parameters = [])
    {
        $this->initArrayItem('join');

        $this->tasklist['join'][] = ['RIGHT JOIN', $tableB, $on, $parameters];

        return $this;
    }


    private function _________________________GROUPBY_ORDERBY_LIMIT()
    {
    }


    public function groupBy($columns)
    {
        $this->initArrayItem('groupby');

        $this->tasklist['groupby'][] = $columns;

        return $this;
    }


    public function orderBy($columns)
    {
        $this->initArrayItem('orderby');

        $this->tasklist['orderby'][] = $columns;

        return $this;
    }


    public function limit($limit)
    {
        $this->tasklist['limit'] = $limit;

        return $this;
    }


    private function _________________________INSERT()
    {
    }


    public function record(array $record)
    {
        $this->tasklist['record'] = $record;

        return $this;
    }


    private function _________________________UPDATE()
    {
    }


    public function setValue($column, $value = null)
    {
        $this->initArrayItem('set');

        if (is_string($column)) {
            $this->tasklist['set'][$column] = [
                'type'   => 'value',
                'column' => $column,
                'value'  => $value,
            ];
        } elseif (is_array($column)) {
            foreach ($column as $key => $value) {
                $this->tasklist['set'][$key] = [
                    'type'   => 'value',
                    'column' => $key,
                    'value'  => $value,
                ];
            }
        } else {
            throw new Exception(__METHOD__ . '参数类型错误');
        }

        return $this;
    }


    public function setExpr($column, $expr, array $parameters = [])
    {
        $this->initArrayItem('set');



        $this->tasklist['set'][$column] = [
            'type'       => 'expr',
            'column'     => $column,
            'expr'       => $expr,
            'parameters' => $parameters,
        ];

        return $this;
    }


    public function setFromTable($column, $tableB, $columnB, $colA, $colB, $checkExistsInWhere = true)
    {
        $this->initArrayItem('set');

        $this->tasklist['set'][$column] = [
            'type'               => 'from_table',
            'column'             => $column,
            'tableB'             => $tableB,
            'columnB'            => $columnB,
            'colA'               => $colA,
            'colB'               => $colB,
            'checkExistsInWhere' => $checkExistsInWhere,
        ];

        return $this;
    }


    public function increment($column, $value = 1)
    {
        $this->initArrayItem('set');


        $this->setExpr($column, "$column + ?", [$value]);

        return $this;
    }


    public function decrement($column, $value = 1)
    {
        $this->initArrayItem('set');


        $this->setExpr($column, "$column - ?", [$value]);

        return $this;
    }


    private function _________________________EXECUTIONS()
    {
    }


    public function select($columnlist = null)
    {
        if (!is_null($columnlist)) {
            $this->columnlist($columnlist);
        }

        $conn = $this->db->getConnection();

        $this->tasklist['verb'] = 'SELECT';
        $sql = $this->build();
        $dataset = $conn->executeRead($sql['statement'], $sql['parameters']);

        return $dataset;
    }


    public function insertOne(array $record, $insertReturn = self::INSERT_RETURN_COUNT)
    {
        if (empty($record)) {
            return 0;
        }

        if (!$this->isAssociateArray($record)) {
            return false;
        }

        $this->record($record);

        $conn = $this->db->getConnection();

        $this->tasklist['verb'] = 'INSERT';
        $sql = $this->build();
        $rowsAffected = $conn->executeWrite($sql['statement'], $sql['parameters']);

        switch ($insertReturn) {
            case self::INSERT_RETURN_COUNT:
                return $rowsAffected;

            case self::INSERT_RETURN_ID:
                if ($rowsAffected === false) {
                    return false;
                }

                return $conn->getPDO()->lastInsertId();
        }
    }


    public function insertMany(array $records, $returnType = self::INSERT_RETURN_COUNT)
    {
        if (empty($records)) {
            return 0;
        }

        if (!$this->isIndexedArray($records)) {
            return false;
        }

        $succ_count = 0;
        $succ_list = [];

        $fail_count = 0;
        $fail_list = [];
        $fail_report = [];

        $pdo = $this->db->getConnection()->getPDO();

        $last_keys = null;
        $last_statement = null;
        $stmt = null;

        foreach ($records as $seq => $record) {
            $this_keys = array_keys($record);

            if ($last_keys !== $this_keys) {
                $this->tasklist['record'] = $record;
                $this->tasklist['verb'] = 'INSERT';
                $sql = $this->build();
                $last_statement = $sql['statement'];
                $last_keys = $this_keys;
                $values = array_values($record);

                $stmt = $pdo->prepare($last_statement);
                if ($stmt === false) {
                    $last_keys = null;
                    continue;
                }

                $result = $stmt->execute($values);

                if ($result) {
                    $succ_count++;
                    if ($returnType === self::INSERT_MANY_RETURN_SUCC_LIST) {
                        $succ_list[$seq] = $pdo->lastInsertId();
                    }
                } else {
                    $fail_count++;
                    if ($returnType === self::INSERT_MANY_RETURN_FAIL_LIST) {
                        $fail_list[$seq] = $pdo->errorCode();
                    } elseif ($returnType === self::INSERT_MANY_RETURN_FAIL_REPORT) {
                        $fail_report[$seq] = $pdo->errorInfo();
                    }
                }

                continue;
            } else {
                $values = array_values($record);

                $result = $stmt->execute($values);

                if ($result) {
                    $succ_count++;
                    if ($returnType === self::INSERT_MANY_RETURN_SUCC_LIST) {
                        $succ_list[$seq] = $pdo->lastInsertId();
                    }
                } else {
                    $fail_count++;
                    if ($returnType === self::INSERT_MANY_RETURN_FAIL_LIST) {
                        $fail_list[$seq] = $pdo->errorCode();
                    } elseif ($returnType === self::INSERT_MANY_RETURN_FAIL_REPORT) {
                        $fail_report[$seq] = $pdo->errorInfo();
                    }
                }

                continue;
            }
        }

        switch ($returnType) {
            case self::INSERT_MANY_RETURN_SUCC_COUNT:
                return $succ_count;
            case self::INSERT_MANY_RETURN_SUCC_LIST:
                return $succ_list;
            case self::INSERT_MANY_RETURN_FAIL_COUNT:
                return $fail_count;
            case self::INSERT_MANY_RETURN_FAIL_LIST:
                return $fail_list;
            case self::INSERT_MANY_RETURN_FAIL_REPORT:
                return $fail_report;
        }
    }


    public function update()
    {
        $conn = $this->db->getConnection();

        $sql = $this->build('UPDATE');

        $result = $conn->executeWrite($sql['statement'], $sql['parameters']);

        return $result;
    }


    public function insertOrUpdateOne(array $record, $pri_col)
    {
        $this->clear();

        $pdo = $this->db->getConnection()->getPDO();

        $sql = $this->record($record)->build('INSERT');
        $stmt = $pdo->prepare($sql['statement']);
        $result = $stmt->execute($sql['parameters']);

        if ($result) {
            return true;
        }

        $this->clear();
        $sql = $this->where($pri_col, '=', $record[$pri_col])
            ->setValue($record)
            ->build('UPDATE');
        $stmt = $pdo->prepare($sql['statement']);
        $result = $stmt->execute($sql['parameters']);

        if ($result && $stmt->rowCount()) {
            return true;
        } else {
            return false;
        }
    }


    public function insertOrUpdateMany(array $records, $pri_col)
    {
        $succ = [];
        $fail = [];

        $last_keys = null;
        $stmtInsert = null;

        $pdo = $this->db->getConnection()->getPDO();

        foreach ($records as $seq => $record) {
            $this_keys = array_keys($record);
            $values = array_values($record);
            if ($this_keys !== $last_keys) {
                $sql = $this->record($record)->build('INSERT');
                $stmtInsert = $pdo->prepare($sql['statement']);
                $last_keys = $this_keys;
            }

            $result = $stmtInsert->execute($values);
            if ($result && $stmtInsert->rowCount() > 0) {
                $succ[$seq] = null;
                continue;
            }

            $this->clear();
            $sql = $this->where($pri_col, '=', $record[$pri_col])
                ->setValue($record)
                ->build('UPDATE');
            $stmtUpdate = $pdo->prepare($sql['statement']);
            $result = $stmtUpdate->execute($sql['parameters']);
            if ($result && $stmtUpdate->rowCount() > 0) {
                $succ[$seq] = null;
                continue;
            }

            $fail[$seq] = null;
        }

        return [
            'succ' => $succ,
            'fail' => $fail,
        ];
    }


    public function delete()
    {
        $conn = $this->db->getConnection();

        $this->tasklist['verb'] = 'DELETE';
        $sql = $this->build();
        $rowsAffected = $conn->executeWrite($sql['statement'], $sql['parameters']);
        return $rowsAffected;
    }


    public function truncate()
    {
        $conn = $this->db->getConnection();

        $this->tasklist['verb'] = 'TRUNCATE';
        $sql = $this->build();
        $rowsAffected = $conn->executeWrite($sql['statement'], $sql['parameters']);
        return $rowsAffected;
    }


    public function __call($name, $arguments)
    {
        if (method_exists('\Dida\Db\DataSet', $name)) {
            switch ($name) {
                case 'not support':
                    break;
                default:
                    $dataset = $this->select();
                    return call_user_func_array([$dataset, $name], $arguments);
            }
        }

        throw new Exception(sprintf('方法不存在 %s::%s', __CLASS__, $name));
    }


    private function _________________________BACKUP_AND_RESTORE()
    {
    }


    public function backupTaskList()
    {
        $data = [
            'tasklist'     => $this->tasklist,
            'whereActive'  => $this->whereActive->name,
            'havingActive' => $this->havingActive->name,
        ];
        return $data;
    }


    public function restoreTaskList(array $data)
    {
        extract($data);

        $this->tasklist = $tasklist;

        if (isset($tasklist['where'])) {
            $this->whereDict = [];
            $this->tasklist['where']->getNamedDictionary($this->whereDict);
            $this->whereActive = &$this->whereDict[$whereActive];
        } else {
            $this->whereDict = [];
            $this->whereActive = null;
        }

        if (isset($tasklist['having'])) {
            $this->havingDict = [];
            $this->tasklist['having']->getNamedDictionary($this->havingDict);
            $this->havingActive = &$this->havingDict[$whereActive];
        } else {
            $this->havingDict = [];
            $this->havingActive = null;
        }
    }


    private function _________________________UTILITIES()
    {
    }


    protected function initArrayItem($name)
    {
        if (!isset($this->tasklist[$name])) {
            $this->tasklist[$name] = [];
        }
    }


    protected function getArrayType(array $array)
    {
        if (empty($array)) {
            return 0;
        }

        $num = false;
        $nan = false;
        foreach ($array as $key => $item) {
            if (is_int($key)) {
                $num = true;
            } else {
                $nan = true;
            }
        }

        if ($nan) {
            return ($num) ? 1 : 2;
        } else {
            return -1;
        }
    }


    protected function isAssociateArray(array $array)
    {
        foreach ($array as $key => $item) {
            if (is_int($key)) {
                return false;
            }
        }

        return true;
    }


    protected function isIndexedArray(array $array)
    {
        foreach ($array as $key => $item) {
            if (!is_int($key)) {
                return false;
            }
        }

        return true;
    }
}
