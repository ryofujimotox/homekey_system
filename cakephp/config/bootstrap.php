<?php
declare(strict_types=1);

define('RESPONCE_CODE_ERROR', 400);
define('RESPONCE_CODE_SUCCESS', 200);

define('VALID_RANGE_TEXT', 1000);
define('VALID_RANGE_TEL', 13);
define('VALID_RANGE_ZIP', 8);

define('EMAIL_ADDRESS', 'xxxxxxx@ryo1999.com');

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.8
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/*
 * Configure paths required to find CakePHP + general filepath constants
 */
require __DIR__ . DIRECTORY_SEPARATOR . 'paths.php';

/*
 * Bootstrap CakePHP.
 *
 * Does the various bits of setup that CakePHP needs to do.
 * This includes:
 *
 * - Registering the CakePHP autoloader.
 * - Setting the default application paths.
 */
require CORE_PATH . 'config' . DS . 'bootstrap.php';

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Database\Type\StringType;
use Cake\Database\TypeFactory;
use Cake\Datasource\ConnectionManager;
use Cake\Error\ConsoleErrorHandler;
use Cake\Error\ErrorHandler;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;
use Cake\Routing\Router;
use Cake\Utility\Security;
use Cake\Error\ErrorTrap;
use Cake\Error\ExceptionTrap;

/*
 * See https://github.com/josegonzalez/php-dotenv for API details.
 *
 * Uncomment block of code below if you want to use `.env` file during development.
 * You should copy `config/.env.example` to `config/.env` and set/modify the
 * variables as required.
 *
 * The purpose of the .env file is to emulate the presence of the environment
 * variables like they would be present in production.
 *
 * If you use .env files, be careful to not commit them to source control to avoid
 * security risks. See https://github.com/josegonzalez/php-dotenv#general-security-information
 * for more information for recommended practices.
*/
if (!env('APP_NAME') && file_exists(CONFIG . '.env')) {
    $dotenv = new \josegonzalez\Dotenv\Loader([CONFIG . '.env']);
    $dotenv->parse()
        ->putenv()
        ->toEnv()
        ->toServer();
}

//DBやdebugの切替
function get_config_name() {
    // ドメインがない(コンソール処理)場合はドキュメントルートのパスで切り替える。
    if (env('HTTP_HOST')) {
        if (is_included_host(['ryo1999.com'])) {
            return 'app_honban';
        }
    } else {
        if (is_included_docRoot(['var/www'])) {
            return 'app_docker';
        } else {
            return 'app_honban';
        }
    }

    // テスト
    return 'app_docker';
}

/*
 * Read configuration file and inject configuration into various
 * CakePHP classes.
 *
 * By default there is only one configuration file. It is often a good
 * idea to create multiple configuration files, and separate the configuration
 * that changes from configuration that does not. This makes deployment simpler.
 */
try {
    Configure::config('default', new PhpConfig());
    Configure::load('app', 'default', false);

    Configure::load(get_config_name(), 'default');
} catch (\Exception $e) {
    exit($e->getMessage() . "\n");
}

/*
 * When debug = true the metadata cache should only last
 * for a short time.
 */
if (Configure::read('debug')) {
    Configure::write('Cache._cake_model_.duration', '+2 minutes');
    Configure::write('Cache._cake_core_.duration', '+2 minutes');
    // disable router cache during development
    Configure::write('Cache._cake_routes_.duration', '+2 seconds');
}

/*
 * Set the default server timezone. Using UTC makes time calculations / conversions easier.
 * Check http://php.net/manual/en/timezones.php for list of valid timezone strings.
 */
date_default_timezone_set(Configure::read('App.defaultTimezone'));

/*
 * Configure the mbstring extension to use the correct encoding.
 */
mb_internal_encoding(Configure::read('App.encoding'));

/*
 * Set the default locale. This controls how dates, number and currency is
 * formatted and sets the default language to use for translations.
 */
ini_set('intl.default_locale', Configure::read('App.defaultLocale'));

/*
 * Register application error and exception handlers.
 */
$isCli = PHP_SAPI === 'cli';
if ($isCli) {
    (new ErrorTrap(Configure::read('Error')))->register();
} else {
    (new ExceptionTrap(Configure::read('Error')))->register();
}

/*
 * Include the CLI bootstrap overrides.
 */
if ($isCli) {
    require CONFIG . 'bootstrap_cli.php';
}

/*
 * Set the full base URL.
 * This URL is used as the base of all absolute links.
 */
$fullBaseUrl = Configure::read('App.fullBaseUrl');
if (!$fullBaseUrl) {
    /*
     * When using proxies or load balancers, SSL/TLS connections might
     * get terminated before reaching the server. If you trust the proxy,
     * you can enable `$trustProxy` to rely on the `X-Forwarded-Proto`
     * header to determine whether to generate URLs using `https`.
     *
     * See also https://book.cakephp.org/4/en/controllers/request-response.html#trusting-proxy-headers
     */
    $trustProxy = false;

    $s = null;
    if (env('HTTPS') || ($trustProxy && env('HTTP_X_FORWARDED_PROTO') === 'https')) {
        $s = 's';
    }

    $httpHost = env('HTTP_HOST');
    if (isset($httpHost)) {
        $fullBaseUrl = 'http' . $s . '://' . $httpHost;
    }
    unset($httpHost, $s);
}
if ($fullBaseUrl) {
    Router::fullBaseUrl($fullBaseUrl);
}
unset($fullBaseUrl);

