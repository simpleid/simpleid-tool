<?php
/*
 * SimpleID
 *
 * Copyright (C) Kelvin Mo 2023-2025
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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Abstract class for commands that call the SimpleID API
 */
class APICommand extends AbstractAPICommand {

    protected function configure() {
        parent::configure();
        $this->setName('api')->setDescription('Calls the SimpleID API');
        $this->addArgument('route', InputArgument::REQUIRED, 'The API endpoint');
        $this->addArgument('params', InputArgument::IS_ARRAY, 'Parameters for the API endpoint');
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $formatter = $this->getHelper('formatter');

        $route = $input->getArgument('route');
        $params = $input->getArgument('params');

        try {
            $result = $this->runSimpleID($input, $route, $params);
        } catch (\RuntimeException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return 1;
        }
        
        if ($result['exit_code'] == 0) {
            $output->writeln($result['output']);
        } elseif (isset($result['error'])) {
            $error_block = $formatter->formatBlock([$result['error']['status'] . ' (' . $result['error']['code'] . ')', $result['error']['description']], 'error');
            $output->writeln($error_block);

            if ($output->isDebug() && isset($result['error']['trace'])) {
                $output->writeln($result['error']['trace']);
            }
        } else {
            $output->writeln('<error>' . $result['output'] . '</error>');
        }

        return $result['exit_code'];
    }
}

?>