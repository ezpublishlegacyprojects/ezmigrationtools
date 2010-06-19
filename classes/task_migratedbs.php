<?php

class Task_MigrateDBs extends eZMigrationTask {
	
	
	static $RUN_SQL_SCRIPT = "{mysqlPath}mysql -u{usr} -h {host} -p{pwd} {dbname} < {sqlScript}";
	
	
	private $mysqlPath;
	
	function __construct(){
		
		$this->setTitle("Databases migration : ");
	}
	
	function run(& $dataSet){
		$ini = eZINI::instance(eZMigrator::$INI_FILE,eZMigrator::$INI_PATH);
        $this->mysqlPath = $ini->variable("DBMigrationSettings", "MysqlPath");
		return $this->upgradeDBS($dataSet["DBList"],$dataSet['Scripts']['MysqlScripts']);
	}
	
	
	function upgradeDBS($dbs,$scripts){
		$this->write("\tStart upgrading all databases.");
		$datas['fields'] = array("{mysqlPath}", "{usr}","{pwd}","{host}","{dbname}","{sqlScript}");
		$datas['values'] = array($this->mysqlPath);
		$this->loopDataOnScript($dbs,self::$RUN_SQL_SCRIPT,$datas,array("User","Password","Server","Database"),true,$scripts,"Upgrade databases","Upgrading database : ","{dbname}");
		return true;
	}
	
	
	
}

?>
