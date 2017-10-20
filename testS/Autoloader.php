<?php

/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */
class Autoloader
{
    private static $_initialized = false;
    private static $_queue = [];


    /**
     * Initialization
     */
    public static function init()
    {
        // only run once
        if (self::$_initialized) {
            return;
        }

        // register the autoload() callback
        spl_autoload_register([__CLASS__, 'autoload'
        ]);

        // set the flag
        self::$_initialized = true;
    }


    /**
     * Checks the queue, autoloads if matched one.
     *
     * @param string $FQCN
     *            Fully Qualified Class Name.
     */
    protected static function autoload($FQCN)
    {
        foreach (self::$_queue as $item) {
            switch ($item['type']) {
                case 'classmap':
                    $result = self::matchClassmap($FQCN, $item['mapfile'], $item['basedir'], $item['map']);
                    if ($result) {
                        return true;
                    }
                    break;

                case 'namespace':
                    $result = self::matchNamespace($FQCN, $item['namespace'], $item['basedir'], $item['len']);
                    if ($result) {
                        return true;
                    }
                    break;

                case 'psr4':
                    $result = self::matchPsr4($FQCN, $item['namespace'], $item['basedir'], $item['len']);
                    if ($result) {
                        return true;
                    }
                    break;

                case 'psr0':
                    $result = self::matchPsr0($FQCN, $item['namespace'], $item['basedir'], $item['len']);
                    if ($result) {
                        return true;
                    }
                    break;

                case 'alias':
                    $result = self::matchAlias($FQCN, $item['alias'], $item['real']);
                    if ($result) {
                        return true;
                    }
                    break;
            }
        }

        // Not matched anyone, return false
        return false;
    }


    /**
     * Adds a PSR-4 namespace
     *
     * @param string $namespace
     *            Namespace. such as 'your\\namespace'
     * @param string $basedir
     *            BaseDir. such as '/your/namespace/base/directory/'
     *
     * @return bool
     */
    public static function addPsr4($namespace, $basedir)
    {
        // Initialize
        self::init();

        // Checks $basedir
        if (!file_exists($basedir) || !is_dir($basedir)) {
            return false;
        } else {
            $basedir = realpath($basedir);
        }

        // Preproccesses $namepsace
        $namespace = trim($namespace, " \\\t\n\r\0\x0B");

        // Adds it to $_queue
        self::$_queue[] = ['type'      => 'psr4', 'namespace' => $namespace, 'basedir'   => $basedir, 'len'       => strlen($namespace)
        ];

        return true;
    }


    /**
     * Matches a PSR-4 namespace
     */
    private static function matchPsr4($FQCN, $namespace, $basedir, $len)
    {
        // Checks if the prefix is matched.
        if (strncmp($FQCN, $namespace . '\\', $len + 1) !== 0) {
            return false;
        }

        // Strips the namespace
        $rest = substr($FQCN, $len + 1);

        // Checks if the target php file exists.
        $target = "{$basedir}/{$rest}.php";
        if (file_exists($target) && is_file($target)) {
            require $target;
            return true;
        } else {
            return false;
        }
    }


    /**
     * Adds a PSR-0 namespace
     *
     * @param string $namespace
     *            Namespace. such as 'your\\namespace'
     * @param string $basedir
     *            BaseDir. such as '/your/namespace/base/directory/'
     *
     * @return bool
     */
    public static function addPsr0($namespace, $basedir)
    {
        // Initialize
        self::init();

        // Checks $basedir
        if (!file_exists($basedir) || !is_dir($basedir)) {
            return false;
        } else {
            $basedir = realpath($basedir);
        }

        // Preproccesses $namepsace
        $namespace = trim($namespace, " \\\t\n\r\0\x0B");

        // Adds it to $_queue
        self::$_queue[] = ['type'      => 'psr0', 'namespace' => $namespace, 'basedir'   => $basedir, 'len'       => strlen($namespace)
        ];

        return true;
    }


    /**
     * Matches a PSR-0 namespace
     */
    private static function matchPsr0($FQCN, $namespace, $basedir, $len)
    {
        // Checks if the prefix is matched.
        if (strncmp($FQCN, $namespace . '\\', $len + 1) !== 0) {
            return false;
        }

        // Strips the namespace
        $rest = substr($FQCN, $len + 1);

        // deal with '_' in the rest
        $rest = str_replace('_', DIRECTORY_SEPARATOR, $rest);

        // Checks if the target php file exists.
        if ($namespace === '') {
            $target = "{$basedir}/{$rest}.php";
        } else {
            $target = "{$basedir}/{$namespace}/{$rest}.php";
        }

        if (file_exists($target) && is_file($target)) {
            require $target;
            return true;
        } else {
            return false;
        }
    }


