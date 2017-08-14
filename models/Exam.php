<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "exam".
 *
 * @property integer $id
 * @property string $name
 * @property string $subject
 * @property boolean $grp_netdev
 * @property boolean $allow_sudo
 * @property boolean $allow_mount
 * @property boolean $firewall_off
 * @property boolean $screenshots
 * @property string $file
 * @property integer $user_id
 * @property string $file_list
 *
 * @property User $user
 * 
 * @property Ticket[] $tickets
 * @property integer ticketCount
 * @property integer openTicketCount
 * @property integer runningTicketCount
 * @property integer closedTicketCount
 */
class Exam extends \yii\db\ActiveRecord
{

    /**
     * @var UploadedFile
     */
    public $examFile;
    public $filePath;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'exam';
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        $instance = $this;
        $this->on(self::EVENT_BEFORE_INSERT, function($instance){
            $this->user_id = \Yii::$app->user->id;
        });

        $this->on(self::EVENT_BEFORE_UPDATE, function($instance){
            if ($this->getOldAttribute('file') != $this->file) {
                $this->md5 = $this->file == null ? null : md5_file($this->file);
                $this->{"file_analyzed"} = 0;
            }
        });

        $this->backup_path = $this->isNewRecord ? '/home/user' : $this->backup_path;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'name', 'subject', 'examFile', 'user_id', 'grp_netdev', 'allow_sudo', 'allow_mount', 'firewall_off', 'screenshots', 'url_whitelist', 'time_limit'], 'validateRunningTickets'],
            [['name', 'subject', 'backup_path'], 'required'],
            [['time_limit'], 'integer', 'min' => 0],
            [['user_id'], 'integer'],
            [['name', 'subject'], 'string', 'max' => 52],
            [['file'], 'file', 'skipOnEmpty' => true, 'extensions' => 'squashfs', 'checkExtensionByMimeType' => false],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'subject' => 'Subject',
            'file' => 'File',
            'md5' => 'MD5 Checksum',
            'file_list' => 'File List',
            'user_id' => 'User ID',
            'grp_netdev' => 'User can edit network connections',
            'allow_sudo' => 'User can gain root privileges by sudo',
            'allow_mount' => 'User has access to external filesystems such as USB Sticks',
            'firewall_off' => 'Disable Firewall',
            'screenshots' => 'Take Screenshots',
            'url_whitelist' => 'HTTP URL Whitelist',
            'time_limit' => 'Time Limit',
            'backup_path' => 'Remote Backup Path',
            'ticketCount' => 'Total Tickets',
            'openTicketCount' => 'Open Tickets',
            'runningTicketCount' => 'Running Tickets',
            'closedTicketCount' => 'Closed Tickets',
            'submittedTicketCount' => 'Submitted Tickets',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'name' => 'The name of the exam. This value may not be unique, but it should be used to <b>identify the exam</b>.',
            'subject' => 'The school subject.',
            'time_limit' => 'If this value is set, the exam status view of the student will show time left. This has <b>NO indication</b> elsewhere. It is just of informative purpose. Set to <code>0</code> or leave empty for no time limit.',
            'grp_netdev' => 'If set, the exam student will be in the network group <code>netdev</code>. Members of this group can manage network interfaces through the network manager and wicd. Notice, that the student will be able to leave the exam network. <b>This should not be set, unless you know what you are doing.</b>',
            'allow_sudo' => 'If set, the exam student will be able to switch to the root user <b>without password</b>. <b>This should not be set, unless you know what you are doing.</b>',
            'allow_mount' => 'If set, the exam student will be able to mount external filesystems such as USB Sticks, Harddrives or Smartcard. <b>This should not be set, unless you know what you are doing.</b>',
            'firewall_off' => 'If set, disables the Firewall and access to all network resourses is given. <b>This should not be set, unless you know what you are doing.</b>',
            'screenshots' => 'If set, the system will create screenshots every 5 minutes. Those screenshots will appear in the Ticket view under the register "Screenshots". When generating exam results, they can also be included.',
            'url_whitelist' => 'URLs given in this list will be allowed to visit by the exam student during the exam. Notice, due to this date, only URLs starting with <code>http://</code> are supported, therefore <code>https://</code> URLs will be ignored. The URLs should be provided newline separated. Those URLs are allowed even if the Firewall is enabled.',
            'backup_path' => 'Specifies the <b>directory to backup</b> at the target machine. This should be an absolute path. Mostly this is set to <code>/home/user</code>, which is the home directory of the user under which the exam is taken. The exam server will then backup the ALL files in <code>/home/user</code> that have changed since the exam started.',
            'file' => 'The exam is a squashfs file. Squashfs is a highly compressed read-only filesystem for Linux. This file contains all changes made on the original machine to setup the exam. These changes are applied to the exam system as soon as the exam starts.',
        ];
    }

    /**
     * @return boolean
     */
    public function upload()
    {
        $this->filePath = \Yii::$app->params['uploadPath'] . '/' . generate_uuid() . '.' . $this->file->extension;

        if ($this->validate(['file'])) {
            return $this->file->saveAs($this->filePath, true);
        } else {
            return false;
        }
    }

    /**
     * @return boolean
     */
    public function deleteFile()
    {
        $file = $this->file;
        $this->file = null;
        $this->md5 = null;
        $this->{"file_analyzed"} = 0;

        if (file_exists($file) && is_file($file) && $this->save()) {
            return @unlink($file);
        }else{
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        $this->deleteFile();
        return parent::delete();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * @return int
     */
    public function getTicketCount()
    {
        return intval($this->getTickets()->count());
    }

    /**
     * @return int
     */
    public function getOpenTicketCount()
    {
        return intval($this->getTickets()->where(["NULLIF(start, '')" => null, "NULLIF(end, '')" => null ])->count());
    }

    /**
     * @return int
     */
    public function getRunningTicketCount()
    {
        return intval($this->getTickets()->where([ 'and', [ 'not', [ "NULLIF(start, '')" => null ] ], [ "NULLIF(end, '')" => null ] ])->count());
    }

    /**
     * @return int
     */
    public function getClosedTicketCount()
    {
        return intval($this->getTickets()->where([ 'and', [ 'not', [ "NULLIF(start, '')" => null ] ], [ 'not', [ "NULLIF(end, '')" => null ] ], [ "NULLIF(test_taker, '')" => null ] ])->count());

    }

    /**
     * @return int
     */
    public function getSubmittedTicketCount()
    {
        return intval($this->getTickets()->where([ 'and', [ 'not', [ "NULLIF(start, '')" => null ] ], [ 'not', [ "NULLIF(end, '')" => null ] ], [ 'not', [ "NULLIF(test_taker, '')" => null ] ] ])->count());

    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTickets()
    {
        return $this->hasMany(Ticket::className(), ['exam_id' => 'id']);
    }

    /**
     * @return string
     */
    public function getFileSize()
    {
        return Yii::$app->file->set($this->file)->size;
    }

    /**
     * @return string
     */
    public function getFileInfo()
    {
        return str_replace(',', '<br>', Yii::$app->file->set($this->file)->info);
    }

    /**
     * @return boolean
     */
    public function getFileConsistency()
    {
        return (strpos($this->fileInfo, 'Squashfs') === false ? false : true);
    }

    /**
     * @return string
     */
    public function getInfo()
    {
        //var_dump(Yii::$app->squashfs->set($this->file));die();
        $x = '';
        //$x .= 'root passwoord ' . (Yii::$app->squashfs->set($this->file)->file_exists('/etc/passwd') ? 'not ' : '') . 'set.';
        return $x;
    }

    public function validateRunningTickets($attribute, $params)
    {
        $this->runningTicketCount != 0 ? $this->addError($attribute, 'Exam update is disabled while there are ' . $this->runningTicketCount . ' tickets in "Running" state.') : null;
    }

    /**
     * @inheritdoc
     * @return ExamQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ExamQuery(get_called_class());
    }


}
