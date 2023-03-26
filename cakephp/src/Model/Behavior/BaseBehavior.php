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

class BaseBehavior extends Behavior {
    public function initialize(array $config) : void {
        // attaches追加
        $Model = $this->table();
        $this->table = $Model;

        // upload
        $modelName = $Model->getTable();
        $modelName = Inflector::camelize($modelName);
        $this->fullUploadDir = UPLOAD_DIR . $modelName;
        $this->wwwUploadDir = '/' . UPLOAD_BASE_URL . '/' . $modelName;

        //
        $attaches = $Model->attaches;
        $this->AttacheOptions = $attaches;
    }
}
