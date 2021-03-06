<?php

use yii\db\Migration;

class m170216_090248_config extends Migration
{

    public $examTable = 'exam';
    public $ticketTable = 'ticket';

    public function safeUp()
    {

        // new config items
        $this->addColumn($this->examTable, 'grp_netdev', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn($this->examTable, 'allow_sudo', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn($this->examTable, 'allow_mount', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn($this->examTable, 'firewall_off', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn($this->examTable, 'screenshots', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn($this->examTable, 'url_whitelist', $this->string(512)->Null()->defaultValue(Null));
        $this->addColumn($this->examTable, 'file_analyzed', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn($this->examTable, 'sq_url_whitelist', $this->string(5120)->Null()->defaultValue(Null));
        $this->addColumn($this->examTable, 'backup_path', $this->string(1024)->notNull()->defaultValue('/home/user'));

        $this->addColumn($this->ticketTable, 'backup_interval', $this->integer(11)->notNull()->defaultValue(300));
        $this->addColumn($this->ticketTable, 'online', $this->boolean()->Null()->defaultValue(Null));
        $this->addColumn($this->ticketTable, 'backup_size', $this->integer(11)->notNull()->defaultValue(0));

        $this->addColumn($this->examTable, 'time_limit', $this->integer(11)->Null()->defaultValue(Null));
        $this->addColumn($this->ticketTable, 'time_limit', $this->integer(11)->Null()->defaultValue(Null));

    }

    public function safeDown()
    {
        $this->dropColumn($this->examTable, 'grp_netdev');
        $this->dropColumn($this->examTable, 'allow_sudo');
        $this->dropColumn($this->examTable, 'allow_mount');
        $this->dropColumn($this->examTable, 'firewall_off');
        $this->dropColumn($this->examTable, 'screenshots');
        $this->dropColumn($this->examTable, 'url_whitelist');
        $this->dropColumn($this->examTable, 'file_analyzed');
        $this->dropColumn($this->examTable, 'sq_url_whitelist');
        $this->dropColumn($this->examTable, 'backup_path');

        $this->dropColumn($this->ticketTable, 'backup_interval');
        $this->dropColumn($this->ticketTable, 'online');
        $this->dropColumn($this->ticketTable, 'backup_size');

        $this->dropColumn($this->examTable, 'time_limit');
        $this->dropColumn($this->ticketTable, 'time_limit');
    }

}
