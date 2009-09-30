<?php
require 'autoload.php';

$migrator = eZMigrator::getInstance();
$migrator->setMode(eZMigrator::$MODE_CLI);
$migrator->start();
$migrator->run();
?>