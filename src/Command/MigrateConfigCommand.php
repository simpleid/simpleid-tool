<?php
/*
 * SimpleID
 *
 * Copyright (C) Kelvin Mo 2014-2022
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

namespace SimpleIDTool\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateConfigCommand extends Command {

    private $option_map = array(
        'SIMPLEID_BASE_URL' => 'canonical_base_path',
        'SIMPLEID_IDENTITIES_DIR' => 'identities_dir',
        'SIMPLEID_STORE_DIR' => 'store_dir',
        'SIMPLEID_ALLOW_PLAINTEXT' => 'allow_plaintext',
        'SIMPLEID_ALLOW_AUTOCOMPLETE' => 'allow_autocomplete',
        'SIMPLEID_VERIFY_RETURN_URL_USING_REALM' => 'openid_verify_return_url',
        'SIMPLEID_LOCALE' => 'locale',
        'SIMPLEID_DATE_TIME_FORMAT' => 'date_time_format',
        'SIMPLEID_LOGFILE' => 'log_file'
    );

    private $log_level_map = array('critical', 'error', 'warning', 'notice', 'info', 'debug');

    private $additional_config = array(
        'temp_dir' => '/tmp',
        'webfinger_access_control_allow_origin' => '*',
        'acr' => 1,
        'logger' => 'SimpleID\Util\DefaultLogger',
        'modules' => array(
            'SimpleID\Base\MyModule',
            'SimpleID\Auth\PasswordAuthSchemeModule',
            'SimpleID\Auth\RememberMeAuthSchemeModule',
            'SimpleID\Auth\OTPAuthSchemeModule',
            'SimpleID\Protocols\OpenID\OpenIDModule',
            'SimpleID\Protocols\OpenID\Extensions\SRegOpenIDExtensionModule',
            'SimpleID\Protocols\OpenID\Extensions\PAPEOpenIDExtensionModule',
            'SimpleID\Protocols\WebFinger\WebFingerModule',
            'SimpleID\Protocols\Connect\ConnectModule',
//            'SimpleID\Protocols\Connect\ConnectSessionModule',
            'SimpleID\Protocols\Connect\ConnectClientRegistrationModule',
        )
    );

    protected function configure() {
        parent::configure();
        $this->setName('migrate-config')->setDescription('Converts a SimpleID 1 configuration file to SimpleID 2');
        $this->addArgument('input', InputArgument::REQUIRED, 'File name of SimpleID 1 config.php');
        $this->addArgument('output', InputArgument::OPTIONAL, 'Output file name, or STDOUT if missing');
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $stderr = ($output instanceof ConsoleOutputInterface) ? $output->getErrorOutput() : $output;

        include_once $input->getArgument('input');

        $config = array();

        // 1. One-to-one maps
        foreach ($this->option_map as $old => $new) {
            if (defined($old)) {
                $config[$new] = constant($old);
            }
        }

        // 2. Special processing
        if (defined('SIMPLEID_CLEAN_URL') && !constant('SIMPLEID_CLEAN_URL')) {
            $stderr->writeln('<error>SIMPLEID_CLEAN_URL is set to false. This is not supported by SimpleID 2.</error>');
        }

        if (defined('SIMPLEID_STORE') && constant('SIMPLEID_STORE') != 'filesystem') {
            $stderr->writeln('<error>Warning: Custom SIMPLEID_STORE.  This will need to be migrated manually.</error>');
        }

        if (defined('SIMPLEID_CACHE_DIR')) {
            $config['cache'] = 'folder=' . constant('SIMPLEID_CACHE_DIR');
        }

        if (defined('SIMPLEID_LOGLEVEL')) {
            $config['log_level'] = $this->log_level_map[constant('SIMPLEID_LOGLEVEL')];
        }

        // 3. Add required configuration
        $config = array_merge($config, $this->additional_config);


        // 4. Results.
        $results = <<<_END_HEADER_
<?php
#
# SimpleID configuration file.
#
# ** Generated by SimpleIDTool **
#
# ** Review this file against config.php.dist and make additional manual
# changes **
#
\$config =
_END_HEADER_;

        $results .= var_export($config, true);

        $results .= <<<_END_FOOTER_
;

#
# Insert additional PHP code here as required.
#

return \$config;

?>
_END_FOOTER_;

        if ($input->getArgument('output')) {
            file_put_contents($input->getArgument('output', $results));
        } else {
            $output->writeln($results);
        }

        return 0;
    }
}

?>
