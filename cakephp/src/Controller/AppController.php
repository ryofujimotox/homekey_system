<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;
use App\Utils\CustomUtility;
use Cake\Routing\Router;
use Cake\Auth\DefaultPasswordHasher;
use \SplFileObject;
use Cake\Utility\Hash;
use Cake\Http\Response;

class AppController extends Controller {
    public function initialize(): void {
        parent::initialize();

        $this->loadComponent('Flash');

        //json
        $this->loadComponent('RequestHandler');

        //コントローラー + アクション
        $controller = $this->request->getParam('controller');
        $action = $this->request->getParam('action');

        // セッション開始
        $this->Session = $this->getRequest()->getSession();
        $name = $this->Session->write('load', true);
    }

     /**
     *
     * 指定したキー以外をPOSTされたらブロックする。
     *
     */
    public function whiteRequest(array $requestKeys) {
        $post_data = $this->request->getData();
        foreach ($post_data as $key => $_val) {
            if (!in_array($key, $requestKeys)) {
                throw new NotFoundException(__('不正な値が含まれています'));
            }
        }
        return true;
    }
}
