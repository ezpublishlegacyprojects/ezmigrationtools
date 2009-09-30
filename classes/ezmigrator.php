<?php
//
// Definition of eZMigrator class
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
  \class eZMigrator ezmigrator.php
  
  \brief migration controler


*/


class eZMigrator {
	
	private $cli;
	private $script;
	private $dataSet;
	private $tasks;
	private $currentTaskID;
	private $mode;
	static $instance;
	
	static $MODE_WEB = "web";
	static $MODE_CLI = "cli";
	static $INI_FILE = "extension/ezmigrationtools/settings/migration.ini";
	
	/**
	 * Display text
	 *
	 * @param string $message
	 */
	function write($message){
		if ($this->mode == self::$MODE_WEB){
			echo "<p>" . $message ."</p>";
		}
		else if ($this->mode == self::$MODE_CLI){
			$this->cli->output($message);
		}
	}
	
	/**
	 * static fetcher
	 * @return eZMigrator
	 */
	static function getInstance(){
	 	if (self::$instance instanceOf eZMigrator){
	 		return self::$instance;
	 	}
	 	else {
	 		self::$instance = new eZMigrator();
	 		return self::$instance;
	 	}	
	}
	
	/**
	 * set mode client mode or other acutaly only the client mode works
	 *
	 * @param string $mode
	 */
	function setMode($mode){
		
		if ($mode == self::$MODE_CLI or $mode == self::$MODE_WEB){
				$this->mode = $mode;
			}
			else {
				$this->mode = self::$MODE_CLI;
			}
		if ($this->mode == self::$MODE_CLI){
			$this->scriptInit();
		}
	}
	
	
	/**
	 * Start the controler
	 *
	 */
	function start(){
		if (isset($this->mode)){
			echo("Initialisation \n");
			//$this->scriptInit();
			$this->write("\tRécuperation liste des bases.");
			$this->dataSet["DBList"] = $this->loadDBSettings();
			$this->write("\tRécuperation liste des tâches.");
			$this->tasks = $this->loadAvailableTasks(); 
			$this->currentTaskID = -1;
			$this->write("Fin initialisation");
			}
			else {
				echo "Please set Mode First";
			}
	
	}
	
	/**
	 * main method runs the controler
	 *
	 */
	function run(){
		$this->write("Stating process");
		$this->selectMigrationVersion();
		while($this->hasNextStep() && $this->promptNextStep()){
			$this->write("********************************************************************************");
			$this->runNextStep();
		}
		$this->endScript();
	}
	
	
	/**
	 * hasNextStep() return true if there is a next step
	 *
	 * @return unknown
	 */
	function hasNextStep(){
		return isset($this->tasks[$this->currentTaskID+1]);
	}
	
	
	/**
	 * perform next step.
	 *
	 */
	function runNextStep(){
		
		$taskID = $this->currentTaskID + 1;
		$taskName = "Task_".$this->tasks[$taskID];
		$fileName = strtolower($taskName).".php";
		$result = false;
		if (file_exists(dirname(__FILE__)."/". $fileName)){
			require_once $fileName;
			$task = new $taskName();
			$this->write($task->getTitle());
			$result = $task->run($this->dataSet);
		}
		if ($result){
			$this->currentTaskID = $taskID;
		}
		else {
			$this->endScript();
		}
	}
	
	
	
	function promptNextStep($question = "Goto next step"){
		$input = $this->askQuestion($question." (y/n) ? ");
		if ($input != 'y'){
			$this->endScript();
		}
		else {
			return true;
		}
		
	}
	
	function askQuestion($question){
		fwrite(STDOUT,$question);
		return $this->getCharTyped();
	}
	
	function getCharTyped(){
		do{
			$input = fgetc(STDIN);
		}while (trim($input) == '');
			return $input;
	}
	
	function getMigrationVersionList(){
		$ini = eZINI::fetchFromFile(self::$INI_FILE);
		$migrationList = $ini->variable("VersionMigration","AvailableVersions");
		foreach ($migrationList as $key=>$item) {
			$migrationList[$key] = split(";",$item);
		}
		return $migrationList;
	}
	
	function selectMigrationVersion(){
		$ini = eZINI::fetchFromFile(self::$INI_FILE);
		$migrationList = $ini->variable("VersionMigration","AvailableVersions");
		$this->write("Choose the version migration you mant to proceed : ");
		foreach ($migrationList as $key=>$item) {
			$migrationList[$key] = split(";",$item);
			$this->write("$key -> {$migrationList[$key][0]}");
		}
		$char = $this->askQuestion("Type the id number : ");
		
		$this->dataSet["Scripts"] = $ini->group("Version".$migrationList[$char][1]);
	}
	
	function endScript(){
		if ($this->mode == self::$MODE_CLI){
			$this->script->shutdown( 0 );
		}
	}
	
	function scriptInit(){
		if ($this->mode == self::$MODE_CLI){
			$this->cli = eZCLI::instance();
			$scriptSettings = array();
			$scriptSettings['description'] = 'Multisite update\'s';
			$scriptSettings['use-session'] = false;
			$scriptSettings['use-modules'] = false;
			$scriptSettings['use-extensions'] = false;
			$scriptSettings['debug-output'] = true;
			$scriptSettings['debug-message'] = true;
			$this->script = eZScript::instance( $scriptSettings );
			$this->script->startup();
			$this->script->initialize();
		}
		
		
	}
	
	function loadDBSettings(){
		$ini = eZINI::instance();
		$SiteAccessList = $ini->variable("SiteAccessSettings","AvailableSiteAccessList");
		$ini = null;
		$dbName = array();
		
		foreach ($SiteAccessList as $item) {
			//$this->script->SiteAccess = $item;
			$sSiteAccessINIFilePath = "settings/siteaccess/$item/site.ini.append.php";
			if (file_exists($sSiteAccessINIFilePath)){
				$ini = eZINI::fetchFromFile($sSiteAccessINIFilePath);
				//$ini->parseFile($sSiteAccessINIFilePath);
				if ($ini->hasGroup("DatabaseSettings")){
					$temp = $ini->group("DatabaseSettings");
					if (!in_array($temp,$dbName)){
							$dbName[$item] = $temp;
					}
				}
			}
			
		}
		return $dbName;
		
	}
	
	function loadAvailableTasks(){
		$ini = eZINI::fetchFromFile(self::$INI_FILE);
		$aTaskList = $ini->variable("MigrationTasks","TaskList");
		if (is_array($aTaskList)){
			return $aTaskList;
		}
		else {
			return FALSE;
		}
		
	}
	
	
}
?>