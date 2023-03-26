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

class ImageUtility {
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

    /**
     *
     * ファイルアップロード
     * @param　array {
     *  tableConf: [],
     *  file: [],
     *  distBase: ""
     * } $option
     *
     */
    public function uploadByBinary($option) {
        $option = array_merge(
            [
                'tableConf' => [], // 変換情報
                'file' => [], // ファイル情報(binary等の配列)
                'distBase' => '', // ベースとする変換先
            ],
            $option
        );
        extract($option);

        // $file = [
        //     'binary' => '',
        //     'name' => '',
        //     'size' => '',
        // ];
        // $tableConfs = [
        //     [
        //         'prefix' => 'full',
        //         'width' => 1200,
        //         'height' => 1200
        //     ]
        // ];

        $tmp_file = tmpfile();
        fwrite($tmp_file, $file['binary']);
        fseek($tmp_file, 0);
        $tmp_path = stream_get_meta_data($tmp_file)['uri'] ?? '';
        $file = [
            'tmp_name' => $tmp_path,
            'name' => $file['name'],
            'size' => $file['type']
        ];

        $uploaded = self::uploadFiles($file, $tableConf, $distBase);

        //tmpファイル破棄
        fclose($tmp_file);

        return $uploaded;
    }

    /**
     *
     * テキストを画像にしてアップロードする。
     *
     */
    public function uploadByString($option) {
        $option = array_merge(
            [
                'size' => [1200, 630],
                'tableConf' => [],
                'text' => '',
                'distBase' => $distBase, // ベースとする変換先
            ],
            $option
        );

        $baseImg = UPLOAD_DIR . 'thumbnail_base.jpg';

        // テキストから画像を生成する
        $binary = self::getBinaryByString($option['text'], $option['size'], $baseImg);
        $option['file'] = [
            'binary' => $binary,
            'name' => 'test.jpeg',
            'size' => '100',
        ];

        return self::uploadByBinary($option);
    }

    //fileをtableConfに合わせ変換してアップロードする。
    public function uploadFiles($file, $tableConf, $distBase) {
        // $file = [
        //     'tmp_name' => '',
        //     'name' => '',
        //     'size' => ''
        // ];

        //確認
        $get_size = ($file['size'] ?? 0) ? $file['size'] : getimagesize($file['tmp_name']);
        if (!$get_size) {
            return false;
        }

        //
        $uuid = Text::uuid();
        $ext = CustomUtility::getExtension($file['name']);//拡張子
        $newname = sprintf('%s', $uuid);
        $newname_ext = $newname . '.' . $ext;//ファイル名

        foreach ($tableConf as $_tableConfig) {
            //変換
            $_option = [
                'size' => [
                    'width' => $_tableConfig['width'],
                    'height' => $_tableConfig['height'],
                ], // 変換サイズ
                'source' => $file['tmp_name'], // 元ファイル
                'dist' => $distBase . $_tableConfig['prefix'] . '_' . $newname_ext, // ファイル変換先
                'method' => $_tableConfig['method'] ?? 'fit', // 方法
            ];
            if (($_tableConfig['format'] ?? '') == 'webp') {
                $_option['dist'] = $distBase . $_tableConfig['prefix'] . '_' . $newname . '.webp';
                ImageUtility::convert($_option);
            } else {
                ImageUtility::convert($_option);
            }
        }

        return [
            'newname' => $newname_ext,
            'name' => $file['name'] ?? '',
            'ext' => $ext,
            'size' => $file['size'] ?? '',
        ];
    }

     /**
     * ファイルアップロード
     * @param $size [width]x[height]
     * @param $source アップロード元ファイル(フルパス)
     * @param $dist 変換後のファイルパス（フルパス）
     * @param $method 処理方法
     *        - fit     $size内に収まるように縮小
     *        - cover   $sizeの短い方に合わせて縮小
     *        - crop    cover 変換後、中心$sizeでトリミング
     * */
    public function convert($option) {
        $option = array_merge(
            [
                'size' => ['width' => 0, 'height' => 0], // [width]x[height]
                'source' => '', // アップロード元ファイル(フルパス)
                'dist' => '', // 変換後のファイルパス（フルパス）
                'method' => 'fit'// 処理方法
            ],
            $option
        );
        extract($option);
        $size = ($size['width'] ?? 0) . 'x' . ($size['height'] ?? 0);

        $convertParams = '-thumbnail';// ImageMagick configure

        list($ow, $oh, $info) = getimagesize($source);
        $sz = explode('x', $size);
        $cmdline = CustomUtility::convertPath();
        //サイズ指定ありなら
        if (0 < $sz[0] && 0 < $sz[1]) {
            if ($ow <= $sz[0] && $oh <= $sz[1]) {
                //枠より完全に小さければ、ただのコピー
                $size = $ow . 'x' . $oh;
                $option = $convertParams . ' ' . $size . '>';
            } else {
                //枠をはみ出していれば、縮小
                if ($method === 'cover' || $method === 'crop') {
                    //中央切り取り
                    $crop = $size;
                    if (($ow / $oh) <= ($sz[0] / $sz[1])) {
                        //横を基準
                        $size = $sz[0] . 'x';
                    } else {
                        //縦を基準
                        $size = 'x' . $sz[1];
                    }

                    //cover
                    $option = '-thumbnail ' . $size . '>';

                    //crop
                    if ($method === 'crop') {
                        $option .= ' -gravity center -crop ' . $crop . '+0+0';
                    }
                } else {
                    //通常の縮小 拡大なし
                    $option = $convertParams . ' ' . $size . '>';
                }
            }
        } else {
            //サイズ指定なしなら 単なるコピー
            $size = $ow . 'x' . $oh;
            $option = $convertParams . ' ' . $size . '>';
        }
        $a = system(escapeshellcmd($cmdline . ' ' . $option . ' ' . $source . ' ' . $dist));
        @chmod($dist, 0666);
        return $a;
    }

