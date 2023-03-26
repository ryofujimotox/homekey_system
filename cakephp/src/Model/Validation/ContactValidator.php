<?php
namespace App\Model\Validation;

use Cake\Validation\Validator;
use App\Model\Validation\DefaultValidator;

class ContactValidator extends DefaultValidator {
    public function requireAndLength($column, $maxLength) {
        $msg = '入力してください';
        $this->requirePresence($column, true, $msg);
        $this->notEmptyString($column, $msg);

        $msg = $maxLength . '文字以内で入力してください';
        $this->add($column, [
            'maxlength' => [
                'provider' => 'custom',
                'rule' => ['checkLength', 100],
                'message' => $msg
            ],
        ]);
    }

    public function __construct() {
        parent::__construct();

        $column = 'name';
        $msg = '入力してください';
        $this->requireAndLength($column, 100);

        $column = 'email';
        $msg = '入力してください';
        $this->requireAndLength($column, 100);

        $column = 'message';
        $msg = '入力してください';
        $this->requireAndLength($column, 1000);

        $column = 'ip';
        $msg = '入力してください';
        $this->requireAndLength($column, 50);
    }
}
