<?php
namespace App\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;
use App\Utils\CustomUtility;
use Cake\Network\Exception\ForbiddenException;
use Cake\Network\Exception\NotFoundException;
use Cake\View\Exception\MissingTemplateException;

//

class AppForm extends Form {
    /**
    *
    */
    public function __construct($config = []) {
    }

    /**
     *
     * デフォルトのメール設定
     *
     */
    public $mailSetting = [
        'test' => [
            'auto_line_break' => true, //メールの自動改行する
            'from' => EMAIL_ADDRESS,
            'to_admin' => [EMAIL_ADDRESS],
            'name' => '藤本凌より',
            'subject_admin' => '【テスト】お問い合わせがありました。', //ない場合は管理者送信しない
            'subject_user' => '【テスト】お問い合わせありがとうございました!', //ない場合はユーザー送信しない
            // 'template_admin' => 'contact_admin',
            // 'template_user' => 'contact_user'
        ],
        'honban' => [
            'auto_line_break' => true, //メールの自動改行する
            'from' => EMAIL_ADDRESS,
            'to_admin' => [EMAIL_ADDRESS],
            'name' => '藤本凌より',
            'subject_admin' => 'お問い合わせがありました。', //ない場合は管理者送信しない
            'subject_user' => 'お問い合わせありがとうございました!', //ない場合はユーザー送信しない
            // 'template_admin' => 'contact_admin',
            // 'template_user' => 'contact_user'
        ]
    ];

    /**
     *
     * FormValidatorを変更する。
     *
     */
    public function validationDefault(Validator $validator): Validator {
        // $validator = new ContactValidator();
        return $validator;
    }

    /**
     *
     * バリデ前の変数変更 + バリデ + DB保存 + メール送信を行う。
     * フォームのデータを再構築するため、executeは使わない
     *
     */
    public function MyExecute(array $post_data) {
        $this->post_data = $this->_beforeExecure($post_data);
        return $this->execute($this->post_data);
    }

    /**
     *
     * バリデーション確認してから実行される。 (エラー時は実行されない)
     *
     */
    protected function _execute(array $data): bool {
        $this->setData($data);

        // メールを送信する
        return true;
    }

    //メール送信
    public function sendmail() {
        if (!CustomUtility::_sendmail($this->post_data, $this->mailSetting)) {
            return false;
        }
        return true;
    }

    /**
     *
     * execute前にフォームデータを再構築する。
     * トークン認証可否の変数も追加してる。
     *
     */
    public function _beforeExecure($post_data) {
        return $post_data;
    }
}
