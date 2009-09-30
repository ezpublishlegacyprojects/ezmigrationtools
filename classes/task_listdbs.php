<?php
class Task_ListDbs extends eZMigrationTask {
	
	
	function __construct(){
		$this->setTitle("Display all databases of siteacces :");
	}
	
	
	
	
	function run(& $dataSet){
		
		foreach ($dataSet['DBList'] as $siteaccess => $dbname) {
			$this->write("\t$siteaccess -> {$dbname['Database']}");	
		}
		return true;
	}
}
?>