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

/**
 *
 * 都道府県周りのデータを管理
 *
 */
class PrefectureUtility {
    public static function getAreaName($pref_slug) {
        $data = array_values(self::getPrefList([$pref_slug]));
        return $data[0] ?? '';
    }

    /**
    * 英字 => 日本語の都道府県リスト
    * [[tokyo => 東京],,, ]
    */
    public static function getPrefList($needle = null) {
        $datas = self::getPrefData($needle);
        return Hash::combine($datas, '{*}.en', '{*}.jp');
    }

    /**
    *
    * [[en => tokyo, jp => 東京],,, ]
    *
    * [ 2 => tokyo ] で配列を渡すと、[ tokyo => [en => tokyo, jp => 東京, id => 2, ],,, ]
    * で返す。
    */
    public static function getPrefData($needle = null) {
        $list = self::getJpAreaList($needle);
        if (!$list) {
            return [];
        }
        return array_reduce($list, function ($carry, $area) {
            $prefs = $area['prefs'] ?? [];
            return !$carry ? $prefs : (array_merge($carry, $prefs));
        });
    }

    /**
     * 　[ 関東 => [ tokyo => 東京 ] ]
     *
     * [ 2 => tokyo ] で配列を渡すと、　[ 関東 => [ 2 => 東京 ] ]
     * で返す。
     */
    public static function getAreaPrefOptgroupList($needle = null) {
        $list = self::getJpAreaList($needle);
        if (!$list) {
            return [];
        }

        //keyをidにする
        $is_id_key = !is_null($needle) && !isset($needle[0]);

        $datas = [];
        foreach ($list as $area) {
            $new_prefs = [];
            foreach ($area['prefs'] as $_ => $pref_data) {
                $index = $is_id_key ? $pref_data['id'] : $pref_data['en'];
                $new_prefs[$index] = $pref_data['jp'];
            }

            $datas[$area['area_jp']] = $new_prefs;
        }
        return $datas;
    }

    /**
     *
     * 必要な都道府県のみで構成する
     * [72 => tokyo]  >>>>>  [area_jp => 関東, prefs => [[en => tokyo, jp => 東京, id => 72 ],,,],,,,]
     *
     */
    public static function getJpAreaList($needle = null) {
        if (is_array($needle) && empty($needle)) {
            return [];
        }

        $is_index_array = isset($needle[0]);
        $need_slug_id_list = is_array($needle) ? array_flip($needle) : [];

        $needle_area_prefs = self::$jp_area_list;
        if (!is_null($needle)) {
            $needle_area_prefs = array_reduce(self::$jp_area_list, function ($total, $area) use ($need_slug_id_list) {
                $needle_prefs = array_filter($area['prefs'], function ($pref_data) use ($need_slug_id_list) {
                    return isset($need_slug_id_list[$pref_data['en']]);
                });
                if (!$needle_prefs) {
                    return $total;
                }

                $area['prefs'] = $needle_prefs;
                if (!$total) {
                    return [$area];
                }
                return array_merge($total, [$area]);
            });
        }

        if (!$needle_area_prefs) {
            return [];
        }

        $area_prefs = array_map(function ($area) use ($need_slug_id_list, $is_index_array) {
            //id付与する必要があれば
            $new_prefs = [];
            foreach (($area['prefs'] ?? []) as $pref) {
                $id = $is_index_array ? null : ($need_slug_id_list[$pref['en']] ?? 0);
                $new_prefs[$pref['en']] = array_merge($pref, [
                    'id' => $id,
                    'area_en' => $area['area_en'],
                    'area_jp' => $area['area_jp'],
                ]);
            }

            $area['prefs'] = $new_prefs;

            return $area;
        }, $needle_area_prefs);

        return $area_prefs;
    }

