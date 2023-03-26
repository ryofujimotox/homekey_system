<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

class RoomsSeed extends AbstractSeed {
    public function run():void {
        $date = '2023-01-01 00:00:00';
        $crypt_key = env('SECURITY_SALT');
        $data = [
            [
                'created' => $date,
                'updated' => $date,
                'status' => 'publish',
                'sesame_id' => 1,
                'password' => openssl_encrypt(env('SAMPLE_ROOM_PASSWORD'), 'AES-128-ECB', $crypt_key),
            ],
        ];

        $table = $this->table('rooms');

        $table->insert($data)->save();
    }
}
