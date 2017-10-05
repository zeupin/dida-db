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
}
