<?php

use think\migration\Migrator;
use think\migration\db\Column;

class Customers extends Migrator
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $table = $this->table('customers', ['engine' => 'InnoDB']);
        $table->addColumn('uid', 'string', ['limit' => 32, 'default'=>'', 'comment' => '用户id'])
            ->addColumn('name', 'string', ['limit' => 55, 'default'=> '', 'comment' => '用户名'])
            ->addColumn('avatar', 'string', ['limit' => 32, 'default'=> '', 'comment' => '用户头像'])
            ->addColumn('ip', 'string', ['limit' => 15, 'default' => '', 'comment' => '用户ip'])
            ->addIndex(['uid'], ['unique' => true])
            ->create();
    }
}
