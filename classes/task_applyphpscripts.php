<?php

class Task_ApplyPHPScripts extends eZMigrationTask {
	
	
	static $RUN_PHP_SCRIPT = "{PHPPath}php {sqlScript} -s {siteaccess}";
	
	
	private $phpPath;
	
	function __construct(){
		
		$this->setTitle("Databases migration : ");
	}
	
	function run(& $dataSet){
		$ini = eZINI::fetchFromFile(eZMigrator::$INI_FILE);
        $this->phpPath = $ini->variable("DBMigrationSettings", "PHPScriptPath");
		return $this->runPhpScripts(array_keys($dataSet["DBList"]),$dataSet['Scripts']['phpScripts']);
	}
	
	
	function runPhpScripts($siteAccesses,$scripts){
		$this->write("\tStart upgrading with php Scripts.");
		
		$datas = array('fields' => array('{PHPPath}','{siteaccess}','{sqlScript}'),
					   'values' => array($this->phpPath));
		
		$this->loopDataOnScript($siteAccesses,self::$RUN_PHP_SCRIPT,$datas,true,true,$scripts,"Runings php scripts on siteaccesses","runing on","{siteaccess}");
	
		return true;
	}
	
	
	
}

?>