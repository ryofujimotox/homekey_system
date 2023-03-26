<?php
declare(strict_types=1);

namespace App\Model\Table;

// use App\Utils\CustomUtility;
use Cake\ORM\Entity;
use App\Model\Entity\Room as RoomEntity;

class RoomsTable extends AppTable {
    public $attaches = [
        'images' => [],
        'files' => [],
    ];

    public function initialize(array $config): void {
        parent::initialize($config);

        // セサミ
        $this->belongsTo('Sesames');
    }

    /**
     *
     * 鍵に合う部屋を探す
     *
     * @param string $password 12345とか
     * @return ?RoomEntity contained sesame
     *
     */
    public function search(string $password): ?RoomEntity {
        $crypt_key = env('SECURITY_SALT');
        $encrypt_password = openssl_encrypt($password, 'AES-128-ECB', $crypt_key);

        //
        $room = $this->find()->contain(['Sesames'])->where(['Rooms.password' => $encrypt_password])->first();
        return $room;
    }
}
