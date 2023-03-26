<?php
namespace App\Exception;

use Cake\Core\Exception\Exception;

/**
 *
 */
class OriginalException extends Exception {
    // コンテキストデータはこのフォーマット文字列に差し込まれます。
    protected $_messageTemplate = '%s が見当たらないようです。';

    // デフォルトの例外コードも設定できます。
    protected $_defaultCode = 404;

    // public function __construct() {
    //     parent::__construct('This is a original exception');
    // }
}
