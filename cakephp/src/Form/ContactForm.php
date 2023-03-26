<?php
namespace App\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Mailer\Email;
use Cake\Core\Configure;
//
use App\Form\AppForm;
use App\Model\Validation\ContactValidator;
use App\Utils\CustomUtility;

class ContactForm extends AppForm {
    /**
     *
     * メール送信
     *
     */
    public function sendmail() {
        extract($this->post_data);
        $content_admin = CustomUtility::implode_n(
            [
                'お問い合わせがありました。',
                '',
                '【名前】',
                $name,
                '',
                '【メールアドレス】',
                $email,
                '',
                '【内容】',
                $message,
            ]
        );
        $content_user = CustomUtility::implode_n(
            [
                'お問い合わせありがとうございました。',
                '',
                '【名前】',
                $name,
                '',
                '【メールアドレス】',
                $email,
                '',
                '【内容】',
                $message,
            ]
        );

        $mailSetting = [
            'test' => [
                'auto_line_break' => true, //メールの自動改行する
                'from' => EMAIL_ADDRESS,
                'to_admin' => [EMAIL_ADDRESS],
                'name' => 'TEST',
                'subject_admin' => '【テスト】お問い合わせがありました。', //ない場合は管理者送信しない
                'subject_user' => '【テスト】お問い合わせありがとうございました!', //ない場合はユーザー送信しない
                // 'template_admin' => 'contact_admin',
                // 'template_user' => 'contact_user'

                'content_admin' => $content_admin,
                'content_user' => $content_user,
            ],
            'honban' => [
                'auto_line_break' => true, //メールの自動改行する
                'from' => EMAIL_ADDRESS,
                'to_admin' => [EMAIL_ADDRESS],
                'name' => 'TEST',
                'subject_admin' => 'お問い合わせがありました。', //ない場合は管理者送信しない
                'subject_user' => 'お問い合わせありがとうございました!', //ない場合はユーザー送信しない
                // 'template_admin' => 'contact_admin',
                // 'template_user' => 'contact_user'

                'content_admin' => $content_admin,
                'content_user' => $content_user,
            ]
        ];

        if (!CustomUtility::_sendmail($this->post_data, $mailSetting)) {
            return false;
        }
        return true;
    }

    /**
     *
     * FormValidatorを変更する。
     *
     */
    public function validationDefault(Validator $validator): Validator {
        $validator = new ContactValidator();
        return $validator;
    }

    /**
     *
     * バリデ前の変換
     *
     */
    public function _beforeExecure($post_data) {
        // $post_data['tes'] = 'OK';
        // $this->Contacts = TableRegistry::getTableLocator()->get('Contacts');

        return parent::_beforeExecure($post_data);
    }

    /**
     *
     * バリデーションのみ
     *
     */
    public function validation(array $post_data) {
        return $this->MyExecute($post_data);
    }

    /**
     *
     * 送信
     *
     */
    public function send(array $post_data) {
        $this->Contacts = TableRegistry::getTableLocator()->get('Contacts');

        // 1分間に3回以上のPOSTを行なっていないかチェックする
        $isBlockedIp = $this->Contacts->isBlockedIp($post_data['ip'] ?? 0);
        if ($isBlockedIp) {
            return false;
        }

        // 検証
        $validate = $this->MyExecute($post_data);
        if (!$validate) {
            return false;
        }

        // 送信処理
        $send = $this->sendmail();
        if (!$send) {
            return false;
        }

        // 保存処理
        $saved = $this->saveAfterValidate($this->post_data);
        if (!$saved) {
            return false;
        }

        //
        return true;
    }

    /**
     *
     *
     *
     */
    public function saveAfterValidate($data) {
        $entity = $this->Contacts->newEntity($data);
        $saved = $this->Contacts->save($entity);

        return $saved;
    }
}
