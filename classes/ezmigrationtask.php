<?php
//
// Definition of eZMigrationTask class
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
  \class eZMigrationTask ezmigrationtask.php
  
  \brief generic migration task

  the title property sets the task title a real task must implement this class
  and set the title thrue the setTitle method.

  the run method is trigerd to execute the task

*/
class eZMigrationTask {
	
	/**
	 * Title of the task
	 *
	 * @var string
	 */
	protected   $title;
	
	/**
	 * getTitle() return the task title
	 *
	 * @return string
	 */
	function getTitle(){
		return $this->title;
	}
	
	/**
	 * run() executes the task
	 *
	 * @param array $dataSet the dataset holds generic datas for all the tasks
	 * @return boolean
	 */
	function run(& $dataSet){
		return true;
	}
	
	/**
	 * Displays a message 
	 *
	 * @param string $message
	 */
	function write($message){
		$mig = eZMigrator::getInstance();
		$mig->write($message);
	}
	
	
	/**
	 * sets The task title 
	 *
	 * @param string $title if empty a defaut title si set.
	 */
	function setTitle($title = ""){
		if ($title == ""){
			$this->title = "Tache de migration ". __CLASS__ ." : ";
		}
		else {
			$this->title = $title;
		}
	}
	
/**
	 * LoopDataOnScript is a recursive looping function. The prupose is
	 * to run a script specified by a pattern on datas in one or two arrays
	 * for example run php scripts on multiple siteaccess (eache one having 
	 * a specific database).
	 * 
	 * A scriptPattern can be this :
	 * {PHPPath}php {sqlScript} -s {siteaccess}
	 * 
	 * Each field {...} must be in the $datas['fields'] array. In this example
	 * only the first one is known on the start : {PHPPath} the to other ones 
	 * will be set by the two arrays $loopArray and $loopArray2.
	 * 
	 * The $data['values'] array will be set with on ellement on matching with 
	 * PHPPath. 
	 * then during the first loop on $loopArray, the items will be used to complete
	 * the $data['values'] by a push. 
	 * The fields in the $datas['fields'] array must be set in the correct order.
	 * 
	 * If $loopArray must be used to set elements in datas['values'], the $specificField1
	 * parameter must be used. In can be a boolean a string or an array of strings.
	 * It depends on how is built the $loopArray parameter.
	 * 		if $loopArray is an array of values and $specificField1 is set to true, each
	 * 				element of the array will be used to fill $datas['values']
	 * 		if $loopArray is an array of arrays then there is two ways to add
	 * 				$data['values']
	 * 			if $specificField1 is a string then for each item the 
	 * 				$item[$specificField1] field will be used ;
	 *          if $specificField1 is an array then each of it's elements
	 * 				will be used on the $loopArray items.
	 * 
	 * $mainmessage is displayed before looping
	 * $loopmessage is displayed before execution and is completed with the sepcifivalues
	 * used. 
	 * 
	 * $testMode if set to true, the command line build will only be displayed but not executed.
	 * 
	 * @param array $loopArray the first set of data to loop on
	 * @param string $scriptPatern the script template
	 * @param array $datas 
	 * @param mixed $specificField1 
	 * @param mixed $specificField2
	 * @param array $loopArray2
	 * @param string $mainmessage
	 * @param string $loopMessage
	 * @param boolean $testMode
	 * @return boolean
	 */

	function loopDataOnScript($loopArray,$scriptPatern,$datas,$specificField1 = false,$specificField2 = false,$loopArray2 = false,$mainmessage = "", $loopMessage = "",$testMode = false){
		$this->write($mainmessage);
		$execDatas = $datas;
		
		$specificValueFieldId = count($datas['values']);
		
		$MessageField = "";
		foreach ($loopArray as $item) {
			$startId = $specificValueFieldId;
			//$loopMessage = "";
			if ($specificField1){
				   if (is_array($specificField1)){
					   	foreach ($specificField1 as $field){
					   			$execDatas['values'][$startId] = $item[$field];
					   			$MessageField = $item[$field];
					   			$startId++;
					   	}	
				   }
				   else if (is_string($specificField1)) {
					$execDatas['values'][$startId] = $item[$specificField1];
					$MessageField = $specificField1;
				   }
				   else if(is_bool($specificField1)){
				   	$execDatas['values'][$startId] = $item;
				   }
					if ($specificField2) {
						$loopMessage.= " ". $MessageField;
					}
					else {
						$this->write($loopMessage." ".$MessageField);
					}
			}
			if (is_array($loopArray2)){
				$this->loopDataOnScript($loopArray2,$scriptPatern,$execDatas,$specificField2,false,false,"",$loopMessage,$testMode);
			}
			else {
				
				
				$this->execute($execDatas,$scriptPatern,$testMode);
			}
		}
		
	}

	/**
	 * execute a scriptPattern replacing all the $data['fields'] 
	 * of the pattern with the $data['values']
	 * only displayes the commnd line if $test is true.
	 *
	 * @param array $data
	 * @param string $script
	 * @param boolean $test
	 * @return boolean
	 */
	function execute($data,$script,$test = false){
		$result = false;
		
		if ($test){
			$this->write("USING TEST MODE NO EXECUTION");
		}
		$this->write("executing : ".str_replace($data['fields'],$data['values'],$script));
		if (!$test){
			passthru(str_replace($data['fields'],$data['values'],$script),$result);
		}
		return $result;
	}

}
?>