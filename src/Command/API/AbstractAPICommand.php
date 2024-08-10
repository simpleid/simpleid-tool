<?php
/*
 * SimpleID
 *
 * Copyright (C) Kelvin Mo 2023
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public
 * License along with this program; if not, write to the Free
 * Software Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 */

namespace SimpleIDTool\Command\API;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * Abstract class for commands that call the SimpleID API
 */
abstract class AbstractAPICommand extends Command {

    /**
     * {@inheritdoc}
     */
    protected function configure() {
        parent::configure();
        $this->addOption('simpleid-dir', 'd', InputOption::VALUE_REQUIRED, 'Directory containing the SimpleID installation (index.php)', getcwd());
        $this->addOption('php-bin', null, InputOption::VALUE_REQUIRED, 'Path to the PHP executable');
    }

    /**
     * Calls the SimpleID API
     * 
     * @param \Symfony\Component\Console\Input\InputInterface
     * @param string $route
     * @param array $params
     * @return array
     */
    protected function runSimpleID(InputInterface $input, string $route, array $params = []) {
        // 1. PHP executable
        if ($input->getOption('php-bin')) {
            $php_path = $input->getOption('php-bin');
        } else {
            $php_finder = new PhpExecutableFinder();
            $php_path = $php_finder->find(false);
            $php_path = ($php_path === false) ? null : implode(' ', array_merge([$php_path], $php_finder->findArguments()));
        }

        if ($php_path == null) throw new RuntimeException('Cannot find PHP executable. Use --php-bin to specify location of PHP.');

        // 2. SimpleID installation
        $working_dir = $input->getOption('simpleid-dir');
        $conf_file = $this->findSimpleIDConfiguration($working_dir);

        if (!file_exists($working_dir . '/index.php') || ($conf_file == null))
            throw new RuntimeException('Cannot find SimpleID index.php or config.php. Use -d to specify location of SimpleID\'s www directory.');

        $config_hash = strtr(trim(base64_encode(hash_file('sha256', $working_dir . '/config.php', true)), '='), '+/', '-_');

        // 3. Environment variables
        $env = [
            'SIMPLEID_TOOL' => 'TRUE',
            'SIMPLEID_TOOL_TOKEN' => $config_hash
        ];

        // 4. Command line arguments
        $args = array_merge([$route], $params);
        $command_line = $php_path . ' index.php ' . implode(' ', array_map(function($x) { return '"' . addslashes($x) . '"';}, $args));

        // 5. Execute
        $process = Process::fromShellCommandline($command_line, $working_dir, $env);
        $exit_code = $process->run();
        $output = $process->getOutput();

        $result = [
            'exit_code' => $exit_code,
            'output' => $output
        ];

        if ($exit_code != 0) {
            $lines = explode(PHP_EOL, $output, 6);
            if (($lines[1] == '===================================') && (substr($lines[2], 0, 6) == 'ERROR ')) {
                // Fat-Free error
                $tokens = explode(' ', $lines[2], 4); // 'ERROR '.$error['code'].' - '.$error['status']

                $result['error'] = [
                    'code' => $tokens[1],
                    'status' => $tokens[3],
                    'description' => $lines[3]
                ];
                if (isset($lines[5])) $result['error']['trace'] = $lines[5];
            }
        }

        return $result;
    }

    /**
     * Find the SimpleID configuration file `config.php` within
     * the specified directory.
     * 
     * @param string $dir the directory to search from
     * @return ?string the path to config.php, or null if not found
     */
    protected function findSimpleIDConfiguration(string $dir) {
        $directories = [ $dir ];
        if (is_dir($dir . '/conf')) $directories[] = $dir . '/conf';

        $finder = new Finder();
        $finder->files()->in($directories)->name('config.php')->depth('== 0');
        $results = iterator_to_array($finder, false);

        if (count($results) == 0) return null;

        return $results[0]->getPathname();
    }
}

?>
