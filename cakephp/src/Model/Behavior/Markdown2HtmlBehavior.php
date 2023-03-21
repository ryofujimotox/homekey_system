<?php
namespace App\Model\Behavior;

use App\Model\Behavior\BaseBehavior;
use ArrayObject;
use Cake\Event\Event;
use Cake\Datasource\EntityInterface;
//
use Cake\Datasource\FactoryLocator;
use App\Utils\HtmlParseUtility;
use App\Utils\AnalyzeUtility;

/**
 *
 *
 * markdown to html
 * content_markdown => content
 *
 * 複数対応してない。
 *
 * newEntity時に変換しておく。
 * afterSaveで保存する。 (questionはidが必要なため。)
 *
 *
 */
class Markdown2HtmlBehavior extends BaseBehavior {
    public function initialize(array $config) : void {
        parent::initialize($config);

        $this->HtmlParseUtility = new HtmlParseUtility();

        // カラム名
        $this->columnMarkdown = 'content_markdown';
        $this->columnHtml = 'content_html';
        $this->columnHtmlText = 'content_text';
    }

     /**
     *
     * newEntity時
     *
     */
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options) {
        // idが必要なため、新規ならhtml登録しない
        $isEdit = $data['id'] ?? 0;
        if (!$isEdit) {
            $data[$this->columnHtml] = '';
            $data[$this->columnHtmlText] = '';
        }
    }

    /**
     *
     * セーブ後に
     *
     */
    public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options) {
        //
        $markdown = $entity[$this->columnMarkdown] ?? '';
        $html = $this->md2html($markdown, $entity['id'] ?? 0);

        // todo
        $columns = $this->dwa2($entity->toArray());
        foreach($columns as $column => $value){
            $this->table->updateAll([$column => $value], ['id' => $entity->id]);
        }


        /**
         *
         *
         *
         */
        // htmlだけ更新する。　新規の場合questionにidが紐づいていないため。
        $this->table->updateAll([$this->columnHtml => $html], ['id' => $entity->id]);

        // text
        $text = $this->HtmlParseUtility->strip_tag($html);
        $this->table->updateAll([$this->columnHtmlText => $text], ['id' => $entity->id]);
    }

    function dwa2($data){
        $resu = [];
        $columns = $this->_table->getSchema()->columns();
        foreach ($columns as $column) {
            $match = preg_match('/^(.*)_jp$/u', $column, $matched);
            $target = $matched[1] ?? '';
            if (!$target) {
                continue;
            }

            $value = $data[$target] ?? '';
            if ($value) {
                $value = AnalyzeUtility::Kanji2Hira($value);
            }
            $resu[$column] = $value;
        }
        return $resu;
    }

    /**
     *
     * md to html
     *
     */
    public function md2html(string $markdown, int $information_id) {
        $option = [
            'question' => function ($title) use ($information_id) {
                $this->Questions = FactoryLocator::get('Table')->get('Questions');
                return $this->Questions->getHtml($title, $information_id);
            },
            'link' => function ($title) {
                $this->Informations = FactoryLocator::get('Table')->get('Informations');
                $html = $this->Informations->getCardHtmlByTitle($title);
                return $html;
            }
        ];

        $html = $this->HtmlParseUtility->md2html($markdown, $option);

        // pr($html);
        // exit;

        return $html;
    }
}
