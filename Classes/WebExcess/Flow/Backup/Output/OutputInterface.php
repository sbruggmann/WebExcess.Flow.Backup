<?php
namespace WebExcess\Flow\Backup\Output;

/*
 * This file is part of the WebExcess.Flow.Backup package.
 */

use TYPO3\Flow\Annotations as Flow;

interface OutputInterface
{

    public function getText();

    public function outputLine($text = '', array $arguments = array());

    public function progressStart($max = null);

    public function progressSet($current, $redraw = false);

    public function progressFinish();

}
