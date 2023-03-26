<?php
namespace App\Utils;

use Cake\ORM\TableRegistry;
// use Cake\Utility\Hash;
//use App\Model\Entity\DokkyoLiteral;
use Cake\I18n\FrozenTime;
use Cake\I18n\Date;
use Cake\Utility\Hash;
//mail
use Cake\Mailer\Email;
use Cake\Core\Configure;
use Cake\Routing\Router;
//Utility
use App\Utils\PrefectureUtility;
use App\Utils\DateUtility;

/**
 *
 * メールまわり
 *
 */
class EmailUtility {
    //Formに変数登録することで上書きできる
    const setting_default = [
        'test' => [//テスト用
            'sendmail' => true, //falseなら送らない
            'from' => 'test@sample.web', //送信元
            'to_admin' => 'test@sample.web', //送信先(管理者のメールアドレス
            'name' => 'お名前', //送信者
            'subject_admin' => '【テスト】お問い合わせがありました', //管理者へのメールタイトル
            'subject_user' => '【テスト】お問い合わせを受け付けました', //ユーザーへのメールタイトル
            // 'template_admin' => 'default_admin', //管理者へのメールテンプレ
            // 'template_user' => 'default_user', //ユーザーへのメールテンプレ

            // 'content_admin' => '',
            // 'content_user' => '',
            'auto_line_break' => true, //自動で1000バイト改行
        ],
        'honban' => [//本番用
            'sendmail' => true,
            'from' => 'test@sample.web',
            'to_admin' => 'test@sample.web',
            'name' => 'お名前',
            'subject_admin' => '【本番】お問い合わせがありました',
            'subject_user' => '【本番】お問い合わせを受け付けました',
            // 'template_admin' => 'default_admin',
            // 'template_user' => 'default_user',

            // 'content_admin' => '',
            // 'content_user' => '',
            'auto_line_break' => true, //自動で1000バイト改行
        ]
    ];

    /**
     * メール送信
     */
    public static function _sendmail($post_data, $settings) {
        $mail_setting = self::mail_setting($settings);

        if ($mail_setting['auto_line_break']) {
            // 1000バイト強制改行問題対策
            foreach ($post_data as $key => $value) {
                if (is_array($value)) {
                    continue;
                }
                $post_data[$key] = CustomUtility::_preventGarbledCharacters($post_data[$key]);
            }
        }

        $r = true;

        try {
            // メール変数
            $mailVars = array();
            $mailVars = compact('post_data');

            // 管理者へメール
            if ($r && ($mail_setting['subject_admin'] ?? '')) {
                $email = new Email('default');
                $email->setCharset('ISO-2022-JP');
                $email->setFrom([$mail_setting['from'] => $mail_setting['name']]);
                $email->setTo($mail_setting['to_admin']);
                $email->setSubject($mail_setting['subject_admin']);
                if ($template = $mail_setting['template_admin'] ?? '') {
                    $email->setTemplate($template);
                }

                $email->setViewVars($mailVars);

                if ($content = $mail_setting['content_admin'] ?? '') {
                    $r = $email->send($content);
                } else {
                    $r = $email->send();
                }
            }

            // ユーザーへメール
            if ($r && ($mail_setting['subject_user'] ?? '') && isset($post_data['email'])) {
                $email = new Email('default');
                $email->setCharset('ISO-2022-JP');
                $email->setFrom([$mail_setting['from'] => $mail_setting['name']]);
                $email->setTo($post_data['email']);
                $email->setSubject($mail_setting['subject_user']);

                if ($template = $mail_setting['template_user'] ?? '') {
                    $email->setTemplate($template);
                }

                $email->setViewVars($mailVars);

                if ($content = $mail_setting['content_user'] ?? '') {
                    $r = $email->send($content);
                } else {
                    $r = $email->send();
                }
            }

            if (!$r) {
                throw new Exception('Error Processing Request', 1);
            }
        } catch (Exception $e) {
            throw new Exception('メール送信失敗' . $e);
            exit;
        }

        return $r;
    }

    /**
     * テスト環境かどうかでメール設定を変更する。
     */
    public static function mail_setting($settings) {
        $setting_type = Configure::read('debug') ? 'test' : 'honban';
        return array_merge(
            self::setting_default[$setting_type] ?? [],
            $settings[$setting_type] ?? [],
        );
    }
}
