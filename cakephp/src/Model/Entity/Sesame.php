<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;
use Sesame\Sesame as SesameAPI;

class Sesame extends Entity {
    protected $_accessible = [
        '*' => true,
    ];
    // protected $_virtual = ['category'];
    // public function _getCategory() {
    //     $category = self::$category_list[$this->category_id ?? 0] ?? '';
    //     return $category;
    // }

    /**
     *
     * Sesame本体の状態を取得する
     *
     * @return array [ 電池残量, 角度, locked, unlocked, moved ]
     *
     */
    public function currentStatus(): array {
        $status = $this->getAPIClient()->status();
        return $status;
    }

    /**
     *
     * Sesame本体の解錠を行う
     *
     */
    public function currentOpen() {
        $result = $this->getAPIClient()->open();
        return $result;
    }

    /**
     *
     * Sesame本体の施錠を行う
     *
     */
    public function currentLock() {
        $result = $this->getAPIClient()->lock();
        return $result;
    }

    /**
     *
     * SesameAPIクライアントを取得する
     *
     */
    public function getAPIClient(): SesameAPI {
        $keys = $this->secretKeys();
        $sesameAPI = new SesameAPI($keys['uuid'], $keys['secret_key'], $keys['api_key']);

        return $sesameAPI;
    }

    /**
     *
     * SesameAPI用のシークレットキーを復号する
     * @return array [ uuid, secret_key, api_key ]
     *
     */
    public function secretKeys(): array {
        $crypt_key = env('SECURITY_SALT');
        $keys = [
            'uuid' => openssl_decrypt($this->uuid, 'AES-128-ECB', $crypt_key),
            'secret_key' => openssl_decrypt($this->secret_key, 'AES-128-ECB', $crypt_key),
            'api_key' => openssl_decrypt($this->api_key, 'AES-128-ECB', $crypt_key),
        ];

        return $keys;
    }
}
