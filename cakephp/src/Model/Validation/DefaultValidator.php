<?php
namespace App\Model\Validation;

use Cake\Validation\Validator;

class DefaultValidator extends Validator {
    public function __construct() {
        parent::__construct();

        $this->setProvider('custom', 'App\Model\Validation\Validations');

        //文字数を確認する
        self::validationLength($this);

        //入力があったときに、適切な値か確認する。 (email等)
        self::validationType($this);
    }

    //文字数バリデーション
    public static function validationLength(Validator $validator) {
        //文字数
        $column_lngeth_array = [
            VALID_RANGE_TEXT => ['name', 'email', 'email_confirm', 'furi', 'kana'],
            VALID_RANGE_TEL => ['tel'],
            VALID_RANGE_ZIP => ['zip'],
        ];

        //
        foreach ($column_lngeth_array as $maxLength => $columns) {
            foreach ($columns as $column) {
                $validator->allowEmptyString($column);
                $validator->add($column, 'custom', [
                    'rule' => function ($value, $context) use ($maxLength) {
                        return mb_strlen($value) <= $maxLength;
                    },
                    'message' => "{$maxLength}文字以内で入力してください",
                    'allowEmpty' => true,
                ]);
            }
        }

        return $validator;
    }

    //種別バリデーション　（email等
    public static function validationType(Validator $validator) {
        $column = 'gender_id';
        $validator->allowEmptyString($column);
        $validator->naturalNumber($column, '選択してください');

        $column = 'email';
        $validator->allowEmptyString($column);
        $validator->email($column, false, '利用できないメールアドレスです');
        $maxLength = 1000;
        $validator->add($column, [
            'maxlength' => [
                'provider' => 'custom',
                'rule' => ['checkLength', $maxLength],
                'message' => $maxLength . '文字以内で入力してください'
            ],
        ]);

        $column = 'name';
        $validator->allowEmptyString($column);
        $maxLength = 500;
        $validator->add($column, [
            'maxlength' => [
                'provider' => 'custom',
                'rule' => ['checkLength', $maxLength],
                'message' => $maxLength . '文字以内で入力してください'
            ],
        ]);

        $column = 'furi';
        $validator->allowEmptyString($column);
        $validator->add($column, [
            'notMatch' => [
                'rule' => ['custom', '/^[ぁ-ん\ \　]+$/u'],
                //伸ばし棒あり
                // 'rule' => ['custom', '/^[ぁ-ん\ \　\-\ー]+$/u'],
                'message' => 'よみがなを入力してください'
            ],
        ]);

        $column = 'tel';
        $validator->allowEmptyString($column);
        $validator->add($column, [
            'notMatch' => [
                'provider' => 'custom',
                'rule' => 'checkTel',
                'message' => '電話番号を半角数字で正しく入力してください'
            ],
        ]);

        $column = 'zip';
        $validator->allowEmptyString($column);
        if (VALID_RANGE_ZIP == 8) {
            //ハイフンあり
            $validator->add($column, [
                'notMatch' => [
                    'provider' => 'custom',
                    'rule' => 'checkPostcodeHaihun',
                    'message' => '郵便番号を半角数字(ハイフンあり)で入力してください'
                ],
            ]);
        } else {
            //ハイフンなし
            $validator->add($column, [
                'notMatch' => [
                    'provider' => 'custom',
                    'rule' => 'checkPostcode',
                    'message' => '郵便番号を半角数字(ハイフンなし)で入力してください'
                ],
            ]);
        }

        $column = 'email_confirm';
        $validator->allowEmptyString($column);
        $validator->add($column, [
            'notMatch' => [
                'provider' => 'custom',
                'rule' => ['compare', 'email'],
                'message' => '同じメールアドレスを入力してください'
            ],
        ]);

        $column = 'privacy';
        $validator->allowEmptyString($column);
        $validator->add($column, [
            'notMatch' => [
                'provider' => 'custom',
                'rule' => 'checkPolicy',
                'message' => 'プライバシーポリシーに同意してください',
                'last' => true
            ]
        ]);

        return $validator;
    }
}
