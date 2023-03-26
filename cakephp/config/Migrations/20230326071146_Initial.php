<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class Initial extends AbstractMigration {
    public $autoId = false;

    /**
     * Up Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-up-method
     * @return void
     */
    public function up(): void {
        $this->table('rooms')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('created', 'datetime', [
                'comment' => '作成日時',
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('updated', 'datetime', [
                'comment' => '更新日時',
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('status', 'string', [
                'comment' => '公開状態',
                'default' => 'publish',
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('sesame_id', 'integer', [
                'comment' => 'セサミID',
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('password', 'string', [
                'comment' => '部屋パスワード',
                'default' => null,
                'limit' => 500,
                'null' => false,
            ])
            ->addIndex(
                [
                    'sesame_id',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('sesames')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('created', 'datetime', [
                'comment' => '作成日時',
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('updated', 'datetime', [
                'comment' => '更新日時',
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('status', 'string', [
                'comment' => '公開状態',
                'default' => 'publish',
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('uuid', 'string', [
                'comment' => 'セサミUUID',
                'default' => null,
                'limit' => 500,
                'null' => false,
            ])
            ->addColumn('secret_key', 'string', [
                'comment' => 'セサミ秘密鍵',
                'default' => null,
                'limit' => 500,
                'null' => false,
            ])
            ->addColumn('api_key', 'string', [
                'comment' => 'セサミAPI鍵',
                'default' => null,
                'limit' => 500,
                'null' => false,
            ])
            ->create();
    }

    /**
     * Down Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-down-method
     * @return void
     */
    public function down(): void {
        $this->table('rooms')->drop()->save();
        $this->table('sesames')->drop()->save();
    }
}
