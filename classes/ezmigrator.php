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
	
	private $runAsDegug;
	private $dumpDBS;
	static $instance;
	
	
	static $MODE_WEB = "web";
	static $MODE_CLI = "cli";
	static $INI_FILE = "migration.ini";
	static $INI_PATH = "extension/ezmigrationtools/settings";
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

			if ($this->manageOptions()){
			    if ($this->runAsDegug){
			    	$this->write("Runing in debug mode");
			    }
				$this->write("Initialisation");
				
				$this->write("\tLoad database settings.");
				$this->dataSet["DBList"] = $this->loadDBSettings();
				$this->write("\tLoading task list.");
				$this->tasks = $this->loadAvailableTasks(); 
				$this->currentTaskID = -1;
				$this->write("Migrator ready");
			}
			else {
				$this->endScript();
			}
		}
		else {
				$this->write( "Please set Mode First");
		}
		
	}
	
	
	/**
	 * ManageOptions function manages the option passed when calling the script
	 * h, help displays the help content with all the options
	 * d, debug this options can active the debug mod, in this case the scripts 
	 * 			are not executed
	 * x, nodump prenvents the databases dumping script to be executed
	 * @return boolean
	 * 	return true if the script execution must be stoped.
	 */
	function manageOptions(){
		$input = new ezcConsoleInput();
		$helpOption = $input->registerOption(new ezcConsoleOption('h','help'));
		$debugOption = $input->registerOption(new ezcConsoleOption('d','debug'));
		$dumpOption = $input->registerOption(new ezcConsoleOption('x','nodump'));
		
		$helpOption->shorthelp = "Display help";
		$debugOption->shorthelp = "Activate the debug mode";
		$dumpOption->shorthelp = "if set the dump action will not be performed";
		
		try{
			$input->process();
		}catch (ezcConsoleOptionException $e){
			die($e->getMessage());
			return false;
		}
		
		if ($helpOption->value === true){
			$this->write("");
			$this->write($input->getSynopsis());
			foreach ($input->getOptions() as $option) {
				$this->write("-{$option->short}/{$option->long}: {$option->shorthelp}");
			
			}
			return false;
		}
		
		if ($debugOption->value === true){
			$this->runAsDegug = eZMigrationTask::RUN_AS_TEST;
		}
		else {
			$this->runAsDegug = eZMigrationTask::RUN_AS_WORK;
		}
		
		if ($dumpOption->value === true){
			$this->dumpDBS = false;	
		}
		else {
			$this->dumpDBS = true;
		}
		return true;
		
	}
	
	/**
	 * main method runs the controler
	 *
	 */
	function run(){
		$this->write("Stating process");
		if ($this->selectMigrationVersion()){
			
			while($this->hasNextStep() && $this->promptNextStep()){
				$this->write("********************************************************************************");
				$this->runNextStep();
			}
			$this->endScript();	
		}
		
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
			$task->setTestMode($this->runAsDegug);
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
		$question->options->validator = new ezcConsoleQuestionDialogCollectionValidator(array("y","n","s","a"),"y",ezcConsoleQuestionDialogCollectionValidator::CONVERT_LOWER);
		$result = ezcConsoleDialogViewer::displayDialog($question);
		if ($result === "y"){
			return true;
		}
		elseif ($result === "s" ){
			$this->currentTaskID++;
			$this->promptNextStep();
			
		}
		else{
			$this->endScript();
			return false;
		} 
	}
	
	
	function getMigrationVersionList(){
		
		$ini = eZINI::instance(self::$INI_FILE,self::$INI_PATH);
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
		
		$migrationList = $this->getMigrationVersionList();
		$question = new ezcConsoleQuestionDialog(self::$out);
		
		
		self::$out->outputLine("Choose the version migration you want to proceed : ");
		$aOptions = array_keys($migrationList);
		foreach ($migrationList as $key=>$item) {
			$this->write("$key -> {$item[0]}");
		}
		$this->write("q or Q -> quit");
		$aOptions[]="q";
		$aOptions[]="Q";
		
		$question->options->text = "Type the id number :";
		$question->options->showResults = true;
		$question->options->validator = new ezcConsoleQuestionDialogCollectionValidator($aOptions);
		$iSelectedOption = ezcConsoleDialogViewer::displayDialog($question);
		
		$iSelectedOption = strtolower($iSelectedOption);
		//$char = $this->askQuestion("Type the id number : ");
		
		if ($iSelectedOption != "q" ){
			$ini = eZINI::instance(self::$INI_FILE,self::$INI_PATH);
			$this->dataSet["Scripts"] = $ini->group("Version".$migrationList[$iSelectedOption][1]);
			return true;
		}
		else {
			// exection is aborted
			$this->endScript();
			return false;
		}
	}
	
	
	/**
	 * Close the script session
	 *
	 */
	function endScript(){
		$this->write("End of script");
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
		$ini = eZINI::instance(self::$INI_FILE,self::$INI_PATH);
		if ($ini->hasVariable("MigrationTasks","TaskList")){
			$aTaskList = $ini->variable("MigrationTasks","TaskList");
			$aTasks = array();
			if (is_array($aTaskList)){
				foreach ($aTaskList as $item){
					$temp = split(";",$item);
					if ($temp[0] == "DumpDBs"){
						if ($this->dumpDBS){
						
							$aTasks[]=array("taskClassName" => $temp[0], "taskTitle"=>$temp[1]);
						} 
					}
					else {
						$aTasks[]=array("taskClassName" => $temp[0], "taskTitle"=>$temp[1]);
					}
				}
				return $aTasks;
			}
			else {
				return FALSE;
			}
		}
		else {
			$this->write("Error, the MigrationTasks group is not correctly defined in the ".self::$INI_FILE ."file, TaskList not found");
			return false;
		}
	
		
		
	}
	
	
}
?>
