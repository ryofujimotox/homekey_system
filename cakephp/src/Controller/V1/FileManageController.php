<?php

namespace App\Controller\V1;

use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\View\Exception\MissingTemplateException;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Filesystem\Folder;
use Cake\Utility\Hash;
use Cake\Routing\RequestActionTrait;
//use App\Model\Entity\DokkyoLiteral;
use Cake\Event\EventInterface;
use App\Utils\CustomUtility;

class FileManageController extends AppController {
    public function initialize() :void {
        parent::initialize();
    }

    public function beforeFilter(EventInterface $event) {
        $this->autoRender = false;
        // $this->getEventManager()->off($this->Csrf);
    }

    /**
     *
     * 画像取得
     *
     */
    public function attachImage($preview_type, $model, $column, $asset_prefix, $id) {
        $isDownload = $preview_type == 'download';
        //
        $format_data = $this->getImageByAsset($model, $column, $asset_prefix, $id);
        return $this->download($format_data, $isDownload);
    }

    public function getImageByAsset($model, $column, $asset_prefix, $id) {
        $Model = $this->getTableLocator()->get($model);

        // 取得
        $data = $Model->find()->where(['id' => $id])->first();
        $file = $data['attaches'][$column][$asset_prefix]['path'] ?? '';

        if (!$file) {
            throw new NotFoundException(__('存在しません'));
        }

        //
        $format_data = [
            [
                'path' => WWW_ROOT . $file,
                'name' => $column
            ]
        ];
        return $format_data;
    }

    /**
     *
     * infoContents等のattachesファイルを取得する。
     *
     */
    public function manageAttaches($model = 'infoContents', $id = 0, $attache_name = 'file', $is_view = 0) {
        $this->{$model} = $this->getTableLocator()->get($model);
        if (!$this->{$model}) {
            return;
        }

        $data = $this->{$model}->find()->where(['id' => $id])->first();
        if (!$data) {
            return;
        }

        $file = $data['attaches'][$attache_name] ?? [];
        $format_data = [
            [
                'path' => WWW_ROOT . ($file[0] ?? ''),
                'name' => ($file['name'] ?? ''),
            ]
        ];

        $is_view = (bool) $is_view;
        return $this->download($format_data, $is_view);
    }

    //file_get_contentsのデータを表示する。 事前にsessionにtypeとdataを保存しとく。
    public function viewContentFile($token, $key) {
        $data = $_SESSION['attache_files'][$token][$key]['binary'] ?? '';//file_get_contents
        $type = $_SESSION['attache_files'][$token][$key]['type'] ?? '';//拡張子

        if (!$data || !$type) {
            throw new NotFoundException(__('存在しません'));
        }
        switch ($type) {
            case IMAGETYPE_JPEG:
                header('content-type: image/jpeg');
                break;
            case IMAGETYPE_PNG:
                header('content-type: image/png');
                break;
            case IMAGETYPE_GIF:
                header('content-type: image/gif');
                break;
            default:
                header("content-type: application/{$type}");
                break;
        }
        echo $data;
        exit;
    }

    /**
     *
     *
     * ファイルダウンロード
     * データのフォーマットは以下
     *
     *
     *
     */
    // $format_data = [
    //     [
    //         'path' => 'ファイルまでのフルパス',//WWW_ROOT~
    //         'name' => 'ファイル名',
    //     ]
    // ];
    public function download($format_data = [], $is_view = false) {
        if (count($format_data) == 1) {
            return $this->output_file($format_data[0], $is_view);
        }
        return $this->output_zip($format_data);
    }

    //ファイル出力
    //ファイルダウンロードか、開くを指定できる。
    public function output_file($data, $download = false) {
        $path = $data['path'];
        if (!file_exists($path)) {
            return;
        }
        $filename = $data['name'] . '.' . CustomUtility::getExtension($path);

        $response = $this->response->withFile(
            $path,
            ['download' => $download, 'name' => $filename]
        );
        return $response;

        return $this->response;
    }

    //ZIP出力
    public function output_zip($datas) {
        header('Content-Type: text/html; charset=UTF-8');

        //ZIP用意
        $zip = new \ZipArchive();
        $tmpname = 'files-' . date('Ymd');
        $tmpZipPath = '/tmp/' . $tmpname . '.zip';
        if (file_exists($tmpZipPath)) {
            unlink($tmpZipPath);
        }
        if ($zip->open($tmpZipPath, \ZipArchive::CREATE) === false) {
            throw new IllegalStateException("failed to create zip file. ${tmpZipPath}");
        }

        $c = 0;
        foreach ($datas as $key => $data) {
            $c++;

            $ext = CustomUtility::getExtension($data['path']);
            $filename = $data['name'] . '_' . date('Ymd') . '-' . $c . '.' . $ext;
            $filename = mb_convert_encoding($filename, 'SJIS-WIN', 'UTF-8');

            $zip->addFile($data['path'], $filename);
        }

        if ($zip->close() === false) {
            throw new IllegalStateException("failed to close zip file. ${tmpZipPath}");
        }

        $tmpname = mb_convert_encoding($tmpname, 'SJIS-WIN', 'UTF-8');

        if (file_exists($tmpZipPath)) {
            $this->response->type('application/zip');
            $this->response->file($tmpZipPath, array('download' => true));
            $this->response->download($tmpname . '.zip');
            $this->response->withHeader('Pragma', 'public');
            $this->response->withHeader('Expires', '0');
            $this->response->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
            $this->response->withHeader('Content-Transfer-Encoding', 'binary');
            $this->response->withHeader('Content-Type', 'application/octet-streams');
            $this->response->withHeader('Content-Disposition', 'attachment; filename=' . $tmpname . '.zip');

            setcookie('loading', 'complete', 0, '/');
            return $this->response;
        } else {
            return false;
        }
    }

    /**
     *
     * その他
     */
    public function getExtension($filename) {
        return strtolower(substr(strrchr($filename, '.'), 1));
    }
}
