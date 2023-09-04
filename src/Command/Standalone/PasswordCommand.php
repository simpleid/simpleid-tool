<?php
/*
 * SimpleID
 *
 * Copyright (C) Kelvin Mo 2014-2023
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
 * Command to encode a password
 */
class PasswordCommand extends Command {

    const MIN_ITERATIONS = 100000;
    const DEFAULT_ITERATIONS = 600000;

    /**
     * {@inheritdoc}
     */
    protected function configure() {
        parent::configure();
        $this->setName('passwd')->setDescription('Encodes a password');
        $this->addArgument('password', InputArgument::OPTIONAL, 'Password to encode (prompt if missing)');
        $this->addOption('algorithm', 'f', InputOption::VALUE_REQUIRED, 'HMAC algorithm', 'sha256');
        $this->addOption('iterations', 'c', InputOption::VALUE_REQUIRED, 'Number of iterations', self::DEFAULT_ITERATIONS);
        $this->addOption('key-length', 'd', InputOption::VALUE_REQUIRED, 'Length of output, with 0 being the full length', 0);
        $this->addOption('simpleid1', null, InputOption::VALUE_NONE, 'Output the encoded password in SimpleID 1.x format');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output) {
        $algo = $input->getOption('algorithm');
        if (!in_array($algo, hash_algos())) {
            $output->writeln('<error>Invalid algorithm: ' . $algo . '</error>');
            return 1;
        }

        $iterations = $input->getOption('iterations');
        if (!is_int($iterations) || ($iterations < self::MIN_ITERATIONS)) {
            $output->writeln('<error>Number of iterations invalid or too small (at least ' . self::MIN_ITERATIONS . '): ' . $iterations . '</error>');
            return 1;
        }

        $length = $input->getOption('key-length');
        if (!is_int($length) || ($length < 0)) {
            $output->writeln('<error>Invalid key length: ' . $length . '</error>');
            return 1;
        }
        if ($input->getOption('simpleid1')) {
            $length = strlen(hash($algo, ''));
            $output->writeln('<comment>SimpleID 1.x passwords require key length to be set to: ' . $length . '</comment>');
        }

        if ($input->getArgument('password')) {
            $password = $input->getArgument('password');
        } elseif (!$input->getOption('no-interaction')) {
            $helper = $this->getHelper('question');

            $question = new Question('<question>Password:</question> ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $question->setValidator(function ($value) {
                if (trim($value) == '') {
                    throw new \Exception('The password cannot be blank');
                }

                return $value;
            });
            $password = $helper->ask($input, $output, $question);

            $question = new Question('<question>Re-type password:</question> ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $verify_password = $helper->ask($input, $output, $question);

            if ($password != $verify_password) {
                $output->writeln('<error>Passwords do not match</error>');
                return 1;
            }
        } else {
            $output->writeln('<error>Password required</error>');
            return 1;
        }

        if ($input->getOption('simpleid1')) {
            // SimpleID 1.x stores the salt as plain text, so we need to ensure that
            // they are sensible ASCII characters
            $salt = bin2hex(random_bytes(32));
            $raw_output = false;
        } else {
            $salt = random_bytes(32);
            $raw_output = true;
        }
        
        $hash = hash_pbkdf2($algo, $password, $salt, $iterations, $length, $raw_output);

        $output->writeln(self::encode_hash($hash, $salt, $algo, $iterations, $length, $input->getOption('simpleid1')));

        return 0;
    }

    /**
     * Encodes a PBKDF2 hashed password for use in a SimpleID identity file.
     * 
     * This function can return the encoded hashed password in either SimpleID 1 or
     * SimpleID 2 format.  For SimpleID 1, the `$hash` parameter should be a hexadecimal
     * string.  For SimpleID 2, the `$hash` parameter should be a binary string.
     * 
     * @param string $hash the hashed password
     * @param string $salt the salt as a binary string
     * @param string $algo the HMAC algorithm
     * @param int $iterations the number of iterations used in hashing the password
     * @param int $length the length of the PBKDF2 output
     * @param bool $simpleid1_format true to return the encoded hashed password in
     * SimpleID 1 format
     * @return string the encoded hashed password
     */
    static function encode_hash($hash, $salt, $algo, $iterations, $length = 0, $simpleid1_format = false) {
        $params = [ 'f' => $algo, 'c' => $iterations ];
        if ($length > 0) $params['dk'] = $length;

        if ($simpleid1_format) {
            return $hash . ':pbkdf2:' . $algo . ':' . $iterations . ':' . $salt;
        } else {
            return '$pbkdf2$' . http_build_query($params) . '$' . base64_encode($hash) . '$' . base64_encode($salt);
        }
    }
}


?>
