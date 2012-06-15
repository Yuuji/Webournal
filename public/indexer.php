<?php

define('IS_CLI', true);

if(isset($_SERVER['argv']) && in_array('--develop', $_SERVER['argv']))
{
    putenv('APPLICATION_ENV=development');
}

$_SERVER["REQUEST_URI"]	= '/webournal/indexer/index/';

require('index.php');