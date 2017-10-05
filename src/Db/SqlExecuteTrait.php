<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

/**
 * Sql Execute Trait
 */
trait SqlExecuteTrait
{
    /**
     * Executes an SQL statement that maybe affect the data.
     *
     * @return boolean
     */
    public function execute()
    {
        if (!$this->built) {
            $this->build();
        }

        // Makes a DB connection.
        if ($this->db->connect() === false) {
            return false;
        }

        try {
            $this->pdoStatement = $this->db->pdo->prepare($this->statement);
            return $this->pdoStatement->execute($this->parameters);
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
        if ($this->pdoStatement === null) {
            if ($this->execute() === false) {
                return false;
            }
        }

        if (is_int($fetch_style)) {
            return $this->pdoStatement->fetch($fetch_style);
        } else {
            return $this->pdoStatement->fetch();
        }
    }


    /**
     * PDOStatement fetchAll()
     *
     * @return array(array)|false
     */
    public function fetchAll($fetch_style = null)
    {
        if ($this->pdoStatement === null) {
            if ($this->execute() === false) {
                return false;
            }
        }

        if (is_int($fetch_style)) {
            return $this->pdoStatement->fetchAll($fetch_style);
        } else {
            return $this->pdoStatement->fetchAll();
        }
    }


    public function rowCount()
    {
        return $this->pdoStatement->rowCount();
    }
}
