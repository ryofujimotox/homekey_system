<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Utility\Text;
use Cake\Filesystem\Folder;
use Cake\Event\EventManager;
use App\Utils\CustomUtility;
use Cake\Utility\Inflector;
//
use App\Model\Behavior\BaseBehavior;
use App\Utils\AnalyzeUtility;

class SaveHiraganaBehavior extends BaseBehavior {
    public function initialize(array $config) : void {
        parent::initialize($config);
    }

    /**
     *
     * newEntity時
     *
     */
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options) {
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
            $data[$column] = $value;
        }
    }
}
