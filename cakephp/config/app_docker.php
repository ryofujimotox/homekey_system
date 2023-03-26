<?php
/*
 * Local configuration file to provide any overrides to your app.php configuration.
 * Copy and save this file as app_local.php and make changes as required.
 * Note: It is not recommended to commit files with credentials such as app_local.php
 * into source code version control.
 */
return [
    'debug' => filter_var(env('DEBUG', true), FILTER_VALIDATE_BOOLEAN),
    'Security' => [
        'salt' => env('SECURITY_SALT'),
    ],
    'Datasources' => [
        'default' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'persistent' => false,
            'host' => 'db',

            'username' => 'testuser',
            'password' => 'testpw',
            'database' => 'testdb',

            'encoding' => 'utf8mb4',
            'timezone' => '+09:00',
            'cacheMetadata' => true,
            'quoteIdentifiers' => false,
            'log' => false,
            // 'unix_socket' => '/Applications/MAMP/tmp/mysql/mysql.sock'
        ],

        'test' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'persistent' => false,
            'host' => 'db',
            // 'host' => '153.126.185.238',
            // 'port' => '3306',

            'username' => 'testuser',
            'password' => 'testpw',
            'database' => 'testdb',

            'encoding' => 'utf8mb4',
            'timezone' => '+09:00',
            'cacheMetadata' => true,
            'quoteIdentifiers' => false,
            'log' => false,
            // 'url' => env('DATABASE_TEST_URL', null),

            //ローカル環境がMAMPの場合
            // 'unix_socket' => '/Applications/MAMP/tmp/mysql/mysql.sock'
        ],
    ],

    /*
     * Email configuration.
     *
     * Host and credential configuration in case you are using SmtpTransport
     *
     * See app.php for more configuration options.
     */
    'EmailTransport' => [
        'default' => [
            'className' => 'Mail',
            'host' => 'localhost',
            'port' => 25,
            'username' => null,
            'password' => null,
            'client' => null,
            'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
        ],
    ],
];
