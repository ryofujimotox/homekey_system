<?php
declare(strict_types=1);

namespace App\Model\Table;

// use App\Utils\CustomUtility;

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
     * entityを変換する。
     */
    public function toApiList($data): array {
        return [
            'id' => $data['id'],
        ];
    }
}
