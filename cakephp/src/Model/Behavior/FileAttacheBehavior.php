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

class FileAttacheBehavior extends BaseBehavior {
    public function initialize(array $config) : void {
        parent::initialize($config);
    }

    /**
    * attaches配列を付与する。
    */
    protected function _beforeFind($table, $results, $primary = false) {
        $results->attaches = $table->getAttaches($results);

        return $results;
    }

    /**
     * 取得時
     * attaches配列を付与する。
     */
    public function beforeFind(Event $event, Query $query, ArrayObject $options, $primary) {
        $table = $event->getSubject();
        $query->formatResults(function ($results) use ($table, $primary) {
            return $results->map(function ($row) use ($table, $primary) {
                if (is_object($row) && !isset($row['existing'])) {
                    $results = $this->_beforeFind($table, $row, $primary);
                }
                return $row;
            });
        });
    }
}
