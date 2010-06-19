<?php

class Task_DumpDBs extends eZMigrationTask {
	
	
	static $DUMP = "{mysqlPath}mysqldump -u{usr} -h {host} -p{pwd} --default_character_set utf8 {dbname} > {migrationDbDumpFolder}/{migrationDbDumpPrefix}{dbname}.sql";
	
	private $mysqlPath;
	private $export;
	
	function __construct(){
		
		$this->setTitle("Databases migration : ");
	}
	
	function run(& $dataSet){
	    if (isset($dataSet['Params']['ExportDB'])){
	    	$this->export = $dataSet['Params']['ExportDB'];
	    }
	    else {
	    	$this->export = true;
	    }
		$ini = eZINI::instance(eZMigrator::$INI_FILE,eZMigrator::$INI_PATH);
		$dbDumpPrefix = "";
		$dbDumpFolder = "";
		$doDumpBeforeUpgrade = $ini->variable("DBMigrationSettings","DumpDbBeforeUpgrade");
		if ($doDumpBeforeUpgrade){
			$dbDumpPrefix = $ini->variable("DBMigrationSettings","DumpDbNamePrefix");
			$dbDumpFolder  = $ini->variable("DBMigrationSettings","DumpDbFolder");
			$this->mysqlPath = $ini->variable("DBMigrationSettings", "MysqlPath");
			$this->dumpDbs($dataSet["DBList"], $dbDumpPrefix, $dbDumpFolder);
		}	
		return true;
	}
	
	function dumpDbs($dbs,$prefix, $folder){
		if ($this->export){
			$this->write("\tStart dumping all databases.");
			
		}
		else {
			$this->write("\tStart injecting dumps in databases.");
			
		}
		$this->manageDumps($dbs,$prefix,$folder,$this->export);
	}
	
	/**
	 * return the correct dump script if $export is true it will make dump if false it will import data.
	 * @param $export
	 * @return string
	 */
	function dumpScript($export){
		if ($export){
			return self::$DUMP;
		}
		else {
			return str_replace(array('>','mysqldump'),array('<','mysql'),self::$DUMP);	
		}
	}
	
	
	
	
	function manageDumps($dbs,$prefix = "dump_",$folder = "var/dump",$export){
		
		$datas['fields'] = array("{mysqlPath}","{migrationDbDumpFolder}","{migrationDbDumpPrefix}", "{usr}","{pwd}","{host}","{dbname}");
		$datas['values'] = array($this->mysqlPath,$folder,$prefix);
		
		$message = "";
		if ($export){
			$message = "\tCreating mysqldump for : ";
		}
		else {
			$message = "\tInjecting dump into : ";
		}
		
		$this->loopDataOnScript($dbs,$this->dumpScript($export),$datas,array("User","Password","Server","Database"),false,false,"Gestion des dumps",$message,"{dbname}");
		
	}
	
	function upgradeDBS($dbs){
		$this->write("\tStart upgrading all databases.");
		$datas['fields'] = array("{mysqlPath}", "{usr}","{pwd}","{host}","{dbname}","{sqlScript}");
		$datas['values'] = array($this->mysqlPath);
		$this->loopDataOnScript($dbs,self::$RUN_SQL_SCRIPT,$datas,array("User","Password","Server","Database"),true,$this->dataSet['Scripts']['MysqlScripts'],"Upgrade databases","Upgrading database : ","{dbname}");
		
	}	
}

?>
