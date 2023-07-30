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
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Abstract class for commands that call the SimpleID API
 */
abstract class APICommand extends Command {

    protected function configure() {
        parent::configure();
        $this->addOption('simpleid-dir', 'd', InputOption::VALUE_REQUIRED, 'Directory containing SimpleID index.php', getcwd());
    }

    public function runSimpleID(string $command, InputInterface $input) {
        $php_finder = new PhpExecutableFinder();
        $php_path = $php_finder->find();

        $command_line = $php_path . ' index.php ' . $command;
        $working_dir = $input->getOption('simpleid-dir');

        $process = Process::fromShellCommandline($command_line, $working_dir);
        $exit_code = $process->run(null, ['SIMPLEID_TOOL' => 'TRUE']);

        // TODO: Parse $exit_code and $process->getOutput())
        return [
            'exit_code' => $exit_code,
            'output' => $process->getOutput()
        ]
    }
}

?>
