<?php

$phpVersion = phpversion();
if (version_compare($phpVersion, '5.5', '<'))
{
    die('Required PHP version for using Material Admin API - 5.5. Your version is ' . $phpVersion);
}

$dir = __DIR__;
require($dir . '/src/Framework.php');

\Framework::start($dir);
\Framework::runApp('SB\\Api\\App');
