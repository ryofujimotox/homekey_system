<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use App\Test\TestTrait;

class SesameTableTest extends TestCase {
    use TestTrait;

    public $fixtures = [
        'app.Sesames',
    ];

    public function setUp(): void {
        parent::setUp();

        $this->Sesames = $this->getTableLocator()->get('Sesames');
        // $this->loadRoutes();
        // $this->disableErrorHandlerMiddleware();
    }

    /**
     *
     *
     */
    public function testInformationIndex() {
        $sesame = $this->Sesames->find()->first();
        pr($sesame->currentStatus());
        exit;
    }
}
