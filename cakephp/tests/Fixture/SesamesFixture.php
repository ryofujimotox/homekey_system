<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use App\Test\FixtureTrait;

class SesamesFixture extends TestFixture {
    use FixtureTrait;

    public $modelName = 'sesames';
    public $import = ['model' => 'sesames', 'connection' => 'default'];

    /**
     *
     * サンプルデータを導入する。
     *
     */
    public function init(): void {
        //traitで手入力したデータを挿入する。 紐付けがわかりやすい。
        $this->setViewSample();

        //
        $this->records = $this->getDefaultColumn();

        parent::init();
    }

    /**
     *
     * テストデータの初期値
     *
     */
    public function getDefaultColumn() {
        $data = [];

        $date = '2023-01-01 00:00:00';
        $crypt_key = env('SECURITY_SALT');
        $data = [
            [
                'created' => $date,
                'updated' => $date,
                'status' => 'publish',
                'uuid' => openssl_encrypt(env('SAMPLE_SESAME_UUID'), 'AES-128-ECB', $crypt_key),
                'secret_key' => openssl_encrypt(env('SAMPLE_SESAME_SECRET_KEY'), 'AES-128-ECB', $crypt_key),
                'api_key' => openssl_encrypt(env('SAMPLE_SESAME_API_KEY'), 'AES-128-ECB', $crypt_key),
            ],
        ];

        return $data;
    }
}
