<?php
namespace WebExcess\Flow\Backup\Service;

/*
 * This file is part of the WebExcess.Flow.Backup package.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Cli\ConsoleOutput;
use WebExcess\Flow\Backup\Service\CryptService;
use WebExcess\Flow\Backup\Output\OutputInterface;

class BackupService
{
    /**
     * @Flow\InjectConfiguration(path="persistence.backendOptions", package="TYPO3.Flow")
     * @var array
     */
    protected $databaseConfiguration;

    /**
     * @Flow\InjectConfiguration(path="Folders.Sources", package="WebExcess.Flow.Backup")
     * @var array
     */
    protected $backupFolders;

    /**
     * @Flow\InjectConfiguration(path="Folders.LocalTarget", package="WebExcess.Flow.Backup")
     * @var array
     */
    protected $localBackupTarget;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @Flow\Inject()
     * @var CryptService
     */
    protected $cryptService;

    /**
     * @var Files
     */
    protected $files;

    /**
     * An array of backup folders
     *
     * @var array
     */
    protected $folders;

    /**
     * Limit of stored Backups
     *
     * @var integer
     */
    protected $limit;

    /**
     * @param OutputInterface $output
     * @param mixed $keyFile
     * @return void
     */
    public function initialize($output, $keyFile = null)
    {
        $this->limit = 6;
        if ($this->databaseConfiguration['driver'] == 'pdo_mysql') {
            $this->backupFolders[] = $this->localBackupTarget . 'Database/';
        }

        if (is_null($keyFile)) {
            $keyFile = $this->localBackupTarget . 'key';
        }

        $this->output = $output;
        if ( file_exists($keyFile) ) {
            $this->cryptService->initialize(file_get_contents($keyFile));
        }
        $this->files = new Files();
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Create a Backup now
     *
     * @return void
     */
    public function createBackup()
    {
        $this->output->outputLine();
        $this->output->outputLine('<b>Prepare Backup</b>');
        $this->output->outputLine();

        $newVersion = time();

        $stats = array(
            'checked' => 0,
            'added' => 0,
            'updated' => 0,
            'removed' => 0,
        );

        $this->output->outputLine('<b>Run Backup</b>');
        $this->output->outputLine();

        $this->dumpDatabase();

        foreach ($this->backupFolders as $folder) {
            $relativeFolder = $this->getPathByFlowRoot($folder);

            $this->output->outputLine('Load Backup Versions for '.$relativeFolder.'..');
            $this->output->outputLine();
            $latestFlatBackup = $this->getFlatBackup($folder);
            $newBackupClean = $this->getBackupActions($folder, $newVersion, $latestFlatBackup);
            $stats['checked'] = $stats['checked'] + count($latestFlatBackup);

            if ( count($newBackupClean)>0 ) {
                // encrypt and create backup..
                $this->files->createDirectoryRecursively($this->localBackupTarget . $newVersion . '/' . $relativeFolder . '/');
                $this->output->outputLine('Backup '.$relativeFolder.' Files..');
                $this->output->progressStart(count($newBackupClean));
                $i = 0;
                foreach ($newBackupClean as $sha1 => $fileItem) {
                    if ($fileItem['action'] == 'add' || $fileItem['action'] == 'update') {
                        if ($fileItem['action'] == 'add') {
                            $stats['added']++;
                        } else {
                            $stats['updated']++;
                        }

                        $this->cryptService->encryptFileToFile(
                            $fileItem['file'],
                            $this->localBackupTarget . $newVersion . '/' . $relativeFolder . '/' . $sha1 . '.file');
                        file_put_contents(
                            $this->localBackupTarget . $newVersion . '/' . $relativeFolder . '/' . $sha1 . '.meta',
                            json_encode($fileItem));

                    } else {
                        if ($fileItem['action'] == 'remove') {
                            $stats['removed']++;

                            file_put_contents(
                                $this->localBackupTarget . $newVersion . '/' . $relativeFolder . '/' . $sha1 . '.meta',
                                json_encode($fileItem));
                        }
                    }
                    $i++;
                    $this->output->progressSet($i);
                }
                $this->output->progressFinish();
                $this->output->outputLine();

            }
        }

        $this->deleteDatabaseTempFolder();

        $this->removeOldBackups();

        $this->output->outputLine('<b>Backup Finished</b>');
        $this->output->outputLine(' - Checked: '.$stats['checked']);
        $this->output->outputLine(' - Added:   '.$stats['added']);
        $this->output->outputLine(' - Updated: '.$stats['updated']);
        $this->output->outputLine(' - Removed: '.$stats['removed']);
        $this->output->outputLine();
        return;
    }

    /**
     * Restore a selected Backup
     *
     * @param string $versionToRestore
     * @return void
     */
    public function restoreBackup($versionToRestore)
    {
        $this->output->outputLine();
        $this->output->outputLine('<b>Restore Files..</b>');
        $this->output->outputLine();
        $stats = array(
            'restored' => 0,
            'bytes' => 0,
        );

        foreach ($this->backupFolders as $folder) {
            $relativeFolder = $this->getPathByFlowRoot($folder);

            $this->output->outputLine('Load Backup Versions for ' . $relativeFolder . '..');
            $this->output->outputLine();
            $flatBackup = $this->getFlatBackup($folder, $versionToRestore);

            if ( is_dir($folder) ) {
                $this->files->removeDirectoryRecursively($folder);
            }

            $this->output->outputLine('Restore Files to '.$relativeFolder.'..');
            $this->output->progressStart(count($flatBackup));
            $i = 0;
            foreach ($flatBackup as $sha1 => $fileItem) {
                $this->files->createDirectoryRecursively( dirname($fileItem['file']) );
                $this->cryptService->decryptFileToFile(
                    $this->localBackupTarget . $fileItem['version'] . '/' . $relativeFolder . '/' . $sha1 . '.file',
                    FLOW_PATH_ROOT . $fileItem['file']);

                $stats['restored']++;
                $stats['bytes'] = $stats['bytes'] + filesize(FLOW_PATH_ROOT . $fileItem['file']);

                $i++;
                $this->output->progressSet($i);
            }
            $this->output->progressFinish();
            $this->output->outputLine();
        }

        $this->importDatabase();
        $this->deleteDatabaseTempFolder();

        $this->output->outputLine('<b>Restored ' . $stats['restored'] . ' files</b> with a total of ' . $this->formatBytes($stats['bytes']));
        $this->output->outputLine();
        return;
    }

    /**
     * Create a new Crypto Key File
     *
     * @return void
     */
    public function generateKeyFile()
    {
        $this->output->outputLine();
        $this->output->outputLine('<b>Generate a new Crypto Key</b>');
        $this->output->outputLine();

        $this->files->createDirectoryRecursively($this->localBackupTarget);

        try {
            $key = \Crypto::createNewRandomKey();
            file_put_contents($this->localBackupTarget . 'key', $key);
            // WARNING: Do NOT encode $key with bin2hex() or base64_encode(),
            // they may leak the key to the attacker through side channels.
        } catch (Ex\CryptoTestFailedException $ex) {
            die('Cannot safely create a key');
        } catch (Ex\CannotPerformOperationException $ex) {
            die('Cannot safely create a key');
        }

        $this->output->outputLine('done');
        $this->output->outputLine();

    }

    /**
     * @param string $command
     * @param array $arguments
     * @return string
     */
    protected function executeLocalShellCommand($command, $arguments = [])
    {
        $shellCommand = call_user_func_array('sprintf', array_merge(array($command), $arguments));
        $shellCommandResult = shell_exec($shellCommand . ' 2>&1');

        return $shellCommandResult;
    }

    private function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');

        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[(string)floor($base)];
    }

    public function getAvailableVersions()
    {
        // fetch available backup versions..
        $availableVersions = array();
        $availableVersionFolders = glob($this->localBackupTarget . '*');
        foreach ($availableVersionFolders as $availableVersionFolder) {
            if ( is_dir($availableVersionFolder) ) {
                $version = basename($availableVersionFolder);
                if (is_numeric($version)) {
                    $availableVersions[] = $version;
                }
            }
        }
        sort($availableVersions);
        return $availableVersions;
    }

    /**
     * @param string $folder
     * @param string $selectedVersion todo
     * @return array
     */
    private function getFlatBackup($folder, $selectedVersion = null)
    {
        $availableVersions = $this->getAvailableVersions();
        $relativeFolder = $this->getPathByFlowRoot($folder);

        // fetch all files in backup folders..
        $filesByVersions = array();
        foreach ($availableVersions as $availableVersion) {
            if ( file_exists($this->localBackupTarget . $availableVersion . '/' . $relativeFolder . '/') ) {
                $filesByVersions[$availableVersion] = $this->files->readDirectoryRecursively($this->localBackupTarget . $availableVersion . '/' . $relativeFolder . '/');
            }
        }

        // create latest flat backup filestructure..
        $latestFlatBackup = array();
        foreach ($filesByVersions as $version => $filesByVersion) {
            if ( is_null($selectedVersion) || $version<=$selectedVersion ) {
                foreach ($filesByVersion as $file) {
                    if (substr($file, -5) === '.meta') {
                        $sha1 = basename(substr($file, 0, -5));
                        $fileItem = json_decode(file_get_contents($file), true);
                        if (!array_key_exists($sha1, $latestFlatBackup)) {
                            $latestFlatBackup[$sha1] = $fileItem;

                        } else {
                            if ($latestFlatBackup[$sha1]['version'] < $fileItem['version']) {
                                $latestFlatBackup[$sha1] = $fileItem;
                            }
                        }
                    }
                }
            }
        }

        //\TYPO3\Flow\var_dump($latestFlatBackup);
        //exit();
        return $latestFlatBackup;
    }

    /**
     * @param string $folder
     * @param integer $newVersion
     * @param array $latestFlatBackup
     * @return array
     */
    private function getBackupActions($folder, $newVersion, $latestFlatBackup)
    {
        if ( !file_exists($folder) ) {
            return array();
        }

        // check for new and updated files..
        $newBackup = array();
        $files = $this->files->readDirectoryRecursively($folder);
        $relativeFolder = $this->getPathByFlowRoot($folder);

        $this->output->outputLine('Search for Changes in '.$relativeFolder.'..');
        $this->output->progressStart(count($files)+count($latestFlatBackup));
        $progress = 0;
        foreach ($files as $file) {
            $sha1 = sha1($file);
            $fileItem = array(
                'file' => $this->getPathByFlowRoot($file),
                'hash' => sha1_file($file),
                'version' => $newVersion,
            );
            if ( array_key_exists($sha1, $latestFlatBackup) && $latestFlatBackup[$sha1]['hash']!==$fileItem['hash'] ) {
                $fileItem['action'] = 'update';
                $newBackup[$sha1] = $fileItem;

            }else if ( !array_key_exists($sha1, $latestFlatBackup) ) {
                $fileItem['action'] = 'add';
                $newBackup[$sha1] = $fileItem;

            }else{
                $fileItem['action'] = 'ignore';
                $newBackup[$sha1] = $fileItem;
            }

            $progress++;
            $this->output->progressSet($progress);
        }

        // check for deleted files..
        foreach ($latestFlatBackup as $sha1 => $fileItem) {
            if ( !array_key_exists($sha1, $newBackup) && $fileItem['action']!='remove' ) {
                $fileItem['action'] = 'remove';
                $fileItem['version'] = $newVersion;
                $newBackup[$sha1] = $fileItem;
            }

            $progress++;
            $this->output->progressSet($progress);
        }

        $this->output->progressFinish();
        $this->output->outputLine();

        // remove ignore-items..
        $newBackupClean = array();
        foreach ($newBackup as $sha1 => $fileItem) {
            if ( $fileItem['action']!='ignore' ) {
                $newBackupClean[$sha1] = $fileItem;
            }
        }
        //\TYPO3\Flow\var_dump($newBackupClean);
        //exit();

        return $newBackupClean;
    }

    /**
     * @return bool
     * @throws \TYPO3\Flow\Utility\Exception
     */
    private function dumpDatabase()
    {
        if ($this->databaseConfiguration['driver'] == 'pdo_mysql') {

            /**
             * EXPORT TABLES
             */

            $this->files->createDirectoryRecursively($this->localBackupTarget . 'Database/');
            $tables = $this->executeLocalShellCommand('echo "show tables;" | mysql --host=%s --user=%s --password=%s %s',
                array(
                    $this->databaseConfiguration['host'],
                    $this->databaseConfiguration['user'],
                    $this->databaseConfiguration['password'],
                    $this->databaseConfiguration['dbname'],
                ));
            $tables = explode("\n", trim($tables));
            array_shift($tables);

            $this->output->outputLine('Export Tables..');
            $this->output->progressStart(count($tables));
            $i = 0;
            foreach ($tables as $table) {
                $this->executeLocalShellCommand('mysqldump --compact --add-drop-table --host=%s --user=%s --password=%s %s %s > %s',
                    array(
                        $this->databaseConfiguration['host'],
                        $this->databaseConfiguration['user'],
                        $this->databaseConfiguration['password'],
                        $this->databaseConfiguration['dbname'],
                        $table,
                        $this->localBackupTarget . 'Database/' . $table . '.sql',
                    ));
                $i++;
                $this->output->progressSet($i);
            }
            $this->output->progressFinish();
            $this->output->outputLine();

            return true;
        }

        return false;
    }

    /**
     * @return void
     */
    private function importDatabase()
    {
        if ($this->databaseConfiguration['driver'] == 'pdo_mysql') {
            $tablesToImport = array();

            if ( !is_dir($this->localBackupTarget . 'Database/') ) {
                return;
            }

            $databaseFolderFiles = glob($this->localBackupTarget . 'Database/*');
            foreach ($databaseFolderFiles as $databaseFolderFile) {
                if ( is_file($databaseFolderFile) ) {
                    $tablesToImport[] = $databaseFolderFile;
                }
            }

            $this->output->outputLine('Restore Tables..');
            $this->output->progressStart(count($tablesToImport));
            $i = 0;
            foreach ($tablesToImport as $table) {
                $this->executeLocalShellCommand('mysql --host=%s --user=%s --password=%s %s < %s', array(
                    $this->databaseConfiguration['host'],
                    $this->databaseConfiguration['user'],
                    $this->databaseConfiguration['password'],
                    $this->databaseConfiguration['dbname'],
                    $table,
                ));

                $i++;
                $this->output->progressSet($i);
            }
            $this->output->progressFinish();

        }
    }

    /**
     * @return void
     */
    private function deleteDatabaseTempFolder()
    {
        if ( !is_dir($this->localBackupTarget . 'Database/') ) {
            return;
        }
        $this->files->removeDirectoryRecursively($this->localBackupTarget . 'Database/');
    }

    public function removeAllBackups()
    {
        $this->output->outputLine();
        $this->output->outputLine('<b>Remove Backups</b>');
        $this->output->outputLine();

        $versions = $this->getAvailableVersions();
        $this->output->progressStart(count($versions));
        $i = 0;
        foreach ($versions as $version) {
            $this->files->removeDirectoryRecursively($this->localBackupTarget . $version . '/');
            $i++;
            $this->output->progressSet($i);
        }
        $this->output->progressFinish();
        $this->output->outputLine();

    }

    public function removeOldBackups()
    {
        $versions = $this->getAvailableVersions();
        if ( $this->limit < count($versions) ) {

            $lastValidIndex = count($versions)-$this->limit;
            if ( $lastValidIndex<=0 ) {
                return ;
            }
            $lastValidVersion = $versions[$lastValidIndex];

            foreach ($this->backupFolders as $folder) {
                $relativeFloder = $this->getPathByFlowRoot($folder);

                $lastValidBackup = $this->getFlatBackup($folder, $lastValidVersion);
                $updatedLastValidBackup = array();
                foreach ($lastValidBackup as $sha1 => $fileItem) {
                    $this->files->createDirectoryRecursively($this->localBackupTarget . $lastValidVersion . '/' . $relativeFloder . '/');
                    if ( $fileItem['version']!=$lastValidVersion && file_exists($this->localBackupTarget . $fileItem['version'] . '/' . $relativeFloder . '/' . $sha1 . '.file') ) {
                        copy(
                            $this->localBackupTarget . $fileItem['version'] . '/' . $relativeFloder . '/' . $sha1 . '.file',
                            $this->localBackupTarget . $lastValidVersion . '/' . $relativeFloder . '/' . $sha1 . '.file');

                        $fileItem['version'] = $lastValidVersion;
                        file_put_contents($this->localBackupTarget . $lastValidVersion . '/' . $relativeFloder . '/' . $sha1 . '.meta',
                            json_encode($fileItem));
                    }
                    $updatedLastValidBackup[$sha1] = $fileItem;
                }

            }

            $this->output->outputLine('Remove old Backups..');
            $i = 0;
            foreach ($versions as $version) {
                $remove = ($i<(count($versions)-$this->limit)) ? true : false;
                if ($remove) {
                    $this->files->removeDirectoryRecursively($this->localBackupTarget . $version . '/');
                    $this->output->outputLine(' - '.$version);
                }
                $i++;
            }
            $this->output->outputLine();
        }
    }

    /**
     * @param $absolutePath
     * @return string
     */
    private function getPathByFlowRoot($absolutePath)
    {
        $relativePath = str_replace(FLOW_PATH_ROOT, '', $absolutePath);
        return $relativePath ? $relativePath : $absolutePath;
    }

}
