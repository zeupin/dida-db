<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db\Builder;

/**
 * Common Trait
 */
trait CommonTrait
{
    /**
     * @var \Dida\Db\Db
     */
    protected $db = null;

    /**
     * @var int
     */
    protected $pdo_default_fetch_mode = null;
}
