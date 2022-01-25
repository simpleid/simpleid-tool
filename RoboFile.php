<?php

use Symfony\Component\Finder\Finder;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks {
    protected function checkPharReadonly() {
        if (ini_get('phar.readonly')) {
            throw new \Exception('Must set "phar.readonly = Off" in php.ini to build phars.');
        }
    }

    /**
     * Build phar file
     */
    public function phar() {
        // 1. Check php config
        $this->checkPharReadonly();

        // 2. Set up robo collections and create temp directory
        $main_collection = $this->collectionBuilder();
        $prepare_collection = $this->collectionBuilder();
        $temp = $main_collection->tmpDir();
        $phar_file = 'simpleid-tool.phar';

        // 3. Prepare step
        // (a) Copy files to temp directory
        $prepare_collection->taskMirrorDir([
            'src' => "$temp/src",
            'bin' => "$temp/bin"
        ]);
        $prepare_collection->taskFilesystemStack()->copy('composer.json', "$temp/composer.json");

        // (b) composer install
        $prepare_collection->taskComposerInstall()->dir($temp)->noDev();

        // (c) run
        $result = $prepare_collection->run();
        if (!$result->wasSuccessful()) {
            return $result;
        }

        // 4. Prepare phar task
        $phar_task = $main_collection->taskPackPhar($phar_file)
            ->compress('bzip2')
            ->stub('stub.php');

        // 5. Add files
        $finder = Finder::create()->name('*.php')->in($temp);
        foreach ($finder as $file) {
            $phar_task->addStripped($file->getRelativePathname(), $file->getRealPath());
        }
        
        $finder = Finder::create()->name('*.exe')->in($temp);
        foreach ($finder as $file) {
            $phar_task->addFile($file->getRelativePathname(), $file->getRealPath());
        }

        // 6. chmod
        $main_collection->taskFilesystemStack()->chmod($phar_file, 0755);
        return $main_collection->run();
    }
}