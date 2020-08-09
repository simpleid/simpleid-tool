<?php

use Symfony\Component\Finder\Finder;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    /**
     * Build phar file
     */
    public function phar() {
        if (ini_get('phar.readonly')) return $this->yell('Cannot build phar: phar.readonly set to true in php.ini', 40, 'red');

        $task = $this->taskPackPhar('simpleid-tool.phar')
            ->compress()
            ->stub('stub.php');

        $finder = Finder::create()->name('*.php')->in('src');
        foreach ($finder as $file) {
            $task->addFile('src/' . $file->getRelativePathname(), $file->getRealPath());
        }

        $finder = Finder::create()->name('*.php')->in('bin');
        foreach ($finder as $file) {
            $task->addFile('bin/' . $file->getRelativePathname(), $file->getRealPath());
        }

        $finder = Finder::create()->name('*.php')->in('vendor');
        foreach ($finder as $file) {
            $task->addStripped('vendor/' . $file->getRelativePathname(), $file->getRealPath());
        }

        $task->run();
    }
}