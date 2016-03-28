<?php

/**
 * bin/behat -c Packages/Application/WebExcess.Flow.Backup/Tests/Behavior/behat.yml
 */

use Behat\Behat\Exception\PendingException;
use Behat\Behat\Exception\ErrorException;
use Behat\Gherkin\Node\TableNode,
    Behat\MinkExtension\Context\MinkContext;
use TYPO3\Flow\Utility\Arrays;
use PHPUnit_Framework_Assert as Assert;
use Symfony\Component\DomCrawler\Crawler;
use TYPO3\Flow\Utility\Files;

require_once( (string)str_replace('docker/host-www/', 'docker/www/', realpath(__DIR__ . '/../../../../../Flowpack.Behat/Tests/Behat/FlowContext.php')) );
//require_once('/docker/www/Neos/Packages/Application/Flowpack.Behat/Tests/Behat/FlowContext.php');

/**
 * Features context
 */
class FeatureContext extends MinkContext
{
    /**
     * @var \TYPO3\Flow\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Files
     */
    protected $files;

    /**
     * @var string
     */
    protected $lastLocalShellResult;

    /**
     * @var array
     */
    protected $nextLocalShellArguments;

    /**
     * Initializes the context
     *
     * @param array $parameters Context parameters (configured through behat.yml)
     */
    public function __construct(array $parameters)
    {
        $this->useContext('flow', new \Flowpack\Behat\Tests\Behat\FlowContext($parameters));
        $this->objectManager = $this->getSubcontext('flow')->getObjectManager();
        $this->files = $this->objectManager->get('\\TYPO3\\Flow\\Utility\\Files');
    }

    /**
     * @Given /^I never started the Backup before$/
     */
    public function iNeverStartedTheBackupBefore()
    {
        $this->files = new Files();
        $this->files->removeDirectoryRecursively('/docker/www/Neos/Data/Backup');

        $this->nextLocalShellArguments = array();

        $dataPersistentFolder = (string)str_replace('docker/host-www/', 'docker/www/', realpath(__DIR__ . '/../../../../../../../')).'/Data/Persistent';
        $testFiles = glob($dataPersistentFolder.'/*.txt');
        foreach ($testFiles as $testFile) {
            unlink($testFile);
        }
    }

    /**
     * @Given /^I add the file "([^"]*)" with "([^"]*)" as content$/
     */
    public function iAddTheFileWithAsContent($arg1, $arg2)
    {
        $dataPersistentFolder = (string)str_replace('docker/host-www/', 'docker/www/', realpath(__DIR__ . '/../../../../../../../')).'/Data/Persistent';
        if ( file_exists($dataPersistentFolder.'/'.$arg1) ) {
            unlink($dataPersistentFolder.'/'.$arg1);
        }
        file_put_contents($dataPersistentFolder.'/'.$arg1, $arg2);
        return true;
    }

    /**
     * @Given /^I remove the file "([^"]*)"$/
     */
    public function iRemoveTheFile($arg1)
    {
        $dataPersistentFolder = (string)str_replace('docker/host-www/', 'docker/www/', realpath(__DIR__ . '/../../../../../../../')).'/Data/Persistent';
        if ( file_exists($dataPersistentFolder.'/'.$arg1) ) {
            unlink($dataPersistentFolder.'/'.$arg1);
        }
        return true;
    }

    /**
     * @Given /^I execute "([^"]*)" on local shell$/
     */
    public function executeOnLocalShell($arg1)
    {
        $this->lastLocalShellResult = trim($this->executeLocalShellCommand($arg1, $this->nextLocalShellArguments));
        $this->nextLocalShellArguments = array();
        //\TYPO3\Flow\var_dump($this->lastLocalShellResult);
        return true;
    }

    /**
     * @Then /^I should see the Feedback "([^"]*)"$/
     */
    public function iShouldSeeTheFeedback($arg1)
    {
        if ( strpos($this->lastLocalShellResult, $arg1)!==false ) {
            return true;
        }else{
            throw new Exception();
        }
    }

    /**
     * @Given /^I should not see the Feedback "([^"]*)"$/
     */
    public function iShouldNotSeeTheFeedback($arg1)
    {
        if ( strpos($this->lastLocalShellResult, $arg1)===false ) {
            return true;
        }else{
            throw new Exception();
        }
    }

    /**
     * @Given /^I use the last but "([^"]*)" Backup Version as local shell argument "([^"]*)"$/
     */
    public function iUseTheLastButBackupVersionAsLocalShellArgument($arg1, $arg2)
    {
        /*
            Available Backups

            Identifier  Date       Time
            1459176938: 28.03.2016 16:55
            1459176939: 28.03.2016 16:55
            1459176940: 28.03.2016 16:55
            1459176941: 28.03.2016 16:55
        */
        $result = trim($this->executeLocalShellCommand('./flow backup:list'));
        $lines = explode("\n", $result);
        if ( $arg1=='least' ) {
            $this->nextLocalShellArguments[] = $arg2;
            $this->nextLocalShellArguments[] = substr(trim($lines[count($lines)-1]), 0, 10);
        }else if ( $arg1=='one' ) {
            $this->nextLocalShellArguments[] = $arg2;
            $this->nextLocalShellArguments[] = substr(trim($lines[count($lines)-2]), 0, 10);
        }else if ( $arg1=='two' ) {
            $this->nextLocalShellArguments[] = $arg2;
            $this->nextLocalShellArguments[] = substr(trim($lines[count($lines)-3]), 0, 10);
        }else if ( $arg1=='three' ) {
            $this->nextLocalShellArguments[] = $arg2;
            $this->nextLocalShellArguments[] = substr(trim($lines[count($lines)-4]), 0, 10);
        }
        return true;
    }

    /**
     * @Given /^The file "([^"]*)" contains "([^"]*)"$/
     */
    public function theFileContains($arg1, $arg2)
    {
        $dataPersistentFolder = (string)str_replace('docker/host-www/', 'docker/www/', realpath(__DIR__ . '/../../../../../../../')).'/Data/Persistent';
        $content = trim(file_get_contents($dataPersistentFolder.'/'.$arg1));
        if ( strpos($content, $arg2)!==false ) {
            return true;
        }else{
            throw new Exception();
        }
    }

    /**
     * @Given /^The file "([^"]*)" does not exist$/
     */
    public function theFileDoesNotExist($arg1)
    {
        $dataPersistentFolder = (string)str_replace('docker/host-www/', 'docker/www/', realpath(__DIR__ . '/../../../../../../../')).'/Data/Persistent';
        if ( !file_exists($dataPersistentFolder.'/'.$arg1) ) {
            return true;
        }else{
            throw new Exception();
        }
    }


    /**
     * @param string $command
     * @param array $arguments
     * @return string
     */
    protected function executeLocalShellCommand($command, $arguments = [])
    {
        if ( !is_array($arguments) ) {
            $arguments = array();
        }
        $shellCommand = call_user_func_array('sprintf', array_merge(array($command), $arguments));
        $shellCommandResult = shell_exec($shellCommand . ' 2>&1');

        return $shellCommandResult;
    }

}
