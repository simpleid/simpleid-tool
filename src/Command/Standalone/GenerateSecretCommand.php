<?php
/*
 * SimpleID
 *
 * Copyright (C) Kelvin Mo 2024
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

namespace SimpleIDTool\Command\Standalone;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Command to generate a secret
 */
class GenerateSecretCommand extends Command {

    const BASE58_CHARS = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    /**
     * {@inheritdoc}
     */
    protected function configure() {
        parent::configure();
        $this->setName('secret')->setDescription('Generates a random secret string');
        $this->addOption('length', 'l', InputOption::VALUE_REQUIRED, 'Length of the secret to be generated', 64);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output) {
        $length = $input->getOption('length');
        if (!is_int($length) || ($length < 0)) {
            $output->writeln('<error>Invalid length: ' . $length . '</error>');
            return 1;
        }

        $output->writeln($this->generateSecret($length));

        return 0;
    }

    /**
     * Generates a random string that can be used as a secret.
     * 
     * The function calls the random_bytes() function with the specified
     * number of characters, then converts to a string containing only alphanumeric
     * characters (case sensitive).  The conversion method is a form of Base58
     * encoding, which strips out confusing characters such as I, l, O and 0.
     *
     * @param int<1, max> $num_chars the number of characters in the secret
     * @return string the random string
     */
    protected function generateSecret($num_chars = 18) {
        // determine mask for valid characters
        $mask = 256 - (256 % strlen(self::BASE58_CHARS));

        $result = '';
        do {
            $rand = random_bytes($num_chars);
            for ($i = 0; $i < $num_chars; $i++) {
                if (ord($rand[$i]) >= $mask) continue;
                $result .= self::BASE58_CHARS[ord($rand[$i]) % strlen(self::BASE58_CHARS)];
            }
        } while (strlen($result) < $num_chars);
        return substr($result, 0, $num_chars);
    }
}


?>
