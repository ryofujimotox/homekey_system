<?php
declare(strict_types=1);
namespace App\Controller;

class HomesController extends AppController {
    public function initialize(): void {
        parent::initialize();

        $this->loadComponent('RequestHandler');

        $this->Rooms = $this->getTableLocator()->get('Rooms');
    }

    public function index() {
        $postdata = $this->request->getData();
        if ($postdata) {
            $action = $postdata['action'] ?? '';
            $password = implode('', $postdata['password'] ?? []);

            //
            $result = $this->Rooms->cmd($password, $action);
        }
    }
}
