<?php
namespace App\Utils;

use meCab\meCab;

class AnalyzeUtility {
    /**
     *
     * 漢字 to ひらがな に変換する
     * @param string $str
     *
     */
    public function Kanji2Hira(string $str) {
        $analyzed = self::mecab($str, ['want' => 'reading']);
        $text = implode('', $analyzed);
        $kana2hira = mb_convert_kana($text, 'cH');
        return $kana2hira;
    }

    /**
     *
     * GoogleAPI
     * ひらがな to 漢字 に変換する
     * @param string $str
     *
     */
    public function Hira2KanjiByGoogle(string $str) {
        $parameta = urlencode($str);
        $analyzeds = file_get_contents('http://www.google.com/transliterate?langpair=ja-Hira|ja&text=' . $parameta);
        $analyzeds = json_decode($analyzeds);
        return implode('', array_map(function ($_analyzed) {
            return $_analyzed[1][0] ?? '';
        }, $analyzeds));
    }

    /**
    *
    * 解析
    *
    * @param text : string
    * @return Array
    *
    */
    public function mecab(string $text, array $config = []) {
        $config = array_merge(
            [
                'excludeSpeech' => [], // 必要ない品詞を指定する
                'needleSpeech' => [], // 必要な品詞を指定する

                'want' => 'origin', // 取得するテキストタイプ
            ],
            $config
        );
        extract($config);

        //
        $text = str_replace(array("\r\n", "\r", "\n"), ' ', $text);

        $mecab = new meCab();
        $analyseds = $mecab->analysis($text);

        $words = [];
        foreach ($analyseds as $analized) {
            // 品詞をチェックする
            $speech = $analized->getSpeech();
            $isNeedle = in_array($speech, $needleSpeech);
            $isExclude = in_array($speech, $excludeSpeech);
            if ($isExclude && !$isNeedle) {
                continue;
            }

            // 元の単語を取得
            $result = '';
            if ($want == 'origin') {
                $result = $analized->getOriginal();
            }
            if ($want == 'reading') {
                $result = $analized->getReading();
            }

            //
            if (!$result) {
                $result = $analized->getText();
            }
            $words[] = $result;
        }

        return $words;
    }
}
