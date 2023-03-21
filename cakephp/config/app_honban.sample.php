<?php
/*
 * Local configuration file to provide any overrides to your app.php configuration.
 * Copy and save this file as app_local.php and make changes as required.
 * Note: It is not recommended to commit files with credentials such as app_local.php
 * into source code version control.
 */
return [
    'debug' => filter_var(env('DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'Security' => [
        'salt' => env('SECURITY_SALT'),
    ],
    'Datasources' => [
        'default' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'persistent' => false,
            'host' => 'localhost',

            'username' => 'xxxxxxxxxx',
            'password' => 'xxxxxxxxxx',
            'database' => 'xxxxxxxxxx',

            'encoding' => 'utf8mb4',
            'timezone' => '+09:00',
            'cacheMetadata' => true,
            'quoteIdentifiers' => false,
            'log' => false,

            //
            'unix_socket' => '/var/lib/mysql/mysql.sock'
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
