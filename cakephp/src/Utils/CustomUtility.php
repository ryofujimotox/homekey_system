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

class CustomUtility {
    // いい感じにタグ無くす
    public static function rip_tags(string $string) {
        // ----- remove HTML TAGs -----
        $string = preg_replace('/<[^>]*>/', ' ', $string);

        // ----- remove control characters -----
        $string = str_replace("\r", '', $string);    // --- replace with empty space
        $string = str_replace("\n", ' ', $string);   // --- replace with space
        $string = str_replace("\t", ' ', $string);   // --- replace with space

        // ----- remove multiple spaces -----
        $string = trim(preg_replace('/[ 　]{2,}/u', ' ', $string));

        return $string;
    }

    /**
     *
     * スペースによる文字列の絞り込み検索に対応したSQLを返す。
     *
     * @param needles = [ [ モデル名 => [ カラム名, カラム名 ] ], [ モデル名 => [ カラム名, カラム名 ] ] ]
     * @param needles = [ モデル名.カラム名, モデル名.カラム名, モデル名.カラム名,  ]
     * @param sch_keyword =　検索する文字列
     *
     */
    public static function getConditionKeyword($needs, $sch_keyword) {
        $sch_keywords = self::multi_explode(array(' ', '　'), $sch_keyword);

        $cond = [];
        foreach ($sch_keywords as $keyword) {
            $keyword = mb_convert_kana($keyword, 'KV');

            $ors = [];
            foreach ($needs as $model => $needle) {
                if (is_array($needle)) {
                    foreach ($needle as $key) {
                        $command = $model . '.' . $key;
                        $ors[] = 'convert(' . $command . " using utf8) collate utf8_unicode_ci like '%" . $keyword . "%'";
                    }
                } else {
                    $command = $needle;
                    $ors[] = 'convert(' . $command . " using utf8) collate utf8_unicode_ci like '%" . $keyword . "%'";
                }
            }

            $cond[] = ['OR' => $ors];
        }
        return $cond;
    }

    /**
     *
     * 処理時間計測する
     *
     */
    public static function CheckSpeed($func) {
        $time_start = microtime(true); //実行開始時間を記録する
        $cnt = 1;
        $time = 0;
        foreach (range(1, $cnt) as $cmt) {
            $func();

            $time_end = microtime(true);
            $time += $time_end - $time_start;
        }
        var_dump($time / $cnt); //実行時間を出力する
        exit;
    }

    // 改行込みでimplode
    public static function implode_n($texts = []) {
        return implode("\n", $texts);
    }

    public function getExtension($filename) {
        return strtolower(substr(strrchr($filename, '.'), 1));
    }

    public function getFileName($filename, $ext) {
        return str_replace('.' . $ext, '', $filename);
    }

    public function getToken($length = 8) {
        return substr(bin2hex(random_bytes($length)), 0, $length);
    }

    /**
     *
     * 削除処理
     *
     */
    const DELETE_CODE_ERROR = 'error';
    const DELETE_CODE_SUCCESS = 'success';
    public static function getDeletedMessage($code) {
        if ($code == self::DELETE_CODE_ERROR) {
            return '削除できませんでした。';
        }
        if ($code == self::DELETE_CODE_SUCCESS) {
            return '削除しました。';
        }
        return self::throwError(200, '未設定');
    }
    public static function getDeletedResult($code, $user = []) {
        $message = self::getDeletedMessage($code);
        if ($code == self::DELETE_CODE_SUCCESS) {
            return ['code' => $code, 'message' => $message, 'result' => 'success', 'datas' => []];
        } else {
            return ['code' => $code, 'message' => $message, 'result' => 'error', 'datas' => []];
        }
    }

    // Limitリスト
    public static $page_limit_list = [
        //'1' => '1件',
        '10' => '10件',
        //'50' => '50件',
        '100' => '100件',
        '200' => '200件',
        // "all" => '全て'
    ];

    //連想配列を必要なキーのみで再構成する。
    public static function reduceDir($dir = [], $keys = []) {
        return ArrayUtility::reduceDir($dir, $keys);
    }

    //連想配列の全ての鍵を値にする。
    public static function val2key($value) {
        return ArrayUtility::val2key($value);
    }

    //指定したindexのみで再構成する。
    public static function searchMultiIndex($needleIndex, $array) {
        return ArrayUtility::searchMultiIndex($needleIndex, $array);
    }

    /**
     *
     * 指定した配列鍵に対する鍵をもつ配列をすべて削除する。
     *
     * @param excludes = [ 削除する鍵 ]
     * @param dic = [ 削除する鍵 => value, 残す鍵 => value2 ]
     *
     * @return Dictionary = [ 残す鍵 => value2 ]
     */
    public static function exclude_dic_keys($excludes, $dic) {
        return ArrayUtility::exclude_dic_keys($excludes, $dic);
    }

