#!/usr/bin/env php
<?php
Phar::mapPhar('simpleid-tool.phar');
require 'phar://simpleid-tool.phar/bin/simpleid-tool.php';
__HALT_COMPILER();
