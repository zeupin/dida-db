<?php
/**
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
     * Builds the final SQL statement from $tasklist array.
     *
     * @param array $tasklist
     */
    public function build(&$tasklist);
}
