<?php
/**!
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

/**
 * Builder Interface
 */
interface BuilderInterface
{
    /**
     * Builds the final SQL statement from $todolist array.
     *
     * @param array $todolist
     */
    public function build(&$todolist);
}
