<?php

declare(strict_types=1);

namespace App\Controller\Admin;

//
use App\Controller\AppController as BaseController;
use Cake\Event\Event;
use App\Utils\CustomUtility;
use Cake\Routing\Router;
use Cake\Auth\DefaultPasswordHasher;
use \SplFileObject;
use Cake\Utility\Hash;
use Cake\Http\Response;
//
use Cake\Http\Exception\NotFoundException;

class AppController extends BaseController {
    public function initialize(): void {
        parent::initialize();
    }
}
