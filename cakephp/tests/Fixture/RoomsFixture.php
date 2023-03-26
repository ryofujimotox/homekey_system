<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use App\Test\FixtureTrait;

class RoomsFixture extends TestFixture {
    use FixtureTrait;

    public $modelName = 'rooms';
    public $import = ['model' => 'rooms', 'connection' => 'default'];

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
                'sesame_id' => 1,
                'password' => openssl_encrypt(env('SAMPLE_ROOM_PASSWORD'), 'AES-128-ECB', $crypt_key)
            ],
            [
                'created' => $date,
                'updated' => $date,
                'status' => 'publish',
                'sesame_id' => 2,
                'password' => openssl_encrypt('001', 'AES-128-ECB', $crypt_key)
            ],
        ];
        return $data;
    }
}
