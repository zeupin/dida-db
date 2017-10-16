<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

require(__DIR__ . '/Autoloader.php');
Autoloader::init();
Autoloader::addPsr4('Dida\\Db', __DIR__ . '/../src/Db');

require(__DIR__ . '/../../composer/vendor/autoload.php');

