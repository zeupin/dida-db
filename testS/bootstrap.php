<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

require(__DIR__ . '/Autoloader.php');
Autoloader::init();
Autoloader::addPsr4('Dida\\', __DIR__ . '/../src/Dida');

require(__DIR__ . '/../../composer/vendor/autoload.php');
