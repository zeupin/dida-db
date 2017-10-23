<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

/**
 * Data Query Clause Interface
 */
interface SqlSelectInterface
{
    /**
     * Adds a WHERE condition.
     *
     * @param mixed $condition
     * @param array $data
     *
     * @return $this
     */
    public function where($condition, array $data = []);


    /**
     * Adds many WHERE conditions.
     *
     * @param array $conditions
     * @param string $logic
     *
     * @return $this
     */
    public function whereMany(array $conditions, $logic = 'AND');


    /**
     * How to join the WHERE condition parts.
     *
     * @param string $logic AND/OR/...
     *
     * @return $this
     */
    public function whereLogic($logic);


    /**
     * Build a WHERE condition to match the given array.
     *
     * @param array $array
     * @param string $logic
     *
     * @return $this
     */
    public function whereMatch(array $array, $logic = 'AND');


    /**
     * JOIN clause
     *
     * @param string $tableB
     * @param string $on
     * @param array $parameters
     *
     * @return $this
     */
    public function join($tableB, $on, array $parameters = []);


    /**
     * INNER JOIN clause
     *
     * @param string $tableB
     * @param string $on
     * @param array $parameters
     *
     * @return $this
     */
    public function innerJoin($tableB, $on, array $parameters = []);


    /**
     * LEFT JOIN clause
     *
     * @param string $tableB
     * @param string $on
     * @param array $parameters
     *
     * @return $this
     */
    public function leftJoin($tableB, $on, array $parameters = []);


    /**
     * RIGHT JOIN clause
     *
     * @param string $tableB
     * @param string $on
     * @param array $parameters
     *
     * @return $this
     */
    public function rightJoin($tableB, $on, array $parameters = []);


    /**
     * GROUP BY clause.
     *
     * @param array $columns
     *
     * @return $this
     */
    public function groupBy(array $columns);


    /**
     * Adds a having condition.
     *
     * @param string|array $condition
     * @param array $parameters
     *
     * @return $this
     */
    public function having($condition, array $parameters = []);


    /**
     * Adds many having conditions.
     *
     * @param array $conditions
     * @param string $logic
     *
     * @return $this
     */
    public function havingMany(array $conditions, $logic = 'AND');


    /**
     * How to join having clause parts.
     *
     * @param string $logic AND/OR/XOR/...
     *
     * @return $this
     */
    public function havingLogic($logic);


    /**
     * DISTINCT clause.
     *
     * @param boolean $distinct
     *
     * @return $this
     */
    public function distinct($distinct = true);


    /**
     * ORDER BY clause.
     *
     * @param array|string $columns
     *
     * @return $this
     */
    public function orderBy($columns);


    /**
     * LIMIT clause.
     *
     * @param int|string $limit
     *
     * @return $this
     */
    public function limit($limit);
}
