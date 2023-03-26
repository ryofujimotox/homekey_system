<?php
namespace App\Test;

// use App\Test\Fixture\UsersFixture;
use Cake\Routing\Router;
use Cake\Auth\DefaultPasswordHasher;
use \Zend\Diactoros\UploadedFile;
//
use App\Utils\CustomUtility;

trait TestTrait {
    /**
     *
     * DB上のサンプルデータを取得する
     *
     */
    public function getLoginToken($sample, $manage_school_id = 0) {
        $sample = $this->roleSamples($sample);

        $Users = $this->getTableLocator()->get('Users');
        $sample = $Users->find()->where(['id' => $sample['id']])->first();

        $ret = ['account_token' => $sample->account_password ?? ''];
        if ($manage_school_id) {
            $ret['manage_school_id'] = $manage_school_id;
        }
        return $ret;
    }

    //ログインAPIしてPOSTする。
    public function loginPost($url, $posted) {
        $manage_school_id = 1;
        $posted = array_merge(
            $posted,
            $this->getLoginToken('admin', $manage_school_id)
        );
        $this->post($url, $posted);
        $this->assertResponseOk();

        $responce = (string) $this->_response->getBody();
        $responce = json_decode($responce, true);
        return $responce;
    }

    //ログイン用のサンプルデータ
    public function roleSamples($role = '') {
        $list = [
            'student' => UsersFixture::getSample('student1'),
            'teacher' => UsersFixture::getSample('teacher1'),
            'school_admin' => UsersFixture::getSample('school_admin1'),
            'admin' => UsersFixture::getSample('admin1'),
        ];
        if ($role) {
            return $list[$role] ?? [];
        }
        return $list;
    }

    /**
     *
     * セッションまわり
     *
     */
    public function sessionDelete() {
        $sessions = $this->_requestSession->read();
        $empty_sessions = array_map(function ($session) {
            return null;
        }, $sessions);
        $this->session($empty_sessions);
    }

    //ログイン用
    public function addSessionLogin($role = 'user') {
        //用意しているサンプルデータ
        $sessionData = self::roleSamples($role);
        $Users = $this->getTableLocator()->get('Users');
        $account = $Users->find()->where(['Users.account_id' => ($sessionData['account_id'] ?? '')])->first();

        $this->session(['role' => $role]);
    }

    /**
     *
     *
     * ログインとページネーションのチェック
     * @param options = [url_arr => [controller => 〇〇, action => 〇〇], role = sample]
     *
     *
     */
    public function checkLoginAndPagination($options) {
        $options = array_merge(
            [
                'url_arr' => ['controller' => 'Admins', 'action' => 'index', 'prefix' => 'admin'],
                'allowRole' => ['student']
            ],
            $options
        );
        extract($options);

        $url = Router::url($url_arr);

        /**
         *
         * ログインチェック
         *
         */
        $this->checkLoginRedirect($url_arr, $allowRole);

        /**
         *
         * ページネーション確認
         *
         */
        foreach ($allowRole as $role) {
            //ログイン処理
            $this->addSessionLogin($role);

            //
            $gets = ['sch_limit' => 1];
            $query_url = Router::url(array_merge($url_arr, ['?' => $gets]));
            $this->get($query_url);

            $this->assertSession('1', "sch.{$this->Model}.sch_limit");
            $this->assertCount(1, $this->viewVariable('datas'));

            //セッション保存してあるlimitが適応されているか
            $this->session(["sch.{$this->Model}.sch_limit" => 1]);
            $this->get($url);
            $this->assertCount(1, $this->viewVariable('datas'));
        }
    }

    /**
     *
     * セーブ時のバリデーション確認
     *
     */
    //id以外の項目全てがエラー出ていること。
    public function checkValidateError($id, $request_data, $error_count = null) {
        //id以外の項目数
        $error_count = is_null($error_count) ? count($request_data) : $error_count;

        if (!is_null($id)) {
            $request_data = array_merge(['id' => $id], $request_data);
            $entity = $this->{$this->Model}->find()->where(["{$this->Model}.id" => $id])->first();
            $entity = $this->{$this->Model}->patchEntity($entity, $request_data);
        } else {
            $entity = $this->{$this->Model}->newEntity($request_data);
        }

        $this->assertCount($error_count, $entity->getErrors());
    }

    //保存できること
    public function checkValidateComplete($id, $request_data) {
        //保存
        if (!is_null($id)) {
            $request_data = array_merge(['id' => $id], $request_data);
            $entity = $this->{$this->Model}->find()->where(["{$this->Model}.id" => $id])->first();
            $entity = $this->{$this->Model}->patchEntity($entity, $request_data);
        } else {
            $entity = $this->{$this->Model}->newEntity($request_data);
        }
        $saved = $this->{$this->Model}->save($entity);
        $id = $id ? $id : $entity->id;

        //確認
        $entity = $this->{$this->Model}->find()->where(["{$this->Model}.id" => $id])->first();
        foreach ($request_data as $key => $_v) {
            $value = $entity->{$key} ?? '';
            if ($value == $_v) {
                continue;
            }

            $hasher = new DefaultPasswordHasher();
            if ($hasher->check($_v, $value)) {
                continue;
            }

            $this->assertEquals(true, false);
        }
        $this->assertEquals(true, true);
    }

    /**
     *
     * アップロードファイルを添付する
     *
     */
    public function convertPostAddUpload($filename, $posted) {
        //ファイル用意
        $testFile = TESTS . 'Fixture' . DS . 'files' . DS . $filename;
        $uploadedFile = new UploadedFile(
            $testFile,
            10,
            UPLOAD_ERR_OK,
            $filename,
            'application/octet-stream'
        );

        // 別途設定する
        $posted_key = 'up_file';
        $this->configRequest(
            [
                'files' => [
                    $posted_key => [
                        'error' => $uploadedFile->getError(),
                        'name' => $uploadedFile->getClientFilename(),
                        'size' => $uploadedFile->getSize(),
                        'tmp_name' => $testFile,
                        'type' => $uploadedFile->getClientMediaType(),
                    ]
                ]
            ]
        );

        return array_merge($posted, [
            $posted_key => $uploadedFile
        ]);
    }

    public function apiPost($url, $params = [], $token = '') {
        // //token
        // if ($fix_token = UsersFixture::getToken($token)) {
        //     $token = $fix_token;
        // }
        // if ($token) {
        //     $url .= '?token=' . $token;
        // }

        $this->post($url, $params);
        $responce = (string) $this->_response->getBody();
        $responce = json_decode($responce, true);
        return $responce;
    }

    public function apiGet($url, $params = [], $token = '') {
        $query = array_map(function ($key, $val) {
            return $key . '=' . $val;
        }, array_keys($params), $params);
        $query = implode('&', $query);
        $url .= (preg_match('/\?/u', $url) ? '&' : '?') . $query;

        $this->get($url);
        $responce = (string) $this->_response->getBody();
        $responce = json_decode($responce, true);
        return $responce;
    }
}
