<?php
namespace WebExcess\Flow\Backup\Output;

/*
 * This file is part of the WebExcess.Flow.Backup package.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\ConsoleOutput as FlowConsoleOutput;

class ConsoleOutput extends FlowConsoleOutput implements OutputInterface
{
    /**
     * @return string
     */
    public function getText()
    {
        return '';
    }

}