    //都道府県
    public static $jp_area_list = array(
        [
            'area_en' => 'hokkaido',
            'area_jp' => '北海道',
            'prefs' => [
                [
                    'en' => 'hokkaido',
                    'jp' => '北海道',
                ],
            ]
        ],
        [
            'area_en' => 'tohoku',
            'area_jp' => '東北',
            'prefs' => [
                [
                    'en' => 'aomori',
                    'jp' => '青森県',
                ],
                [
                    'en' => 'iwate',
                    'jp' => '岩手県',
                ],
                [
                    'en' => 'akita',
                    'jp' => '秋田県',
                ],
                [
                    'en' => 'miyagi',
                    'jp' => '宮城県',
                ],
                [
                    'en' => 'yamagata',
                    'jp' => '山形県',
                ],
                [
                    'en' => 'fukushima',
                    'jp' => '福島県',
                ],
            ]
        ],
        [
            'area_en' => 'kanto',
            'area_jp' => '関東',
            'prefs' => [
                [
                    'en' => 'ibaraki',
                    'jp' => '茨城県',
                ],
                [
                    'en' => 'tochigi',
                    'jp' => '栃木県',
                ],
                [
                    'en' => 'gunma',
                    'jp' => '群馬県',
                ],
                [
                    'en' => 'saitama',
                    'jp' => '埼玉県',
                ],
                [
                    'en' => 'chiba',
                    'jp' => '千葉県',
                ],
                [
                    'en' => 'tokyo',
                    'jp' => '東京都',
                ],
                [
                    'en' => 'kanagawa',
                    'jp' => '神奈川県',
                ],
            ]
        ],
        [
            'area_en' => 'chubu',
            'area_jp' => '中部',
            'prefs' => [
                [
                    'en' => 'yamanashi',
                    'jp' => '山梨県',
                ],
                [
                    'en' => 'nagano',
                    'jp' => '長野県',
                ],
                [
                    'en' => 'niigata',
                    'jp' => '新潟県',
                ],
                [
                    'en' => 'toyama',
                    'jp' => '富山県',
                ],
                [
                    'en' => 'ishikawa',
                    'jp' => '石川県',
                ],
                [
                    'en' => 'fukui',
                    'jp' => '福井県',
                ],
                [
                    'en' => 'shizuoka',
                    'jp' => '静岡県',
                ],
                [
                    'en' => 'aichi',
                    'jp' => '愛知県',
                ],
                [
                    'en' => 'gifu',
                    'jp' => '岐阜県',
                ],
            ]
        ],
        [
            'area_en' => 'kinki',
            'area_jp' => '近畿',
            'prefs' => [
                [
                    'en' => 'mie',
                    'jp' => '三重県',
                ],
                [
                    'en' => 'shiga',
                    'jp' => '滋賀県',
                ],
                [
                    'en' => 'kyoto',
                    'jp' => '京都府',
                ],
                [
                    'en' => 'oosaka',
                    'jp' => '大阪府',
                ],
                [
                    'en' => 'hyogo',
                    'jp' => '兵庫県',
                ],
                [
                    'en' => 'nara',
                    'jp' => '奈良県',
                ],
                [
                    'en' => 'wakayama',
                    'jp' => '和歌山県',
                ],
            ]
        ],
        [
            'area_en' => 'chubu',
            'area_jp' => '中国・四国',
            'prefs' => [
                [
                    'en' => 'tottori',
                    'jp' => '鳥取県',
                ],
                [
                    'en' => 'shimane',
                    'jp' => '島根県',
                ],
                [
                    'en' => 'okayama',
                    'jp' => '岡山県',
                ],
                [
                    'en' => 'hiroshima',
                    'jp' => '広島県',
                ],
                [
                    'en' => 'yamaguchi',
                    'jp' => '山口県',
                ],
                [
                    'en' => 'kagawa',
                    'jp' => '香川県',
                ],
                [
                    'en' => 'ehime',
                    'jp' => '愛媛県',
                ],
                [
                    'en' => 'tokushima',
                    'jp' => '徳島県',
                ],
                [
                    'en' => 'kochi',
                    'jp' => '高知県',
                ],
            ]
        ],
        [
            'area_en' => 'kyusyu',
            'area_jp' => '九州',
            'prefs' => [
                [
                    'en' => 'fukuoka',
                    'jp' => '福岡県',
                ],
                [
                    'en' => 'saga',
                    'jp' => '佐賀県',
                ],
                [
                    'en' => 'nagasaki',
                    'jp' => '長崎県',
                ],
                [
                    'en' => 'kumamoto',
                    'jp' => '熊本県',
                ],
                [
                    'en' => 'ooita',
                    'jp' => '大分県',
                ],
                [
                    'en' => 'miyazaki',
                    'jp' => '宮崎県',
                ],
                [
                    'en' => 'kagoshima',
                    'jp' => '鹿児島県',
                ],
                [
                    'en' => 'okinawa',
                    'jp' => '沖縄県',
                ],
            ]
        ],
    );
}
