<?php

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
	static $out;
	
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
			//$this->cli->output($message);
			self::$out->outputLine($message);
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
	
	private function eZMigrator(){
		self::$out = new ezcConsoleOutput();
		$this->setMode(self::$MODE_CLI);
		$this->scriptInit();
		
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
			//echo("Initialisation \n");
			self::$out->outputLine("Initialisation");
			//$this->scriptInit();
			$this->write("\tLoad database settings.");
			$this->dataSet["DBList"] = $this->loadDBSettings();
			$this->write("\tLoading task list.");
			$this->tasks = $this->loadAvailableTasks(); 
			$this->currentTaskID = -1;
			$this->write("Migrator ready");
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
		$taskName = "Task_".$this->tasks[$taskID]["taskClassName"];
		$fileName = strtolower($taskName).".php";
		$result = false;
		if (file_exists(dirname(__FILE__)."/". $fileName)){
			require_once $fileName;
			$task = new $taskName();
			$this->write($task->getTitle());
			$task->setTestMode(eZMigrationTask::RUN_AS_TEST);
			$result = $task->run($this->dataSet);
		}
		if ($result){
			$this->currentTaskID = $taskID;
		}
		else {
			$this->endScript();
		}
	}
	
	
	
	function promptNextStep($questionString = "Goto next step"){
		$taskID = $this->currentTaskID + 1;
		$sNextStepTitle = $this->tasks[$taskID]["taskTitle"];
		$question = new ezcConsoleQuestionDialog(self::$out);
		$question->options->text = "$questionString : $sNextStepTitle ?";
		$question->options->showResults = true;
		$question->options->validator = new ezcConsoleQuestionDialogCollectionValidator(array("y","n"),"y",ezcConsoleQuestionDialogCollectionValidator::CONVERT_LOWER);
		if (ezcConsoleDialogViewer::displayDialog($question) === "y"){
			return true;
		}
		else {
			$this->endScript();
		}
	}
	
	
	function getMigrationVersionList(){
		$ini = eZINI::fetchFromFile(self::$INI_FILE);
		$migrationList = $ini->variable("VersionMigration","AvailableVersions");
		foreach ($migrationList as $key=>$item) {
			$migrationList[$key] = split(";",$item);
		}
		return $migrationList;
	}
	
	/**
	 * Display the available migrations so that the user can select the one he wants to play
	 */
	function selectMigrationVersion(){
		$ini = eZINI::fetchFromFile(self::$INI_FILE);
		$migrationList = $ini->variable("VersionMigration","AvailableVersions");
		
		$question = new ezcConsoleQuestionDialog(self::$out);
		
		
		self::$out->outputLine("Choose the version migration you want to proceed : ");
		$aOptions = array_keys($migrationList);
		foreach ($migrationList as $key=>$item) {
			$migrationList[$key] = split(";",$item);
			$this->write("$key -> {$migrationList[$key][0]}");
		}
		
		$question->options->text = "Type the id number :";
		$question->options->showResults = true;
		$question->options->validator = new ezcConsoleQuestionDialogCollectionValidator($aOptions);
		$iSelectedOption = ezcConsoleDialogViewer::displayDialog($question);
		
		
		//$char = $this->askQuestion("Type the id number : ");
		
		$this->dataSet["Scripts"] = $ini->group("Version".$migrationList[$iSelectedOption][1]);
	}
	
	
	/**
	 * Close the script session
	 *
	 */
	function endScript(){
		if ($this->mode == self::$MODE_CLI){
			$this->script->shutdown( 0 );
		}
	}
	
	/**
	 * scriptInit  sets the eZCLI Instance 
	 *
	 */
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
	
	/**
	 * Retrieves the all the differente database settings from the different siteaccess 
	 * configuration files.
	 * return Array
	 */
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
	
	/**
	 * Load the task list from the ini file
	 * return Array
	 */
	function loadAvailableTasks(){
		$ini = eZINI::fetchFromFile(self::$INI_FILE);
		$aTaskList = $ini->variable("MigrationTasks","TaskList");
		$aTasks = array();
		if (is_array($aTaskList)){
			foreach ($aTaskList as $item){
				$temp = split(";",$item);
				$aTasks[]=array("taskClassName" => $temp[0], "taskTitle"=>$temp[1]);
			}
			return $aTasks;
		}
		else {
			return FALSE;
		}
		
	}
	
	
}
?>