Cache::setConfig(Configure::consume('Cache'));
ConnectionManager::setConfig(Configure::consume('Datasources'));
TransportFactory::setConfig(Configure::consume('EmailTransport'));
Mailer::setConfig(Configure::consume('Email'));
Log::setConfig(Configure::consume('Log'));
Security::setSalt(Configure::consume('Security.salt'));

/*
 * Setup detectors for mobile and tablet.
 * If you don't use these checks you can safely remove this code
 * and the mobiledetect package from composer.json.
 */
ServerRequest::addDetector('mobile', function ($request) {
    $detector = new \Detection\MobileDetect();

    return $detector->isMobile();
});
ServerRequest::addDetector('tablet', function ($request) {
    $detector = new \Detection\MobileDetect();

    return $detector->isTablet();
});

/*
 * You can enable default locale format parsing by adding calls
 * to `useLocaleParser()`. This enables the automatic conversion of
 * locale specific date formats. For details see
 * @link https://book.cakephp.org/4/en/core-libraries/internationalization-and-localization.html#parsing-localized-datetime-data
 */
// \Cake\Database\TypeFactory::build('time')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('date')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('datetime')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('timestamp')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('datetimefractional')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('timestampfractional')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('datetimetimezone')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('timestamptimezone')
//    ->useLocaleParser();

// There is no time-specific type in Cake
TypeFactory::map('time', StringType::class);

/*
 * Custom Inflector rules, can be set to correctly pluralize or singularize
 * table, model, controller names or whatever other string is passed to the
 * inflection functions.
 */
//Inflector::rules('plural', ['/^(inflect)or$/i' => '\1ables']);
//Inflector::rules('irregular', ['red' => 'redlings']);
//Inflector::rules('uninflected', ['dontinflectme']);

function is_included_host($targets = array()) {
    foreach ($targets as $target) {
        if (strpos((env('HTTP_HOST') ?? ''), $target) !== false) {
            return true;
        }
    }
    return false;
}

function is_included_docRoot($targets = array()) {
    foreach ($targets as $target) {
        if (strpos((env('SCRIPT_FILENAME') ?? ''), $target) !== false) {
            return true;
        }
        if (strpos(ROOT, $target) !== false) {
            return true;
        }
    }
    return false;
}

class Image {
    /**
     * 画像（バイナリ）のEXif情報を元に回転する
     */
    public function rotateFromBinary($binary) {
        $exif_data = $this->getExifFromBinary($binary);
        if (empty($exif_data['Orientation']) || in_array($exif_data['Orientation'], [1, 2])) {
            return $binary;
        }
        return $this->rotate($binary, $exif_data);
    }

    /**
     * バイナリデータからexif情報を取得
     */
    private function getExifFromBinary($binary) {
        $temp = tmpfile();
        fwrite($temp, $binary);
        fseek($temp, 0);

        $meta_data = stream_get_meta_data($temp);
        $exif_data = @exif_read_data($meta_data['uri']);

        fclose($temp);
        return $exif_data;
    }

    /**
     * 画像を回転させる
     */
    private function rotate($binary, $exif_data) {
        ini_set('memory_limit', '256M');

        $src_image = imagecreatefromstring($binary);

        $degrees = 0;
        $mode = '';
        switch ($exif_data['Orientation']) {
            case 2: // 水平反転
                $mode = IMG_FLIP_VERTICAL;
                break;
            case 3: // 180度回転
                $degrees = 180;
                break;
            case 4: // 垂直反転
                $mode = IMG_FLIP_HORIZONTAL;
                break;
            case 5: // 水平反転、 反時計回りに270回転
                $degrees = 270;
                $mode = IMG_FLIP_VERTICAL;
                break;
            case 6: // 反時計回りに270回転
                $degrees = 270;
                break;
            case 7: // 反時計回りに90度回転（反時計回りに90度回転） 水平反転
                $degrees = 90;
                $mode = IMG_FLIP_VERTICAL;
                break;
            case 8: // 反時計回りに90度回転（反時計回りに90度回転）
                $degrees = 90;
                break;
        }

        if (!empty($mode)) {
            imageflip($src_image, $mode);
        }

        if ($degrees > 0) {
            $src_image = imagerotate($src_image, $degrees, 0);
        }

        ob_start();
        if (empty($exif_data['MimeType']) || $exif_data['MimeType'] == 'image/jpeg') {
            imagejpeg($src_image);
        } elseif ($exif_data['MimeType'] == 'image/png') {
            imagepng($src_image);
        } elseif ($exif_data['MimeType'] == 'image/gif') {
            imagegif($src_image);
        }
        imagedestroy($src_image);
        return ob_get_clean();
    }
}