    /**
    *
    * 指定したテキストをキーとする配列をすべて取得し平坦化する。
    * @param array = [test => [1], test2 => [ test => [2, 3] ]]
    * @param targetKey = "test"
    *
    * @return Array = [ [1], [2, 3] ]
    *
    */
    public static function array_choice($array, $targetKey) {
        return ArrayUtility::array_choice($array, $targetKey);
    }

    /**
     *
     * array_mampと同じ挙動。　関数の第二引数をキーにする。
     *
     * @param func = array_mapに渡す関数
     * @param array = array_mapに渡す配列
     * @param need_flatten = 取得結果を平坦化する。
     *
     */
    public static function array_keymap($func, $array, $need_flatten = true) {
        return ArrayUtility::array_choice($func, $array, $need_flatten);
    }

    /**
     *
     * 配列の子要素だけで再構成
     * @param val = [ [], [num1], [], [[num2]], [] ]
     *
     * @return Array = [num1, [num2]]
     *
     */
    public static function array_flatten($array) {
        return ArrayUtility::array_flatten($array);
    }

    //250文字ごとに改行する。　必ず最後に改行が付与される。
    public static function _preventGarbledCharacters($bigText, $width = 249) {
        $pattern = "/(.{1,{$width}})(?:\\s|$)|(.{{$width}})/uS";
        $replace = '$1$2' . "\n";
        $wrappedText = preg_replace($pattern, $replace, $bigText);
        return $wrappedText;
    }

    //大文字小文字数字を含めたトークン
    public static function getRandomStr($length = 20) {
        $result = '';
        $str = array_merge(range('a', 'z'), range('A', 'Z"'), range('0', '9'));
        for ($i = 0; $i < $length; $i++) {
            $result .= $str[rand(0, count($str) - 1)];
        }
        return $result;
    }

    //ひらがなから、あかさたなを算出する
    public static function getHiraganaAKSTN($hira) {
        $kana = array(
            'あ' => '[あ-お]',
            'か' => '[か-こが-ご]',
            'さ' => '[さ-そざ-ぞ]',
            'た' => '[た-とだ-ど]',
            'な' => '[な-の]',
            'は' => '[は-ほば-ぼぱ-ぽ]',
            'ま' => '[ま-も]',
            'や' => '[や-よ]',
            'ら' => '[ら-ろ]',
            'わ' => '[わ-ん]',
            '他' => '.*'
        );
        foreach ($kana as $initial => $pattern) {
            if (preg_match('/^' . $pattern . '/u', $hira)) {
                return $initial;
            }
        }
        return '';
    }

    /**
     *
     * explodeの分割文字を複数文字に対応。
     * 分割する文字を配列に
     *
     * @param delimiters => [ 分割する文字, 分割する文字 ]
     * @param str => 分割にする対象の文字列
     *
     */
    public static function multi_explode($delimiters, $str) {
        $array = array($str);
        foreach ($delimiters as $value1) {
            $return = array();
            foreach ($array as $key => $value2) {
                $return = array_merge($return, explode($value1, $value2));
            }
            $array = $return;
        }
        return $array;
    }

    //セッションデータの取得
    public static function getSessionData($key = '', $dir = '') {
        $data = $_SESSION ?? [];
        if ($dir) {
            $data = $data[$dir] ?? [];
        }

        if ($key) {
            if ($key == 'id') {
                return $data[$key] ?? 0;
            } else {
                return $data[$key] ?? '';
            }
        }

        return $data;
    }

