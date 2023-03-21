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
use App\Utils\ImageUtility;
use App\Utils\FileUtility;
use App\Model\Behavior\BaseBehavior;

class RegistFileAttacheBehavior extends BaseBehavior {
    public function initialize(array $config) : void {
        parent::initialize($config);

        $this->checkUploadDirectory($this->table);
    }

    /**
     *
     * その他
     *
     * */
    //uploadフォルダーがなければ作成する。
    public function checkUploadDirectory($table) {
        $Folder = new Folder();

        $uploadDirPermition = 0777;

        $dir = $this->fullUploadDir . DS . 'images';
        if (!is_dir($dir) && !empty($table->attaches['images'])) {
            if (!$Folder->create($dir, $uploadDirPermition)) {
            }
        }

        $dir = $this->fullUploadDir . DS . 'files';
        if (!is_dir($dir) && !empty($table->attaches['files'])) {
            if (!$Folder->create($dir, $uploadDirPermition)) {
            }
        }
    }

    /**
     * newEntity時だった気がする
     * 画像はsessionに保存しプレビューリンクを用意する。
     */
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options) {
        $table = $event->getSubject();

        //attachesのデータをsession保存する。
        foreach (array_keys($this->AttacheOptions['images']) as $key) {
            $tmp = $data[$key] ?? [];
            $token = ($data['attaches_token'] ?? '') ? $data['attaches_token'] : CustomUtility::getToken();//sessionと紐付ける。
            $data['attaches_token'] = $token;

            $saved_attache_data = $this->sessionsave_attache_files($token, ($key . '_new'), $tmp);
            if ($saved_attache_data) {
                $data['attaches'][$key] = array_merge(
                    $saved_attache_data,
                    [
                        'preview' => '/v1/preview/' . $token . '/' . $key . '_new'
                    ]
                );
            }
            if (isset($data[$key])) {
                $data['_' . $key] = $tmp;
                unset($data[$key]);
            } else {
                if (isset($data['_' . $key])) {
                    unset($data['_' . $key]);
                }
            }
        }
    }

    /**
     * セーブ直前
     * 保存直前にファイルアップロードしてカラムにパスを入れる。
     */
    public function beforeSave(Event $event, EntityInterface $entity, ArrayObject $options) {
        $table = $event->getSubject();

        $id = $entity->id ?? 0;

        $attaches_token = ($entity->attaches_token ?? '') ? $entity->attaches_token : '';

        $type = 'images';
        foreach ($this->AttacheOptions[$type] as $key => $tableConf) {
            //新しいファイルがない場合は、旧ファイルのまま上書きしない
            $is_saved = $entity->attaches[$key]['preview'] ?? false;
            if (!$is_saved) {
                continue;
            }

            //sessionが破棄されていたらエラー
            $attache_files = $_SESSION['attache_files'][$attaches_token][$key . '_new'] ?? [];
            if (!$attache_files) {
                return false;
            }

            // アップロード作業
            $option = [
                'tableConf' => $tableConf, // 変換情報
                'file' => $attache_files, // ファイル情報(binary等の配列)
                'distBase' => $this->fullUploadDir . DS . $type . DS, // ベースとする変換先
            ];
            $uploaded = ImageUtility::uploadByBinary($option);
            $newname = $uploaded['newname'] ?? '';
            if (!$newname) {
                continue;
            }

            // 旧ファイルの削除
            $old_files = $entity->getOriginal('attaches')[$key] ?? [];
            foreach ($old_files as $_prefix => $pathes) {
                $file_path = $pathes['path'] ?? '';
                FileUtility::deleteByPath($file_path);
            }

            //entityにデータ追加(保存する)
            if ($type == 'images') {
                $entity->{$key} = $newname;
            }
            if ($type == 'files') {
                $entity->{$key} = $newname;
                $entity->{$key . '_name'} = CustomUtility::getFileName($uploaded['name'], $uploaded['ext']);
                $entity->{$key . '_size'} = $uploaded['size'];
                $entity->{$key . '_extension'} = $uploaded['ext'];
            }
        }
        //セッション破棄
        unset($_SESSION['attache_files'][$attaches_token]);
    }

    // データ削除時に
    public function beforeDelete(Event $event, EntityInterface $entity, ArrayObject $options) {
        // ファイル削除
        $attaches = $entity->attaches ?? [];
        foreach ($attaches as $columnKey => $attach) {
            foreach ($attach as $_prefix => $pathes) {
                $file_path = $pathes['path'] ?? '';
                FileUtility::deleteByPath($file_path);
            }
        }
    }

    //attachesの更新は_uploadAttachesか、_deleteのみ。
    public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options) {
        $table = $event->getSubject();

        // アタッチ更新
        $entity->attaches = $table->getAttaches($entity);
    }

    /**
     *
     * tmpパスからバイナリーデータを生成して、指定されたトークン鍵でセッション保存する。
     *
     */
    public function sessionsave_attache_files($token, $key, $tmp) {
        // $name = $tmp->getClientFilename();
        // $path = WWW_ROOT . 'upload' . DS . $name;
        // $myFile->moveTo($path);

        if ($tmp->getError()) {
            return [];
        }

        $stream = $tmp->getStream();
        $attaches = [
            // 'stream' => $stream,
            // 'stream' => $tmp->getStream()->getContents(),
            // 'binary' => $tmp->getStream()->__toString(),
            'name' => $tmp->getClientFilename(),
            'size' => $tmp->getSize(),
            'type' => $tmp->getClientMediaType(),
        ];
        $saves = $attaches;

        // 画像判定
        $path = $stream->getMetadata()['uri'] ?? '';
        $is_image = @exif_imagetype($path);

        //画像だった場合
        if ($is_image) {
            //バイナリー状のまま回転させる。(写真対応)
            $Image = new \Image();
            $data = $Image->rotateFromBinary($stream->getContents());
            $saves['binary'] = $data;
        }

        //返却
        $_SESSION['attache_files'][$token][$key] = $saves;
        return $attaches;
    }
}