     /**
     * ファイルアップロード
     * @param $size [width]x[height]
     * @param $source アップロード元ファイル(フルパス)
     * @param $dist 変換後のファイルパス（フルパス）
     * @param $method 処理方法
     *        - fit     $size内に収まるように縮小
     *        - crop    cover 変換後、中心$sizeでトリミング
     * */
    public function convertWebp($option) {
        $option = array_merge(
            [
                'size' => ['width' => 0, 'height' => 0], // [width]x[height]
                'source' => '', // アップロード元ファイル(フルパス)
                'dist' => '', // 変換後のファイルパス（フルパス）
                'method' => 'fit'// 処理方法
            ],
            $option
        );
        extract($option);
        $size = ($size['width'] ?? 0) . ' ' . ($size['height'] ?? 0);
        $dist = preg_replace('/(.*)\..*/u', '${1}.webp', $dist);

        $cmd = 'cwebp ';
        if ($size) {
            $cmd .= "-resize {$size} ";
        }
        $cmd .= "{$source} -o {$dist}";

        $a = system(escapeshellcmd($cmd));
        @chmod($dist, 0666);
        return $a;
    }

    /**
     *
     *
     *
     */
    public function getBinaryByString(string $str, array $size = [600, 600], string $imgpath = '') {
        // 画像を生成します
        if ($imgpath) {
            $im = imagecreatefromstring(file_get_contents($imgpath));
            $size = [imagesx($im), imagesy($im)];
        } else {
            $im = imagecreatetruecolor($size[0], $size[1]);
        }

        // いくつかの色を生成します
        $white = imagecolorallocate($im, 255, 255, 255);
        $grey = imagecolorallocate($im, 128, 128, 128);
        $black = imagecolorallocate($im, 0, 0, 0);
        if (!$imgpath) {
            // 背景色
            imagefilledrectangle($im, 0, 0, $size[0], $size[1], $white);
        }

        // フォントサイズ等
        $font = WWW_ROOT . 'testfont.otf';

        /**
        *
        * 上のタイトル箇所
        *
        */
        $text = 'サムネイル自動生成';
        $fontsize = 100;
        $contentSize = self::getFontContentSize($fontsize, $font, $text);
        $positionX = ($size[0] / 2) - ($contentSize[0] / 2);
        $position = [$positionX, $fontsize * 2];
        imagettftext($im, $fontsize, 0, $position[0], $position[1], $black, $font, $text);

        /**
         *
         *
         *
         */
        $text = $str;
        $fontsize = 100;
        $contentSize = self::getFontContentSize($fontsize, $font, $text);
        $positionX = ($size[0] / 2) - ($contentSize[0] / 2);
        $position = [$positionX, $size[1] / 2 + $fontsize / 2 + $fontsize];
        // テキストに影を付けます
        imagettftext($im, $fontsize, 0, $position[0] + 7, $position[1] + 7, $grey, $font, $str);

        // テキストを追加します
        imagettftext($im, $fontsize, 0, $position[0], $position[1], $black, $font, $str);

        // imagepng() を使用して imagejpeg() よりもクリアなテキストにします
        ob_start();
        imagejpeg($im);
        $image = ob_get_clean();

        imagedestroy($im);

        return $image;
    }

    public function getFontContentSize($size, $font, $text) {
        $result = ImageTTFBBox($size, 0, $font, $text);

        // 左上
        $x0 = $result[6];
        $y0 = $result[7];
        // 右下
        $x1 = $result[2];
        $y1 = $result[3];

        $width = $x1 - $x0;
        $height = $y1 - $y0;
        return [$width, $height];
    }

    public function viewImageByString(string $str) {
        // コンテントタイプを設定します
        header('Content-type: image/png');
        $im = self::getBinaryByString($str);
        echo $im;
        exit;
    }
}