    //エラーリクエスト
    public static function throwError($code = '', $message = '') {
        $http_status = 200;

        $state_list = array(
            [
                'code' => 200,
                'title' => 'empty',
                'content' => "Cake\Http\Exception\ForbiddenException"
            ],
            [ // タイプミス等、リクエストにエラーがあります。
                'code' => 400,
                'title' => 'Bad Request',
                'content' => 'Cake\Http\Exception\BadRequestException'
            ],

            //ユーザー登録させる
            [// 認証に失敗しました。（パスワードを適当に入れてみた時などに発生）
                'code' => 401,
                'title' => 'Unauthorixed',
                'content' => 'Cake\Http\Exception\UnauthorizedException'
            ],

            // [//使ってない
            //     'code' => 402,
            //     'title' => 'empty',
            //     'content' => 'ForbiddenException'
            // ],
            [
                'code' => 403, // あなたにはアクセス権がありません。
                'title' => 'Forbidden',
                'content' => 'Cake\Http\Exception\ForbiddenException'
            ],
            [
                'code' => 404, // 該当アドレスのページはありません、またはそのサーバーが落ちている。
                'title' => 'Not Found',
                'content' => 'Cake\Http\Exception\NotFoundException'
            ],
            [
                'code' => 500, // CGIスクリプトなどでエラーが出た。
                'title' => 'Internal Server Error',
                'content' => 'Cake\Http\Exception\InternalErrorException'
            ],
            [
                'code' => 501, // リクエストを実行するための必要な機能をサポートしていない。
                'title' => 'Not Implemented',
                'content' => 'Cake\Http\Exception\NotImplementedException'
            ],
            [
                'code' => 509, // オリジナルコード　例外処理
                'title' => 'Other',
                'content' => 'Cake\Http\Exception\ServiceUnavailableException'
            ],
        );
        $state_list = Hash::combine($state_list, '{n}.code', '{n}');

        $code2messages = array(
            '1000' => 'パラメーターエラー',
            '1001' => 'パラメーターエラー',
            '1002' => 'パラメーターエラー',
            '2000' => '取得データがありませんでした',
            '2001' => '取得データがありませんでした',
            '9000' => '認証に失敗しました',
            '9001' => '',
        );

        $error = $state_list[$code] ?? '';
        if (!$error) {
            self::throwError(404, 'エラー');
        }

        $error_cotent = $error['content'] ?? '';
        $errorClass = new $error_cotent($message);
        throw $errorClass;
    }

    /**
     *
     * URLまわり
     *
     */
    public static function nowUrl() {
        return ManageUrl::nowUrl();
    }

    public static function getUrlData($url = '') {
        return ManageUrl::getUrlData($url);
    }

    public static function deleteUrlQuery($url = '', $deleteQueryKey = []) {
        return ManageUrl::deleteUrlQuery($url, $deleteQueryKey);
    }

    public static function addUrlQuery($url = '', $addQueryData = []) {
        return ManageUrl::addUrlQuery($url, $addQueryData);
    }

    /**
     *
     * ログイン情報まわり
     *
     */
    //ログインしたアカウントの情報
    public static function getSessionLogined($key = '') {
        return self::getSessionData($key, 'logined');
    }
    //ログインした時の権限
    public static function getSessionRole() {
        return self::getSessionData('role');
    }
    //ログインまたは選択した学校の情報
    public static function getSessionManageSchool() {
        return self::getSessionData('manage_school');
    }

    /**
     *
     * メールまわり
     *
     */
    public static function _sendmail($post_data, $settings) {
        // const settings = [
        //     'test' => [//テスト用
        //         'sendmail' => true, //falseなら送らない
        //         'from' => 'test+from@caters.co.jp', //送信元
        //         'to_admin' => 'test+to@caters.co.jp', //送信先(管理者のメールアドレス
        //         'name' => 'カテル', //送信者
        //         'subject_admin' => '【テスト】お問い合わせがありました', //管理者へのメールタイトル
        //         'subject_user' => '【テスト】お問い合わせを受け付けました', //ユーザーへのメールタイトル
        //         'template_admin' => 'default_admin', //管理者へのメールテンプレ
        //         'template_user' => 'default_user'//ユーザーへのメールテンプレ
        //     ],
        //     'honban' => [//本番用
        //         'sendmail' => true,
        //         'from' => 'test+from@caters.co.jp',
        //         'to_admin' => 'test+to@caters.co.jp',
        //         'name' => 'カテル',
        //         'subject_admin' => '【本番】お問い合わせがありました',
        //         'subject_user' => '【本番】お問い合わせを受け付けました',
        //         'template_admin' => 'default_admin',
        //         'template_user' => 'default_user'
        //     ]
        // ];
        return EmailUtility::_sendmail($post_data, $settings);
    }

    /**
     *
     * 日付まわり
     *
     */
    //様々な形式の日付をdate型にして返す。
    public static function getDate($date = '', $format = '') {
        return DateUtility::getDate($date, $format);
    }

    //その日の00時を返す。
    public static function getStartDateTime($date = null) {
        return DateUtility::getStartDateTime($date);
    }

    //その月の初日
    public static function getMonthFirstDay($date, $format = 'Y-m-d') {
        return DateUtility::getMonthFirstDay($date, $format);
    }

    //その月の末日
    public static function getMonthLastDay($date, $format = 'Y-m-d') {
        return DateUtility::getMonthLastDay($date, $format);
    }

    public static function getNow($format = 'Y-m-d H:i') {
        return DateUtility::getDate('', $format);
    }

