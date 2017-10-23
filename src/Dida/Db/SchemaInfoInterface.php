<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

/**
 * Schema Interface
 */
interface SchemaInfoInterface
{
    /**
     * List all table names of the <schema>.
     */
    public function listTableNames($schema, $prefix = '');


    /**
     * Get all metadata about the <schema.table>.
     */
    public function getTableInfo($schema, $table);


    /**
     * Get all column information about the <schema.table>.
     */
    public function getAllColumnInfo($schema, $table);
}
