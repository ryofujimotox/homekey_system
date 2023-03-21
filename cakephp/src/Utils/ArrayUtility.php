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

class ArrayUtility {
    /**
     *
     *
     *
     */
    public function array_find($arry, $func) {
        $filters = array_values(array_filter($arry, $func));
        return $filters[0] ?? [];
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
        return array_filter($dic, function ($k) use ($excludes) {
            return !in_array($k, $excludes, true);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     *
     * 指定したindexのみで再構成する。
     *
     * @param needleIndex = [0, 2]
     * @param array = [test, test1, test2]
     *
     * @return Array = [test, test2]
     *
     */
    public static function searchMultiIndex($needleIndex, $array) {
        return array_filter(
            array_map(function ($index, $_arr) use ($needleIndex) {
                return in_array($index, $needleIndex) ? $_arr : [];
            }, array_keys($array), $array)
        );
    }

    //連想配列を必要なキーのみで再構成する。
    public static function reduceDir($dir = [], $keys = []) {
        // $dir = [
        //     'aomori' => '青森県',
        //     'iwate' => '岩手県',
        //     'akita' => '秋田県',
        //     'miyagi' => '宮城県',
        //     'yamagata' => '山形県',
        //     'fukushima' => '福島県',
        // ];
        // $keys = [
        //     'aomori',
        //     'fukushima'
        // ];
        // return [
        //     'aomori' => '青森県',
        //     'fukushima' => '福島県',
        // ];
        $result = [];
        foreach ($dir as $key => $value) {
            if (in_array($key, $keys)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    //連想配列の全ての鍵を値にする。
    public static function val2key($value) {
        return array_combine($value, $value);
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
        $getModelData = function ($key, $data) use ($targetKey, &$getModelData) {
            if ($key === $targetKey) {
                return $data;
            }
            if (is_array($data)) {
                return self::array_keymap($getModelData, $data);
            }
        };
        return self::array_keymap($getModelData, $array);
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
        $ans = array_map($func, array_keys($array), $array);
        if ($need_flatten) {
            $ans = self::array_flatten($ans);
        } else {
            $ans = $ans;
        }
        return $ans;
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
        $tmp = [];
        foreach ($array as $key => $data) {
            if ($data) {
                if (is_array($data)) {
                    $tmp = array_merge($tmp, $data);
                } else {
                    $tmp[] = $data;
                }
            }
        }
        return $tmp;
    }
}
