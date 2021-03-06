<?php

use Phalcon\Loader;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;


/**
 * Shared configuration service
 */
$di->setShared('config', function () {
    return include APP_PATH . "/config/config.php";
});

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->setShared('db', function () {
    $config = $this->getConfig();

    $class = 'Phalcon\Db\Adapter\Pdo\\' . $config->database->adapter;
    $params = [
        'host'     => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname'   => $config->database->dbname,
        'charset'  => $config->database->charset
    ];

    if ($config->database->adapter == 'Postgresql') {
        unset($params['charset']);
    }

    $connection = new $class($params);

    return $connection;
});

/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->setShared('modelsMetadata', function () {
    return new MetaDataAdapter();
});

/**
 * Configure the Volt service for rendering .volt templates
 */
$di->setShared('voltShared', function ($view) {
    $config = $this->getConfig();

    $volt = new VoltEngine($view, $this);
    $volt->setOptions([
        'compiledPath' => function($templatePath) use ($config) {
            // Makes the view path into a portable fragment
            $templateFrag = str_replace($config->application->appDir, '', $templatePath);
            $dirName = dirname($templateFrag);
            if (!is_dir($config->application->cacheDir . 'volt/' . $dirName)) {
                mkdir($config->application->cacheDir . 'volt/' . $dirName, 0777, true);
            }

            return $config->application->cacheDir . 'volt/' . $templateFrag . '.php';
        }
    ]);

    return $volt;
});

$di->setShared("redisShared", function() {
    $redis = new \Redis();
    $redis->connect("127.0.0.1");
    return $redis;
});

$di->setShared("logger", function () {
    $config = $this->getConfig();
    $path = $config->logger->path;
    if (!file_exists($path)) {
        mkdir($path, 0777, TRUE);
    }
    $logger = new Phalcon\Logger\Adapter\File($path . $config->logger->system);
    return $logger;
});