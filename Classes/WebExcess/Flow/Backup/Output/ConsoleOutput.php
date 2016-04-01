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
     * @var string
     */
    protected $text;

    /**
     * @param $text
     * @return void
     */
    private function addText($text)
    {
        $this->text .= (empty($this->text) ? '' : "\n") . $text;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return '';
    }

    public function outputLine($text = '', array $arguments = array())
    {
        $this->addText($text);

        parent::outputLine($text, $arguments);
    }
}
