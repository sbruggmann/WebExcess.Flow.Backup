<?php
namespace WebExcess\Flow\Backup\Command;

/*
 * This file is part of the WebExcess.Flow.Backup package.
 */

use TYPO3\Flow\Annotations as Flow;
use WebExcess\Flow\Backup\Service\BackupService;

/**
 * @Flow\Scope("singleton")
 */
class BackupCommandController extends \TYPO3\Flow\Cli\CommandController
{

    /**
     * @Flow\Inject()
     * @var BackupService
     */
    protected $backupService;

    private function initialize()
    {
        //$this->backupService->initialize(new TextOutput());
        $this->backupService->initialize(new \WebExcess\Flow\Backup\Output\ConsoleOutput());
    }

    /**
     * Create a Backup now
     *
     * @return void
     */
    public function nowCommand()
    {
        $this->initialize();
        $this->backupService->createBackup();
    }

    /**
     * Select & restore a Backup
     *
     * @param string $selectedBackup
     * @return void
     */
    public function restoreCommand($selectedBackup = null)
    {
        $this->initialize();

        if (is_null($selectedBackup)) {
            $availableVersions = $this->backupService->getAvailableVersions();
            $selectedBackup = $this->askWithSelectForVersion($availableVersions);
        }

        $this->backupService->restoreBackup($selectedBackup);
    }

    /**
     * Create a new Crypto Key
     *
     * @return void
     */
    public function keyCommand()
    {
        $this->initialize();
        $this->backupService->generateKeyFile();
    }

    /**
     * Show available Backups
     *
     * @return void
     */
    public function listCommand()
    {
        $this->output->outputLine();
        $this->output->outputLine('<b>Available Backups</b>');
        $this->output->outputLine();
        $this->output->outputLine('<b>Identifier  Date       Time</b>');

        $this->initialize();
        $availableVersions = $this->backupService->getAvailableVersions();

        foreach ($availableVersions as $version) {
            if ( is_numeric($version) ) {
                $this->output->outputLine($version . ': ' . date('d.m.Y H:i', $version * 1));
            }
        }
        $this->output->outputLine();
    }

    /**
     * Remove all Backups
     *
     * @return void
     */
    public function clearCommand()
    {
        $this->initialize();
        //$this->backupService->removeAllBackups();
        $this->backupService->removeOldBackups();
    }

    /**
     * @param array $versions
     * @return string
     */
    private function askWithSelectForVersion($versions)
    {
        sort($versions);
        $versionToRestore = $versions[$this->output->select('Which Backup Version?', $versions)];
        return $versionToRestore;
    }

}
