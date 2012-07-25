<?php

define('IS_CLI', true);

if(isset($_SERVER['argv']) && in_array('--develop', $_SERVER['argv']))
{
    putenv('APPLICATION_ENV=development');
}

if(isset($_SERVER['argv']) && in_array('--testing', $_SERVER['argv']))
{
    putenv('APPLICATION_ENV=testing');
}

$_SERVER["REQUEST_URI"]	= '/webournal/indexer/index/';

require('index.php');