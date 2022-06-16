<?php
/** Имя сервера MySQL */
define('DB_HOST', $_SERVER['AMZ_DB_HOST']);

define('DB_NAME', $_SERVER['AMZ_DB_NAME']);

/** Имя пользователя MySQL */
define('DB_USER', $_SERVER['AMZ_DB_USER']);

/** Пароль к базе данных MySQL */
define('DB_PASSWORD', $_SERVER['AMZ_DB_PASS']);

/** Кодировка базы данных для создания таблиц. */
define('DB_CHARSET', 'utf8mb4');

/** Схема сопоставления. Не меняйте, если не уверены. */
define('DB_COLLATE', '');

define('DB_LOG_FILE_NAME', 'prod_tmpslog.txt');