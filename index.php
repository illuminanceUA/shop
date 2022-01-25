<?php

define('VG_ACCESS', true); // константа

header('Content-Type:text/html;charset=utf-8'); // отправка заголовка

session_start();

require_once 'config.php';
require_once 'core/base/settings/internal_settings.php'; // доп настройки
require_once 'libraries/functions.php';

use core\base\exceptions\RouteException;
use core\base\controller\RouteController;
use core\base\exceptions\DbException;


$s = \core\base\settings\Settings::instance();
$s1 = \core\base\settings\ShopSettings::instance();

exit();

try {
   RouteController::instance()->route();
}
catch (RouteException $e) {
   exit($e->getMessage());
}
catch (DbException $e) {
    exit($e->getMessage());
}


