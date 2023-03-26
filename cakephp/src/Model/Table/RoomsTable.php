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
     * 部屋の解施錠
     *
     * @param string $password 12345
     * @param string $cmd "open" | "lock"
     *
     * @return bool
     *
     */
    public function cmd(string $password, string $cmd = 'open'): bool {
        $room = $this->search($password);
        if (!$room) {
            return false;
        }

        switch ($cmd) {
            case 'open':
                $room->sesame->currentOpen();
                return true;
            case 'lock':
                $room->sesame->currentLock();
                return true;
            default:
                return false;
        }
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
