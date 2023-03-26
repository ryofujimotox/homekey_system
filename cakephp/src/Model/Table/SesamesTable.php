<?php
declare(strict_types=1);

namespace App\Model\Table;

// use App\Utils\CustomUtility;

class SesamesTable extends AppTable {
    public $attaches = [
        'images' => [],
        'files' => [],
    ];

    public function initialize(array $config): void {
        parent::initialize($config);

        // 部屋情報
        $this->hasMany('Rooms');
    }
}
