<?php
/*
 * SimpleID
 *
 * Copyright (C) Kelvin Mo 2014
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
use SimpleIDTool\Application;

set_time_limit(0);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$autoload_paths = [
    __DIR__.'/../vendor/autoload.php', // local
    __DIR__.'/../../../autoload.php' // dependency
];

foreach ($autoload_paths as $path) {
    if (file_exists($path)) {
        $autoloader = $path;
        break;
    }
}

if (isset($autoloader)) {
    include_once $autoloader;
} else {
    echo "Cannot load dependencies - trying installing using `composer install`\n";
    exit(1);
}

$app = new Application();
$app->run();

?>