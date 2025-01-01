<?php
/*
 * SimpleID
 *
 * Copyright (C) Kelvin Mo 2022-2025
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

namespace SimpleIDTool;

use Symfony\Component\Console\Application as SymfonyConsoleApplication;

use SimpleIDTool\Command\API\APICommand;
use SimpleIDTool\Command\Standalone\PasswordCommand;
use SimpleIDTool\Command\Standalone\GenerateSecretCommand;
use SimpleIDTool\Command\Migration\MigrateConfigCommand;
use SimpleIDTool\Command\Migration\MigrateUserCommand;

class Application extends SymfonyConsoleApplication {
    public function __construct() {
        parent::__construct('SimpleID Tool');

        $this->addCommands([
            new PasswordCommand(),
            new GenerateSecretCommand(),
            new MigrateConfigCommand(),
            new MigrateUserCommand(),
            new APICommand(),
        ]);
    }
}

?>