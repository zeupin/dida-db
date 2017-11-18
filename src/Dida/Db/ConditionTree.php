<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License.
 * Redistributions of files MUST retain the above copyright notice.
 */

namespace Dida\Db;

class ConditionTree
{
    const VERSION = '20171113';

    public $name = null;

    public $logic = 'AND';

    public $items = [];


    public function __construct($logic = 'AND', $name = null)
    {
        $this->logic = $logic;
        $this->name = $name;
    }


    public function getNamedDictionary(array &$dict)
    {
        if (is_string($this->name)) {
            $dict[$this->name] = &$this;
        }

        foreach ($this->items as $key => $item) {
            if ($item instanceof ConditionTree) {
                $this->items[$key]->getNamedDictionary($dict);
            }
        }
    }
}
