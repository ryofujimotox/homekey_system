<?php
namespace App\Test;

use App\Test\Fixture\AdminsFixture;
use App\Test\Fixture\ShopsFixture;
// use App\Test\Fixture\ShopsFixture;
use Cake\Routing\Router;
use Cake\Auth\DefaultPasswordHasher;
use App\Utils\CustomUtility;

trait FixtureTrait {
    public $recordId;
    public $connection = 'test';

    public function setViewSample() {
        $viewable_samples = $this->getSamples($this->modelName);

        $now = new \Datetime();
        $before1minute = new \Datetime('-1 minute');
        $before2minute = new \Datetime('-2 minute');
        $yesterday = new \Datetime('-1 day');
        $tomorrow = new \Datetime('+1 day');

        foreach ($viewable_samples as $K => $data) {
            foreach ($data as $key => $value) {
                if ($value === 'yesterday') {
                    $data[$key] = $yesterday;
                }
                if ($value === 'now') {
                    $data[$key] = $now;
                }
                if ($value === 'tomorrow') {
                    $data[$key] = $tomorrow;
                }
                if ($value === 'before1minute') {
                    $data[$key] = $before1minute;
                }
                if ($value === 'before2minute') {
                    $data[$key] = $before2minute;
                }

                if ($key === '_account_password') {
                    $data['account_password'] = (new DefaultPasswordHasher)->hash($value);
                }
            }

            if (!($data['created'] ?? '')) {
                $data['created'] = $yesterday;
            }
            if (!($data['modified'] ?? '')) {
                $data['modified'] = $now;
            }
            if (!($data['updated'] ?? '')) {
                $data['updated'] = $now;
            }
            $this->records[] = $this->getRecord($data);
        }
    }

    //ログイン等で確認する必要がある項目
    public static $useSampleList = [
        [
            'key' => 'user1',
            'model' => 'users',
            'id' => 1,
        ],
        [
            'key' => 'user2',
            'model' => 'users',
            'id' => 2,
        ],
        [
            'key' => 'receive_message1',
            'model' => 'messages',
            'id' => 6,
        ]
    ];

    /**
     *
     */
    public static $viewable_samples = [];

    public static function getToken($sample) {
        $sample = self::getSample($sample);
        return $sample['token'] ?? '';
    }

    /**
     *
     * useSampleListのkeyを指定すると、そのデータを返す。
     *
     */
    public static function getSample($useSampleKey) {
        $useSampleList = self::$useSampleList;

        //useSampleKeyから、useSampleを取得する。
        $arrayKey = array_search($useSampleKey, array_column($useSampleList, 'key'));
        if ($arrayKey === false) {
            return [];
        }
        $needle = $useSampleList[$arrayKey] ?? [];
        extract($needle);

        //idから、samplesを取得する。
        $viewable_samples = self::getSamples($model);
        $arrayKey = array_search($id, array_column($viewable_samples, 'id'));
        if ($arrayKey === false) {
            return [];
        }
        $needle = $viewable_samples[$arrayKey] ?? [];

        return $needle;
    }

    /**
     *
     * 指定したモデル配下すべてを再帰的に取得する。
     *
     */
    public static function getSamples($targetKey) {
        return  CustomUtility::array_choice((self::$viewable_samples), $targetKey);
    }

    /**
     *
     * レコードの初期値を設定する。
     *
     * @param updated = [ id = 〇〇,,,,,]
     *
     */
    public function getRecord($updated = []) {
        $this->recordId = $updated['id'] ?? ($this->recordId + 1);
        $now = new \Datetime();

        return array_merge(
            $this->getDefaultColumn(),
            [
                'id' => $this->recordId,
                'created' => $now,
                'modified' => $now,
            ],
            $updated,
        );
    }

    /**
     *
     * テストデータの初期値
     *
     */
    public function getDefaultColumn() {
        return [];
    }
}
