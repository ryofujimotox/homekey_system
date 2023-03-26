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
 * 日付まわり
 *
 */
class DateUtility {
    /**
     *
     * 秒 を H:i:s に変換する。
     * @param int seconds
     *
     */
    public static function Second2His(int $seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;

        $hms = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

        return $hms;
    }

    //様々な形式の日付をdate型にして返す。
    //format K == 令和　　X == 00(令和何年)
    public static function getDate($date = '', $format = '') {
        if ($date) {
            //2021年02月21日　=> 日本語フォーマットの日付をdatetime型に変換する
            if ((preg_match('/[0-9]{4}年[0-9]+月[0-9]+日/', $date))) {
                list($year, $other) = explode('年', $date);
                list($month, $other) = explode('月', $other);
                list($day, $other) = explode('日', $other);
                $date = $year . '-' . $month . '-' . $day;
            }

            try {
                $ddd = new \DateTime($date);
            } catch (Exception $e) {
                $ddd = new \DateTime('0000-00-00');
            }
        } else {
            $ddd = new \DateTime();
        }

        if ($format) {
            if ($format == 'jp') {
                $week = array('日', '月', '火', '水', '木', '金', '土');
                $w = $week[$ddd->format('w')];
                return $ddd->format('Y年m月d日') . ' (' . $w . ') ' . $ddd->format('H:i');
            } else {
                // 元号一覧
                $era_list = [
                    // 令和(2019年5月1日〜)
                    [
                        'jp' => '令和', 'jp_abbr' => '令',
                        'en' => 'Reiwa', 'en_abbr' => 'R',
                        'time' => '20190501'
                    ],
                    // 平成(1989年1月8日〜)
                    [
                        'jp' => '平成', 'jp_abbr' => '平',
                        'en' => 'Heisei', 'en_abbr' => 'H',
                        'time' => '19890108'
                    ],
                    // 昭和(1926年12月25日〜)
                    [
                        'jp' => '昭和', 'jp_abbr' => '昭',
                        'en' => 'Showa', 'en_abbr' => 'S',
                        'time' => '19261225'
                    ],
                    // 大正(1912年7月30日〜)
                    [
                        'jp' => '大正', 'jp_abbr' => '大',
                        'en' => 'Taisho', 'en_abbr' => 'T',
                        'time' => '19120730'
                    ],
                    // 明治(1873年1月1日〜)
                    // ※明治5年以前は旧暦を使用していたため、明治6年以降から対応
                    [
                        'jp' => '明治', 'jp_abbr' => '明',
                        'en' => 'Meiji', 'en_abbr' => 'M',
                        'time' => '18730101'
                    ],
                ];

                $format_K = '';
                $format_k = '';
                $format_Q = '';
                $format_q = '';
                $format_X = $ddd->format('Y');
                $format_x = $ddd->format('y');

                foreach ($era_list as $era) {
                    $dt_era = new \DateTime($era['time']);
                    if ($ddd->format('Ymd') >= $dt_era->format('Ymd')) {
                        $format_K = $era['jp'];
                        $format_k = $era['jp_abbr'];
                        $format_Q = $era['en'];
                        $format_q = $era['en_abbr'];
                        $format_X = sprintf('%02d', $format_x = $ddd->format('Y') - $dt_era->format('Y') + 1);
                        break;
                    }
                }

                $result = '';

                foreach (str_split($format) as $val) {
                    // フォーマットが指定されていれば置換する
                    if (isset(${"format_{$val}"})) {
                        $result .= ${"format_{$val}"};
                    } else {
                        $result .= $ddd->format($val);
                    }
                }

                return $result;
            }
        }
        return $ddd;
    }

    //その日の00時を返す。
    public static function getStartDateTime($date = null) {
        if ($date) {
            $date = new \DateTime($date);
        } else {
            $date = new \DateTime();
        }
        $date = $date->format('Y-m-d 00:00:00');
        return new \DateTime($date);
    }

    //その月の初日
    public static function getMonthFirstDay($date, $format = 'Y-m-d') {
        $date = new Date(date('Y-m-d', strtotime('first day of ' . $date)));
        return $date->format($format);
    }

    //その月の末日
    public static function getMonthLastDay($date, $format = 'Y-m-d') {
        $date = new Date(date('Y-m-d', strtotime('last day of ' . $date)));
        return $date->format($format);
    }

    //次の日の取得
    public static function nextDay($date) {
        if (is_object($date)) {
            $ddd = $date;
        } else {
            $ddd = new \DateTime($date);
        }
        $ddd->modify('+1 days');
        return $ddd->format('Y-m-d');
    }

    //和暦の変換。　to_wareki('KX年', post_custom('cus_date'))
    public static function to_wareki($format, $time = 'now') {
        if (!$time) {
            return '';
        }
        // 元号一覧
        $era_list = [
            // 令和(2019年5月1日〜)
            [
                'jp' => '令和', 'jp_abbr' => '令',
                'en' => 'Reiwa', 'en_abbr' => 'R',
                'time' => '20190501'
            ],
            // 平成(1989年1月8日〜)
            [
                'jp' => '平成', 'jp_abbr' => '平',
                'en' => 'Heisei', 'en_abbr' => 'H',
                'time' => '19890108'
            ],
            // 昭和(1926年12月25日〜)
            [
                'jp' => '昭和', 'jp_abbr' => '昭',
                'en' => 'Showa', 'en_abbr' => 'S',
                'time' => '19261225'
            ],
            // 大正(1912年7月30日〜)
            [
                'jp' => '大正', 'jp_abbr' => '大',
                'en' => 'Taisho', 'en_abbr' => 'T',
                'time' => '19120730'
            ],
            // 明治(1873年1月1日〜)
            // ※明治5年以前は旧暦を使用していたため、明治6年以降から対応
            [
                'jp' => '明治', 'jp_abbr' => '明',
                'en' => 'Meiji', 'en_abbr' => 'M',
                'time' => '18730101'
            ],
        ];
        $dt = new \DateTime($time . '-01-01');

        $format_K = '';
        $format_k = '';
        $format_Q = '';
        $format_q = '';
        $format_X = $dt->format('Y');
        $format_x = $dt->format('y');

        foreach ($era_list as $era) {
            $dt_era = new \DateTime($era['time']);
            if ($dt->format('Ymd') >= $dt_era->format('Ymd')) {
                $format_K = $era['jp'];
                $format_k = $era['jp_abbr'];
                $format_Q = $era['en'];
                $format_q = $era['en_abbr'];
                $format_X = sprintf('%02d', $format_x = $dt->format('Y') - $dt_era->format('Y') + 1);
                break;
            }
        }

        $result = '';

        foreach (str_split($format) as $val) {
            // フォーマットが指定されていれば置換する
            if (isset(${"format_{$val}"})) {
                $result .= ${"format_{$val}"};
            } else {
                $result .= $dt->format($val);
            }
        }

        return $result;
    }
}
