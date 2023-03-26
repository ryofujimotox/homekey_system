<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use App\Test\TestTrait;

class RoomTableTest extends TestCase {
    use TestTrait;

    public $fixtures = [
        'app.Sesames',
        'app.Rooms',
    ];

    public function setUp(): void {
        parent::setUp();

        $this->Rooms = $this->getTableLocator()->get('Rooms');
        // $this->loadRoutes();
        // $this->disableErrorHandlerMiddleware();
    }

    /**
     *
     *
     */
    public function testRoomPassword() {
        // 存在確認
        $password = env('SAMPLE_ROOM_PASSWORD');
        $got_status = (bool) $this->Rooms->search($password)->sesame->currentStatus();
        $this->assertEquals(true, $got_status);

        // 存在確認
        $password = '001';
        $got_status = (bool) $this->Rooms->search($password)->sesame;
        $this->assertEquals(false, $got_status);

        // 存在確認
        $password = 'error';
        $got_status = (bool) $this->Rooms->search($password);
        $this->assertEquals(false, $got_status);
    }
}
