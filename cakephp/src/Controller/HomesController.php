<?php
declare(strict_types=1);
namespace App\Controller;

use Cake\Event\EventInterface;
//
use App\Utils\ImageUtility;
use App\Utils\AnalyzeUtility;

class HomesController extends AppController {
    public function initialize(): void {
        parent::initialize();

        $this->loadComponent('RequestHandler');

        $this->Informations = $this->getTableLocator()->get('Informations');
    }

    public function index() {
        // $this->autoRender = false;
    }
}
