<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

/**
 * SchemaInfo Interface
 *
 * 查询指定数据库的表元信息和列元信息。
 */
interface SchemaInfoInterface
{
    /**
     * 列出<schema>中的所有表名
     */
    public function listTableNames($prefix = null, $schema = null);


    /**
     * 获取<schema.table>的表元信息。
     */
    public function getTableInfo($table, $schema = null);


    /**
     * 获取指定的<schema.table>的所有列元信息。
     */
    public function getAllColumnInfo($table, $schema = null);


    /**
     * 把驱动相关的数据类型转换为驱动无关的通用类型
     */
    public function getBaseType($datatype);


    /**
     * 获取<schema.table>的主键列名
     */
    public function getPrimaryKey($table, $schema = null);


    /**
     * 获取<schema.table>的所有UNIQUE约束的列名数组
     */
    public function getUniqueColumns($table, $schema = null);
}
