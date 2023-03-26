<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Filesystem\Folder;
use Cake\Utility\Text;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Query;
//
use App\Utils\CustomUtility;
use Cake\Utility\Inflector;

class AppTable extends Table {
    public function initialize(array $config): void {
        // 作成日時と更新日時の自動化
        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                    'updated' => 'always'
                ]
            ]
        ]);
    }

    /**
     *
     * アタッチ類を追加する
     *
     */
    //attachesの実データをまとめる。
    public function getAttaches($entity) {
        $attaches = [];

        $modelname = $this->getAlias();
        $wwwUploadDir = '/' . UPLOAD_BASE_URL . '/' . Inflector::camelize($modelname);

        $id = $entity->id;

        //image
        foreach ($this->attaches['images'] ?? [] as $columns => $_att) {
            $attaches_path = $entity->{$columns} ?? '';

            foreach ($_att as $_option) {
                $prefix = $_option['prefix'];
                $_file = $wwwUploadDir . '/images/' . $prefix . '_' . $attaches_path;
                if (($_option['format'] ?? '') == 'webp') {
                    $_file = preg_replace('/(.*)\..*/u', '${1}.webp', $_file);
                }

                //
                $_url = "{$modelname}/{$columns}/{$prefix}/{$id}";
                $attaches[$columns][$prefix] = [
                    'path' => $_file,
                    'preview' => "/v1/preview/{$_url}",
                    'download' => "/v1/download/{$_url}",
                ];
            }
        }

        //file
        foreach ($table->attaches['files'] ?? [] as $columns => $_att) {
            $def = array('0', 'src', 'extention', 'name', 'download');
            $def = array_fill_keys($def, null);

            $attaches_path = $data[$columns] ?? '';

            $_attaches = $def;
            $_file = $wwwUploadDir . '/files/' . $attaches_path;

            if (is_file(WWW_ROOT . $_file)) {
                $_attaches['0'] = $_file;
                $_attaches['src'] = $_file;
                $_attaches['extention'] = CustomUtility::getExtension($data[$columns . '_name']);
                $_attaches['name'] = $data[$columns . '_name'];
                $_attaches['size'] = $data[$columns . '_size'];
                $_attaches['download'] = $wwwUploadDir . '/file/' . $data[$table->getPrimaryKey()] . '/' . $columns . '/';
                $_attaches['view'] = $wwwUploadDir . '/file/' . '/' . $data[$table->getPrimaryKey()] . '/' . $columns . '/view';
            }
            $attaches[$columns] = $_attaches;
        }
        return $attaches;
    }

    /**
    *
    * オフセット法ページネーション
    * @param array $options
    *
    * limit = 10
    * offset = 5
    * 6 7 8 9  「10」, 11, 12, 13, 14, 15
    *
    */
    public function paginationByOffset($query = null, array $options = []) {
        $alias = $this->getAlias();

        $options = array_merge(
            [
                'limit' => 30, //ページ数
                'center' => 0, //指定した行数が中心となるように取得する
                'offset' => 0,
            ],
            $options
        );
        extract($options);

        if (!$query) {
            $query = $this->find();
            $query->order(['id' => 'DESC']);
        }

        //
        $all_query = clone $query;
        $count = $all_query->count();

        // offset
        $offset = $offset;
        $getOffsetByCenter = !$offset && $center;
        if ($getOffsetByCenter) {
            $offset = $center - ($limit / 2);

            // オフセット確認
            $overOffset = ($offset + $limit) - $count;
            if ($overOffset > 0) {
                $offset -= $overOffset;
            }
            if ($offset < 0) {
                $offset = 0;
            }
        }

        //
        if ($offset > 0) {
            $query->offset($offset);
        }
        $result = $query->limit($limit)->toArray();

        return [
            'data' => $result,
            'paginate' => [
                'offset' => $offset,
                'max_count' => $count
            ]
        ];
    }

    /**
    *
    * 指定したIDを中心に取得する
    *
    */
    public function paginationByCenterId($query = null, array $options = []) {
        $alias = $this->getAlias();

        $options = array_merge(
            [
                'limit' => 30, //ページ数
                'centerId' => 0, //指定したIDが中心となるように取得する
            ],
            $options
        );
        extract($options);

        $query = $query ? $query : $this->find();

         //
        $all_query = clone $query;
        $count = $all_query->count();

        //
        $_query = clone $all_query;
        $result = $this->getDatasByCenterId($centerId, $limit, $_query);

        //
        $_query = clone $all_query;
        $firstId = $result[0]['id'] ?? 0;
        $offset = $this->getOffsetById($firstId, $_query, 'id', 'DESC');

        //
        $centerPosition = array_search($centerId, array_column($result, 'id')) + 1;
        $centerPosition = $offset + $centerPosition;

        return [
            'data' => $result,
            'paginate' => [
                'offset' => $offset,
                'position' => $centerPosition, // 存在すれば、取得後にスクロールする。
                'max_count' => $count
            ]
        ];
    }

    /**
    *
    * IDから日付を取得してシーク法ページネーション
    * @param string $direction next か prev
    * @param array $options
    *
    */
    public function paginationBySeek(string $direction, array $options) {
        $alias = $this->getAlias();

        $options = array_merge(
            [
                'limit' => 20, //ページ数
                'id' => 0, //前ページ最後のID
                'date' => null, //前ページ最後の日付
            ],
            $options
        );
        extract($options);

        // 指定したIDデータ最終日を取得する。
        if ($id) {
            $last = $this->find()->where([$alias . '.id' => $id])->first();
            $date = $last->date;
        }

        // 日付指定がない + 前ページ取得なら
        if (!$date && $direction == 'prev') {
            return [];
        }

        // 返却
        $cond = [];
        $order = 'DESC';
        if ($date) {
            if ($direction == 'next') {
                // 指定した日付より古いこと
                $cond[$alias . '.date <'] = $date;
                $order = 'DESC';
            } else {
                // 指定した日付より新しいこと
                $cond[$alias . '.date >'] = $date;
                $order = 'ASC';
            }
        }
        $data = $this->find()->where($cond)->order(['date' => $order])->limit($limit)->toArray();

        //
        if ($direction == 'prev') {
            $data = array_reverse($data);
        }
        return $data;
    }

    /**
     *
     * IDまでのoffsetを取得する
     *
     */
    public function getOffsetById($id, $query, $orderColumn = 'id', $orderDir = 'DESC') {
        $query = $query ? $query : $this->find();
        $alias = $this->getAlias();

        $searchValue = $id;
        if ($orderColumn != 'id') {
            $data = $this->find()->where(['id' => $id])->enableHydration(false)->select('id')->first();
            $searchValue = $data[$orderColumn] ?? '';
        }
        $orderDir = $orderDir == 'DESC' ? '>' : '<';
        return $query->where(["$alias.$orderColumn $orderDir" => $searchValue])->count();
    }

    /**
     *
     * 指定したIDを中心に指定数取得する。
     *
     */
    public function getDatasByCenterId($id, $limit = 0, $query = null, $orderColumn = 'id') {
        $query = $query ? $query : $this->find();

        $alias = $this->getAlias();

        //
        $_query = clone $query;
        $centerData = $_query->where(["$alias.id" => $id])->first();
        $centerValue = $centerData[$orderColumn] ?? '';

        // center分減らす
        $limit = $limit - 1;

        // 大きい方
        $big_cnt = floor($limit / 2);
        $_query = clone $query;
        $bigs = $_query->where(["$alias.$orderColumn >=" => $centerValue, "$alias.id !=" => $id])->order(["$alias.$orderColumn" => 'ASC'])->limit($big_cnt)->toArray();
        $bigs = array_reverse($bigs);

        // 小さい方
        $small_cnt = $limit - $big_cnt;
        $_query = clone $query;
        $smalls = $_query->where(["$alias.$orderColumn <=" => $centerValue, "$alias.id !=" => $id])->order(["$alias.$orderColumn" => 'DESC'])->limit($small_cnt)->toArray();

        // マージ
        $result = array_merge($bigs, [$centerData], $smalls);
        return $result;
    }

    /**
     *
     * すべてのbehaviorを削除する
     *
     */
    public function removeBehaviors() {
        foreach ($this->behaviors()->loaded() as $key) {
            $this->removeBehavior($key);
        }
    }

     /**
     *
     * キーワードで絞り込む
     * @param Query $query
     * @param $options
     *  -columns => [モデル名 => [カラム, カラム]]
     *  -word => text
     */
    public function _findKeyword(Query $query, array $columns, string $word):Query {
        //
        $cond = CustomUtility::getConditionKeyword($columns, $word);
        return $query->where($cond);
    }

    /**
     *
     * ランダムな並び順にする。(random_order)
     * @param Query $query
     */
    public function findShuffleByOrder(Query $query, array $options = []) {
        $alias = $this->getAlias();
        return $query->order('RAND()');
    }

    /**
     *
     * ランダムな1件を取得する。(全ID取得してからランダム算出)
     * @param Query $query
     */
    public function findShuffleById(Query $query, array $options = []) {
        $alias = $this->getAlias();

        $clone1 = clone $query;
        $id_list = $clone1->select(['id'])->extract('id')->toArray();

        //エラー
        if (!$id_list) {
            return $query->where(['id' => 0]);
        }

        //
        $rand_row = rand(0, count($id_list) - 1);
        return $query->where(['id' => $id_list[$rand_row] ?? 0]);
    }

    /**
     *
     * 1分間に3回以上のPOSTを行なっていないかチェックする
     *
     */
    public function isBlockedIp(string $ip) {
        $timered = new \Datetime('-1 minute');
        $cond = [
            'ip' => $ip,
            'updated >' => $timered
        ];
        $count_requested = $this->find()->where($cond)->count();
        return $count_requested >= 3;
    }
}
