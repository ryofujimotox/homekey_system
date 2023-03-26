<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

class SesamesSeed extends AbstractSeed {
    public function run():void {
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

        $table = $this->table('sesames');

        $table->insert($data)->save();
    }
}
