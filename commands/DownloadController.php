<?php

namespace app\commands;

use yii;
use yii\db\Expression;
use app\commands\DaemonController;
use app\models\Ticket;
use app\models\Daemon;
use app\models\Activity;
use app\components\ShellCommand;
use yii\helpers\FileHelper;
use yii\helpers\Console;
use app\models\BackupSearch;
use app\models\EventItem;
use app\models\DaemonInterface;
use app\models\Issue;

/**
 * Download Daemon (push)
 * This is the daemon which calls rsync to push the exam to the clients one by one.
 */
class DownloadController extends DaemonController implements DaemonInterface
{

    /**
     * @var Ticket The ticket in processing at the moment 
     */
    public $ticket;

    /**
     * @var string The user to login at the target system
     */
    public $remoteUser = 'root';

    /**
     * @var string The path at the target system to create the shutdown filesystem
     */
    public $remotePath = '/run/initramfs';

    /**
     * @inheritdoc
     */
    public function start()
    {
        parent::start();
    }

    /**
     * @inheritdoc
     */
    public function doJobOnce ($id = '')
    {
        if (($this->ticket = $this->getNextItem()) !== null) {
            $this->processItem($this->ticket);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function doJob ($id = '')
    {

        $this->calcLoad(0);
        while (true) {
            pcntl_signal_dispatch();
            $this->cleanup();

            if ($id != '') {
                if (($this->ticket = Ticket::findOne(['ticket.id' => $id, 'download_lock' => 0, 'bootup_lock' => 1])) == null){
                    $this->logError('Error: ticket with id ' . $id . ' not found, it is already in processing.');
                    return;
                }
                
                if (!$this->lockItem($this->ticket)) {
                    $this->logError('Error: ticket with id ' . $id . ' not found, it is already in processing (flock).');
                    return;
                }
            }

            if ($this->ticket == null) {
                $this->logInfo('idle', true, false);
                do {
                    sleep(rand(5, 10));
                    $this->calcLoad(0);
                } while (($this->ticket = $this->getNextItem()) === null);
            }

            $this->processItem($this->ticket);
            $this->calcLoad(1);

            if ($id != '') {
                return;
            }

        }

    }

    /**
     * @inheritdoc
     */
    public function processItem ($ticket)
    {

        $this->ticket = $ticket;
        $this->logInfo('Processing ticket (download): ' .
            ( empty($this->ticket->test_taker) ? $this->ticket->token : $this->ticket->test_taker) .
            ' (' . $this->ticket->ip . ')', true);
        $this->ticket->download_state = yiit('ticket', 'Connecting to client ...');
        $this->ticket->save(false);

        if ($this->checkPort(22, 3, $emsg) === false) {
            Issue::markAs(Issue::CLIENT_OFFLINE, $this->ticket->id);

            $this->ticket->online = false;
            $this->ticket->download_state = yiit('ticket', 'Download failed: network error, {error}.');
            $this->ticket->download_state_params = ['error' => $emsg];
            $this->unlockItem($this->ticket);


            $act = new Activity([
                    'ticket_id' => $this->ticket->id,
                    'description' => yiit('activity', 'Download failed: network error, {error}.'),
                    'description_params' => ['error' => $emsg],
                    'severity' => Activity::SEVERITY_ERROR,
            ]);
            $act->save();

        } else {
            Issue::markAsSolved(Issue::CLIENT_OFFLINE, $this->ticket->id);

            $this->ticket->scenario = Ticket::SCENARIO_DOWNLOAD;
            $this->ticket->online = $this->ticket->runCommand('true', 'C', 10)[1] == 0 ? true : false;
            $this->ticket->client_state = yiit('ticket', 'download in progress') . ' ...';
            $this->ticket->runCommand('echo "download in progress" > ' . $this->remotePath . '/state');
            $this->ticket->save(false);

            // create a temporary directory
            $tempDir = sys_get_temp_dir() . "/" . generate_uuid();
            mkdir($tempDir);

            // all contents in this directory are rsynced to the client
            if (file_exists($this->ticket->exam->file)) {
                symlink($this->ticket->exam->file, $tempDir . "/exam.squashfs");
            }
            if (file_exists($this->ticket->exam->file2)) {
                symlink($this->ticket->exam->file2, $tempDir . "/exam.zip");
            }
            if (file_exists(\Yii::$app->params['sciptsPath'] . "/mount.sh")) {            
                symlink(\Yii::$app->params['sciptsPath'] . "/mount.sh", $tempDir . "/mount.sh");
            }

            $cmd = "rsync -L --checksum --partial --progress "
                 . "--bwlimit=" . escapeshellarg(\Yii::$app->params['examDownloadBandwith']) . " "
                 . "--rsh='ssh -i " . \Yii::$app->params['dotSSH'] . "/rsa "
                 . " -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no' "
                 . $tempDir . '/*' . " "
                 . escapeshellarg($this->remoteUser . "@" . $this->ticket->ip . ":" . $this->remotePath . '/squashfs/') . " "
                 . "| stdbuf -oL tr '\\r' '\\n' ";

            $this->logInfo('Executing rsync: ' . $cmd);

            $cmd = new ShellCommand($cmd);
            $output = "";
            $logFile = Yii::getAlias('@runtime/logs/download.' . $this->ticket->token . '.' . date('c') . '.log');

            @mkdir(dirname($logFile), 0755, true);

            $cmd->on(ShellCommand::COMMAND_OUTPUT, function($event) use (&$output, $logFile) {
                echo $this->ansiFormat($event->line, $event->channel == ShellCommand::STDOUT ? Console::NORMAL : Console::FG_RED);
                $output .= $event->line;
                preg_match('/\s([0-9]+)\%/', $event->line, $match);
                if (isset($match[1])) {
                    $this->ticket->download_progress = intval($match[1])/100;
                    $this->ticket->save();
                }
                @file_put_contents($logFile, $event->line, FILE_APPEND);
            });

            $retval = $cmd->run();

            // remove the temporary directory
            @unlink($tempDir . "/exam.squashfs");
            @unlink($tempDir . "/exam.zip");
            @unlink($tempDir . "/mount.sh");
            @rmdir($tempDir);

            if ($retval != 0) {
                $this->logError('rsync failed (retval: ' . $retval . '), output: ' . PHP_EOL . $output);

                $act = new Activity([
                        'ticket_id' => $this->ticket->id,
                        'description' => yiit('activity', 'Download failed: rsync failed (retval: {retval})'),
                        'description_params' => [ 'retval' => $retval ],
                        'severity' => Activity::SEVERITY_ERROR,
                ]);
                $act->save();

                $this->ticket->download_state = yiit('ticket', "Download failed: rsync failed");
                $this->unlockItem($this->ticket);
            } else {

                if ($this->ticket->test_taker) {
                    $act = new Activity([
                        'ticket_id' => $this->ticket->id,
                        'description' => yiit('activity', 'Exam download finished by {ip} from {test_taker}.'),
                        'description_params' => [
                            'ip' => $this->ticket->ip,
                            'test_taker' => $this->ticket->test_taker,
                        ],
                        'severity' => Activity::SEVERITY_SUCCESS,
                    ]);
                } else {
                    $act = new Activity([
                        'ticket_id' => $this->ticket->id,
                        'description' => yiit('activity', 'Exam download finished by {ip} from Ticket with token {token}.'),
                        'description_params' => [
                            'ip' => $this->ticket->ip,
                            'token' => $this->ticket->token,
                        ],
                        'severity' => Activity::SEVERITY_SUCCESS,
                    ]);
                }
                $act->save();

                $this->ticket->download_progress = 1;
                $this->ticket->client_state = yiit('ticket', 'download finished');
                $this->ticket->download_finished = new Expression('NOW()');
                $this->unlockItem($this->ticket);

                /* if there is a backup available, restore the latest */
                $backupSearchModel = new BackupSearch();
                $backupDataProvider = $backupSearchModel->search($this->ticket->token);
                if ($backupDataProvider->totalCount > 0) {
                    $restoreDaemon = new Daemon();
                    /* run the restore daemon in the foreground */
                    /* restore all that was backed up AND the screen_capture files as well */
                    $pid = $restoreDaemon->startRestore($this->ticket->id, '::All::', 'now', false, '/run/initramfs/backup/' . $this->ticket->exam->backup_path);
                }

                $this->ticket->client_state = yiit('ticket', 'preparing system');
                $this->ticket->save();

                /* run the prepare.sh script on the client */
                $cmd = "cat " . \Yii::$app->basePath . "/scripts/prepare.d/* " . \Yii::$app->basePath . "/scripts/prepare.sh "
                     . "| ssh -i " . \Yii::$app->params['dotSSH'] . "/rsa -o "
                     . "UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no "
                     . escapeshellarg($this->remoteUser . "@" . $this->ticket->ip) . " "
                     . "'bash -s' " . escapeshellarg($this->ticket->token);

                $this->logInfo('Executing ssh: ' . $cmd);

                $cmd = new ShellCommand($cmd);

                $cmd->on(ShellCommand::COMMAND_OUTPUT, function($event) use (&$output, $logFile) {
                    echo $this->ansiFormat($event->line, $event->channel == ShellCommand::STDOUT ? Console::NORMAL : Console::FG_RED);
                });

                $retval = $cmd->run();

                // success
                if ($retval == "0") {
                    $eventItem = new EventItem([
                        'event' => 'ticket/' . $this->ticket->id,
                        'priority' => 0,
                        'data' => [
                            'setup_complete' => true,
                        ],
                    ]);
                    $eventItem->generate();
                    $this->ticket->client_state = yiit('ticket', 'setup complete');
                    $this->ticket->save();
                } else {
                    $eventItem = new EventItem([
                        'event' => 'ticket/' . $this->ticket->id,
                        'priority' => 0,
                        'data' => [
                            'setup_failed' => true,
                        ],
                    ]);
                    $eventItem->generate();
                    $this->ticket->client_state = yiit('ticket', 'setup failed');
                    $this->ticket->save();
                }

            }

            $this->unlockItem($this->ticket);

        }

        $this->ticket = null;
    }

    /**
     * @inheritdoc
     */
    public function stop($cause = null)
    {

        if ($this->ticket != null) {

            $this->ticket->download_lock = 0;
            $this->ticket->client_state = yiit('ticket', 'aborted, waiting for download');
            $this->ticket->save(false, ['client_state_id', 'client_state_data', 'download_lock']);

            $act = new Activity([
                'ticket_id' => $this->ticket->id,
                'description' => yiit('activity', 'Exam download aborted (server side).'),
                'severity' => Activity::SEVERITY_ERROR,
            ]);
            $act->save();
        }

        parent::stop($cause);
    }

    /**
     * Determines if a given port on the target system is open or not
     *
     * @param integer $port The port to check
     * @param integer $tries The number to times to try (with 5 seconds delay inbetween every check)
     * @param integer $errstr contains the error message of the last try from fsockopen()
     * @param integer $errno the error code of the last try from connect()
     * @return boolean Whether the port is open or not
     */
    private function checkPort($port, $tries = 1, &$errstr = null, &$errno = null)
    {
        for($c=1;$c<=$tries;$c++){
            $fp = @fsockopen($this->ticket->ip, $port, $errno, $errstr, 10);
            if (!$fp) {
                //$this->logError('Port ' . $port . ' is closed or blocked. (try ' . $c . '/' . $tries . ')');
                $this->logError(substitute('Port {port} is closed to blocked on ticket with token {token} and ip {ip}, error code: {code}, error message: {error}. (try {try}/{tries})', [
                    'port' => $port,
                    'token' => $this->ticket->token,
                    'ip' => $this->ticket->ip,
                    'code' => $errno,
                    'error' => $errstr,
                    'try' => $c,
                    'tries' => $tries,
                ]));
                sleep(5);
            } else {
                // port is open and available
                fclose($fp);
                return true;
            }
        }
        return false;
    }

    /**
     * Clean up abandoned tickets. If a ticket stays in download_lock and
     * its associated daemon is not running anymore, this function will unlock them.
     *
     * @return void
     */
    private function cleanup ()
    {

        $query = Ticket::find()
            ->where(['download_lock' => 1]);

        $tickets = $query->all();
        foreach ($tickets as $ticket) {
            if (($daemon = Daemon::findOne($ticket->running_daemon_id)) !== null) {
                if ($daemon->running != true) {
                    $ticket->download_lock = 0;
                    $ticket->save(false);
                    $daemon->delete();
                }
            }else{
                $ticket->download_lock = 0;
                $ticket->save(false);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function lockItem ($ticket)
    {
        if ($this->lock($ticket->id . "_download")) {
            $ticket->download_lock = 1;
            $ticket->running_daemon_id = $this->daemon->id;
            return $ticket->save(false);
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function unlockItem ($ticket)
    {
        $this->unlock($ticket->id . "_download");
        $ticket->download_lock = 0;
        return $ticket->save(false);
    }

    /**
     * @inheritdoc
     *
     * Determines the next ticket to process
     *
     * @return Ticket|null
     */
    public function getNextItem ()
    {

        // first do a cleanup
        $this->cleanup();

        $this->pingOthers();

        // tickets which requested the download
        $query = Ticket::find()
            ->where(['not', ['start' => null]])
            ->andWhere(['end' => null])
            ->andWhere(['not', ['ip' => null]])
            ->andWhere(['download_lock' => 0])
            ->andWhere(['bootup_lock' => 1])
            ->andWhere(['not', ['download_request' => null]])
            ->andWhere([
                'or',
                ['download_finished' => null],
                [
                    '<',
                    new Expression('unix_timestamp(`download_finished`)'),
                    new Expression('unix_timestamp(`download_request`)')
                ],
            ])            
            ->orderBy('download_request ASC');

        // finally lock the next ticket and return it
        if (($ticket = $query->one()) !== null) {
            if ($this->lockItem($ticket)) {
                return $ticket;
            }
        }

        return null;

    }

}