<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

use \PDO;

/**
 * Query Trait
 */
trait SqlQueryTrait
{
    /**
     * Executes an SQL statement that does not affect the data.
     *
     * @return \PDOStatement|false Returns a result set as a PDOStatement object or FALSE on failure.
     */
    public function query()
    {
        if (!$this->built) {
            $this->build();
        }

        // Makes a DB connection.
        if ($this->db->connect() === false) {
            return false;
        }

        try {
            $stmt = $this->db->pdo->prepare($this->statement);
            if ($stmt->execute($this->parameters)) {
                return $stmt;
            } else {
                return false;
            }
        } catch (Exception $ex) {
            return false;
        }
    }


    /**
     * PDOStatement fetch()
     *
     * @return array|false
     */
    public function fetch($fetch_style = null)
    {
        $result = $this->query();

        if ($result === false) {
            return false;
        }

        if (is_int($fetch_style)) {
            return $result->fetch($fetch_style);
        } else {
            return $result->fetch();
        }
    }


    /**
     * PDOStatement fetchAll()
     *
     * @return array(array)|false
     */
    public function fetchAll($fetch_style = null)
    {
        $result = $this->query();

        if ($result === false) {
            return false;
        }

        if (is_int($fetch_style)) {
            return $result->fetchAll($fetch_style);
        } else {
            return $result->fetchAll();
        }
    }
}
