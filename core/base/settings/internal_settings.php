<?php

defined('VG_ACCESS') or die('Access denied');

const TEMPLATE = 'templates/default/';
const ADMIN_TEMPLATE = 'core/admin/view/';
const UPLOAD_DIR = 'userfiles/';

const COOKIE_VERSION = '1.0.0';
const CRYPT_KEY = 'TjWmZq4t7w!z%C*FbQeThWmYq3t6w9z$G+KbPeShVmYp3s6v%D*G-KaPdSgVkYp2w!z%C*F-JaNdRgUk3t6w9z$C&F)J@NcRkYp3s6v9y$B&E)H@SgVkXp2s5v8y/B?E'; // ключ шифрования
const COOKIE_TIME = 60; // время бездействия
const BLOCK_TIME = 3; // ВРЕМЯ БЛОКИРОВКИ ПОЛЬЗОВАТЕЛЯ КОТОРЫЙ ПОПЫТАЛСЯ ПОДОБРАТЬ ПАРОЛЬ К САЙТУ.

const QTY = 8;
const QTY_LINKS = 3;

const ADMIN_CSS_JS = [
    'styles' => ['css/main.css'],
    'scripts' => ['js/frameworkfunctions.js', 'js/scripts.js']
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