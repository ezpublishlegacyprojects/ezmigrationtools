<?php
require 'autoload.php';

$migrator = eZMigrator::getInstance();
$migrator->start();
$migrator->run();
?>