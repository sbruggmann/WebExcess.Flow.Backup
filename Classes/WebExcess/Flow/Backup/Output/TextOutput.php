<?php
namespace WebExcess\Flow\Backup\Output;

/*
 * This file is part of the WebExcess.Flow.Backup package.
 */

use TYPO3\Flow\Annotations as Flow;

class TextOutput implements OutputInterface
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
        return $this->text;
    }

    public function getMaximumLineLength()
    {
    }

    public function output($text, array $arguments = array())
    {
    }

    public function outputLine($text = '', array $arguments = array())
    {
        $this->addText($text);
    }

    public function outputFormatted($text = '', array $arguments = array(), $leftPadding = 0)
    {
    }

    public function outputTable($rows, $headers = null)
    {
    }

    public function select($question, $choices, $default = null, $multiSelect = false, $attempts = false)
    {
    }

    public function ask($question, $default = null, array $autocomplete = null)
    {
        $this->addText($question . ' ' . (is_null($default) ? 'null' : $default));

        return $default;
    }

    public function askConfirmation($question, $default = true)
    {
        $this->addText($question . ' ' . ($default === true ? 'true' : 'false'));

        return $default;
    }

    public function askHiddenResponse($question, $fallback = true)
    {
    }

    public function askAndValidate(
        $question,
        $validator,
        $attempts = false,
        $default = null,
        array $autocomplete = null
    ) {
    }

    public function askHiddenResponseAndValidate($question, $validator, $attempts = false, $fallback = true)
    {
    }

    public function progressStart($max = null)
    {
    }

    public function progressAdvance($step = 1, $redraw = false)
    {
    }

    public function progressSet($current, $redraw = false)
    {
    }

    public function progressFinish()
    {
    }

    protected function getDialogHelper()
    {
    }

    protected function getProgressHelper()
    {
    }

    protected function getTableHelper()
    {
    }

}
