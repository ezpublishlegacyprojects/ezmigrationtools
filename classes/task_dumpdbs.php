<?php
//
// Definition of Task_DumpDBs class
//
// Created on: <17-Apr-2002 09:15:27 bf>
//
// SOFTWARE NAME: eZ Migration Tools extension for eZ Publish
// SOFTWARE RELEASE: 0.1
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//

/*!
  \class Task_DumpDBs task_dumpdbs.php
  
  \brief dump task estends the migration task

  This task is in charge of dumping all the databases found in the
  settings.

  if the ini parameter DumpDbBeforeUpgrade in the [DBMigrationSettings] section
  of the migration.ini file is set to false, the dump will not be performed.

*/


class Task_DumpDBs extends eZMigrationTask {
	
	/**
	 * Template script string. the fields will be replaced
	 * with the parameters found in the settings
	 *
	 * @var string
	 */
	static $DUMP = "{mysqlPath}mysqldump -u{usr} -p{pwd} -h{hostname} --default_character_set utf8 {dbname} > {migrationDbDumpFolder}/{migrationDbDumpPrefix}{dbname}.sql";
	
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
		$ini = eZINI::fetchFromFile(eZMigrator::$INI_FILE);
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
		
		$datas['fields'] = array("{mysqlPath}","{migrationDbDumpFolder}","{migrationDbDumpPrefix}", "{usr}","{pwd}", "{hostname}","{dbname}");
		$datas['values'] = array($this->mysqlPath,$folder,$prefix);
		
		$message = "";
		if ($export){
			$message = "\tCreating mysqldump for : ";
		}
		else {
			$message = "\tInjecting dump into : ";
		}
		
		$this->loopDataOnScript($dbs,$this->dumpScript($export),$datas,array("User","Password","Server","Database"),false,false,"Gestion des dumps",$message);
		
	}
	
}
	
	
		


?>