    public static function getDateJP($date = '') {
        return DateUtility::getDate($date, 'jp');
    }

    public static function nextDay($date) {
        return DateUtility::nextDay($date);
    }

    /**
     *
     * IP、サーバー情報まわり
     *
     */
    //サブネットマスク後のIPを確認
    public static function isExternalIP() {
        return ManageServerIp::isExternalIP();
    }

    //IPアドレスの取得
    public static function getIp() {
        return ManageServerIp::getIp();
    }

    //リファラーの取得
    public static function getReferrer() {
        return ManageServerIp::getReferrer();
    }

    /**
     *
     * 都道府県関数
     *
     */
    public static function getPrefList($needle = null) {
        return PrefectureUtility::getPrefList($needle);
    }

    public static function getPrefData($needs = null) {
        return PrefectureUtility::getPrefData($needs);
    }

    public static function getAreaName($pref_slug) {
        return PrefectureUtility::getAreaName($pref_slug);
    }

    public static function isPrefSlug($pref_slug) {
        return (bool) PrefectureUtility::getAreaName($pref_slug);
    }

    public static function getJpAreaList($need_pref_slug = null) {
        return PrefectureUtility::getJpAreaList($need_pref_slug);
    }

    public static function getAreaPrefOptgroupList($needs = null) {
        return PrefectureUtility::getAreaPrefOptgroupList($needs);
    }

    /**
     *
     * その他
     *
     */

    //SomeImageTable と RegistFileAttacheBehaviorで使う
    public static function convertPath() {
        $config_name = get_config_name();
        if ($config_name == 'app_docker') {
            return '/usr/bin/convert';
        }

        $is_local = (strpos(env('HTTP_HOST'), 'localhost') !== false);
        if ($is_local) {
            return '/usr/local/bin/convert';
        } else {
            return '/usr/bin/convert';
        }
    }
}

/**
 *
 * サーバー情報まわり
 *
 */
class ManageServerIp {
    //サブネットマスク後のIPを確認
    public static function isExternalIP() {
        $remote_ip = self::getIp();
        $dokkyo_accepts = [
            // '202.250.238.0/23',
            // '202.209.203.88/29',
            // '114.160.88.0/27',

            //カテル用
            //ルート　192.168.1.1
            '121.2.64.195',
            //ルート　192.168.1.2
            '39.110.198.114',
            //ルート192.168.1.3
            //"121.101.85.59"
        ];
        foreach ($dokkyo_accepts as $accept) {
            $address = explode('/', $accept);
            if (isset($address[1])) {
                list($accept_ip, $mask) = $address;
                $accept_long = ip2long($accept_ip) >> (32 - $mask);
                $remote_long = ip2long($remote_ip) >> (32 - $mask);
                if ($accept_long == $remote_long) {
                    return false;
                }
            } else {
                $address = $address[0];
                if ($address == $remote_ip) {
                    return false;
                }
            }
        }
        return true;
    }

    //IPアドレスの取得
    public static function getIp() {
        $ip = '';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipArray = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = $ipArray[0];
        }
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    //リファラーの取得
    public static function getReferrer() {
        $referer = '';
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
        }
        return $referer;
    }
}

/**
 *
 * URLまわり
 *
 */
class ManageUrl {
    public static function nowUrl() {
        return (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    //queryやクリアURLを配列で取得する
    public static function getUrlData($url = '') {
        $data = parse_url($url ? $url : self::nowUrl());

        if (!isset($data['scheme']) || !isset($data['host'])) {
            return '';
        }

        $url = $data['scheme'] . '://' . $data['host'] . $data['path'];
        $data['base_url'] = $url;

        //queryを配列にする
        parse_str(html_entity_decode($data['query'] ?? ''), $query);
        $data['queries'] = $query;

        return $data;
    }

    //queryを削除したurlを取得する
    public static function deleteUrlQuery($url = '', $deleteQueryKey = []) {
        $url_data = self::getUrlData($url);
        if (!$url_data) {
            return '';
        }
        extract($url_data);

        if (!$queries) {
            return $base_url;
        }
        foreach ($deleteQueryKey as $key) {
            unset($queries[$key]);
        }
        $query_url = http_build_query($queries);

        return $base_url . '?' . $query_url;
    }

    //queryを追加したurlを取得する
    public static function addUrlQuery($url = '', $addQueryData = []) {
        extract(self::getUrlData($url));
        if (!$queries && !$addQueryData) {
            return $base_url;
        }
        $queries = array_merge($queries, $addQueryData);
        $query_url = http_build_query($queries);
        return $base_url . '?' . $query_url;
    }
}
