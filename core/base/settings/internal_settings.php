<?php

defined('VG_ACCESS') or die('Access denied');

const TEMPLATE = 'templates/default/';
const ADMIN_TEMPLATE = 'core/admin/view/';

const COOKIE_VERSION = '1.0.0';
const CRYPT_KEY = ''; // ключ шифрования
const COOKIE_TIME = 60; // время бездействия
const BLOCK_TIME = 3; // ВРЕМЯ БЛОКИРОВКИ ПОЛЬЗОВАТЕЛЯ КОТОРЫЙ ПОПЫТАЛСЯ ПОДОБРАТЬ ПАРОЛЬ К САЙТУ.

const QTY = 8;
const QTY_LINKS = 3;

const ADMIN_CSS_JS = [
    'styles' => [],
    'scripts' => []
];

const USER_CSS_JS = [
    'styles' => [],
    'scripts' => []
];

use core\base\exceptions\RouteException;

function autoloadMainClasses($className) {

     $className = str_replace( '\\',  '/', $className);

     if(!@include_once $className . '.php') {
        throw new RouteException('Не верное имя файла для подключения - '. $className);
     }

}
spl_autoload_register('autoloadMainClasses');