    /**
     * Adds a namespace.
     *
     * If try to match the \Namepsace\Your\Cool\Class,
     * it will check:
     * <basedir>/Your/Cool/Class.php
     * <basedir>/Your/Cool/Class/Class.php
     *
     * @param string $namespace
     *            Namespace. such as 'your\\namespace'
     * @param string $basedir
     *            BaseDir. such as '/your/namespace/base/directory/'
     *
     * @return bool
     */
    public static function addNamespace($namespace, $basedir)
    {
        // Initialize
        self::init();

        // Checks $basedir
        if (!file_exists($basedir) || !is_dir($basedir)) {
            return false;
        } else {
            $basedir = realpath($basedir);
        }

        // Preproccesses $namepsace
        $namespace = trim($namespace, " \\\t\n\r\0\x0B");

        // Adds it to $_queue
        self::$_queue[] = ['type'      => 'namespace', 'namespace' => $namespace, 'basedir'   => $basedir, 'len'       => strlen($namespace)
        ];

        return true;
    }


    /**
     * Matches a namespace
     */
    private static function matchNamespace($FQCN, $namespace, $basedir, $len)
    {
        // Checks if the prefix is matched.
        if (strncmp($FQCN, $namespace . '\\', $len + 1) !== 0) {
            return false;
        }

        // Strips the namespace
        $rest = substr($FQCN, $len + 1);

        // Checks if the target php file exists.
        $target = "$basedir/$rest.php";
        if (file_exists($target) && is_file($target)) {
            require $target;
            return true;
        }

        // If $rest not contain '\'
        if (strpos($rest, '\\') === false) {
            $target = "{$basedir}/{$rest}/{$rest}.php";
            if (file_exists($target) && is_file($target)) {
                require $target;
                return true;
            } else {
                return false;
            }
        }

        // If $rest contains '\', split $rest to $base + $name, then checks files exist.
        $array = explode('\\', $rest);
        $name = array_pop($array);
        $base = implode('/', $array);
        $target1 = "{$basedir}/{$base}/{$name}.php";
        $target2 = "{$basedir}/{$base}/{$name}/{$name}.php";
        if (file_exists($target1) && is_file($target1)) {
            require $target1;
            return true;
        } elseif (file_exists($target2) && is_file($target2)) {
            require $target2;
            return true;
        } else {
            return false;
        }
    }


    /**
     * Adds a class map file
     *
     * @param string $mapfile
     *            The real path of the class map file.
     * @param string $basedir
     *            The base directory. default is the mapfile's directory.
     *
     * @return bool
     */
    public static function addClassmap($mapfile, $basedir = null)
    {
        // Initialize
        self::init();

        // Checks $mapfile
        if (!file_exists($mapfile) || !is_file($mapfile)) {
            return false;
        } else {
            $mapfile = realpath($mapfile);
        }

        // Checks $basedir
        if (is_null($basedir)) {
            $basedir = dirname($mapfile);
        } elseif (!is_string($basedir) || !file_exists($basedir) || !is_dir($basedir)) {
            return false;
        } else {
            $basedir = realpath($basedir);
        }

        // Adds it to $_queue
        self::$_queue[] = ['type'    => 'classmap', 'mapfile' => $mapfile, 'basedir' => $basedir, 'map'     => null
        ];

        return true;
    }


    /**
     * Matches FQCN from the map file
     */
    private static function matchClassmap($FQCN, $mapfile, $basedir, &$map)
    {
        // If first run, loads the mapfile content to $map.
        if (is_null($map)) {
            $map = require($mapfile);

            // Checks $map, sets it to [] if invalid.
            if (!is_array($map)) {
                $map = [];
                return false;
            }
        }

        // Checks if $map is empty.
        if (empty($map)) {
            return false;
        }

        // Checks if FQCN exists.
        if (!array_key_exists($FQCN, $map)) {
            return false;
        }

        // Loads the target file.
        $target = $basedir . '/' . $map[$FQCN];
        if (file_exists($target) && is_file($target)) {
            require $target;
            return true;
        } else {
            return false;
        }
    }


    /**
     * Add an alias
     *
     * @param string $alias
     *            Your\Class\Alias
     * @param string $real
     *            \Its\Real\FQCN
     *
     * @return bool
     */
    public static function addAlias($alias, $real)
    {
        // Initialize
        self::init();

        // Adds it to $_queue
        self::$_queue[] = ['type'  => 'alias', 'alias' => $alias, 'real'  => $real
        ];

        return true;
    }


    /**
     * Matches an alias
     */
    private static function matchAlias($FQCN, $alias, $real)
    {
        if ($FQCN === $alias) {
            return class_alias($real, $alias);
        } else {
            return false;
        }
    }
}
