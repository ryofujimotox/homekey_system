<?php
namespace App\Utils;

use Cake\ORM\TableRegistry;
// use Cake\Utility\Hash;
//use App\Model\Entity\DokkyoLiteral;
use Cake\I18n\FrozenTime;
use Cake\I18n\Date;
use Cake\Utility\Hash;
//mail
use Cake\Mailer\Email;
use Cake\Core\Configure;
use Cake\Routing\Router;
//Utility
use App\Utils\PrefectureUtility;
use App\Utils\DateUtility;
use App\Utils\EmailUtility;
use App\Utils\ArrayUtility;
use Cake\Utility\Text;

class FileUtility {
    // ファイル削除
    public static function deleteByPath($path) {
        if ($path && is_file(WWW_ROOT . $path)) {
            @unlink(WWW_ROOT . $path);
        }
    }
}
