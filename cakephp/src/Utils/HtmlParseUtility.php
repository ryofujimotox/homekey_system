<?php
namespace App\Utils;

use App\Utils\CustomUtility;

class HtmlParseUtility {
    // 練習用
    public function practice() {
        $text = '
```
<scritp>alert("hello")</script>
```

# タイトル

@question(testtext1)

- リスト1
- リスト2

@question(testtext2)
        ';
        $option = [
            'question' => function ($title) {
                return "<div class='question'>{$title}</div>";
            }
        ];
        $html = $this->md2html($text, $option);
    }

    /**
     *
     * md to html
     *
     * @param string $markdown
     * @param array{
     *  key => 〇〇,// @以降にキーとする値
     *  parse => function(value), //
     * }
     *
     */
    public function md2html(string $markdown, array $options = []) {
        $Parsedown = new OverrideParsedown();
        $Parsedown->mineOption = $options;

        $html = $Parsedown->text($markdown);

        // $html = $this->sanitize_output($html);

        return $html;
    }

    /**
     *
     * タグと改行を無くす
     *
     */
    public function strip_tag($html) {
        $html = $this->strip_img($html);
        $html = CustomUtility::rip_tags($html);
        $html = $this->sanitize_output($html, true);
        return $html;
    }

    /**
     *
     * imgの src と alt 箇所だけ抜き出す
     *
     */
    public function strip_img($string) {
        $string = preg_replace('/<img(.*?)src="(.*?)"(.*?)alt="(.*?)"(.*?)(\/>)/u', '$2 $4', $string);
        return $string;
    }

    /**
     *
     * htmlの圧縮
     *
     */
    public function sanitize_output($buffer, $nl2br = false) {
        if ($nl2br) {
            return trim(preg_replace('/(?:\n|\n)/', ' ', $buffer));
        }
        // if (false) {
        //     return trim(preg_replace('/(?:\n|\r|\r\n)/', '', $buffer));
        // }

        $search = array(
            '/\>[^\S ]+/s',  // strip whitespaces after tags, except space
            '/[^\S ]+\</s',  // strip whitespaces before tags, except space
            '/(\s)+/s'       // shorten multiple whitespace sequences
        );
        $replace = array(
            '>',
            '<',
            '\\1'
        );
        $buffer = preg_replace($search, $replace, $buffer);
        return $buffer;
    }
}

use Parsedown;

class OverrideParsedown extends Parsedown {
    protected $BlockTypes = array(
        '#' => array('Header'),
        '*' => array('Rule', 'List'),
        '+' => array('List'),
        '-' => array('SetextHeader', 'Table', 'Rule', 'List'),
        '0' => array('List'),
        '1' => array('List'),
        '2' => array('List'),
        '3' => array('List'),
        '4' => array('List'),
        '5' => array('List'),
        '6' => array('List'),
        '7' => array('List'),
        '8' => array('List'),
        '9' => array('List'),
        ':' => array('Table'),
        '<' => array('Comment', 'Markup'),
        '=' => array('SetextHeader'),
        '>' => array('Quote'),
        '[' => array('Reference'),
        '_' => array('Rule'),
        '`' => array('FencedCode'),
        '|' => array('Table'),
        '~' => array('FencedCode'),

        '@' => array('Mine'), // 追加項目
    );

    protected function blockMine($Line) {
        if (!preg_match('/@(.*?)\((.*?)\)/u', $Line['text'], $matches)) {
            return;
        }

        $key = $matches[1];
        $option = $this->mineOption[$key] ?? null;
        if (!$option) {
            return;
        }

        $value = $matches[2];
        $html = $option($value);

        //
        $Block = array(
            'element' => array(
                'name' => 'div', // タグ名
                'rawHtml' => $html, //textでもいい
                'handler' => 'line',
            ),
        );
        return $Block;
    }
}
