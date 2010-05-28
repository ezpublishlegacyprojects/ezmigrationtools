<?php

class Task_RestoreDBs extends eZMigrationTask {

	function __construct(){
		
		$this->setTitle("Databases restoration : ");
	}
	
	function run(& $dataSet){
	    if (!isset($dataSet['Params']['ExportDB'])){
	    	$dataSet['Params']['ExportDB'] = false;
	    }
	    
	    $this->emptyDatabases($dataSet['DBList']);
	    require_once "task_dumpdbs.php";
		$dumptAsk = new Task_DumpDBs();
		return $dumptAsk->run($dataSet);
		
	}
	
	function emptyDatabases($dbs){
		
		$reconnect = false;
		$connectionParams = array("User"=>"","Password"=>"","Server"=>"localhost","Port"=>"");
		$db = false;
		$tables = array();
		
		foreach ($dbs as $dbDaTa) {
			$dropResult = true;
			$reconnect = false;
			foreach ($connectionParams as $key=>$value) {
				if($dbDaTa[$key] != $value){
					$connectionParams[$key] = $dbDaTa[$key];
					if ($key != "Database")
					$reconnect = true;
				}
			}
			if ($reconnect){
				$db = mysql_connect($connectionParams["Server"],$connectionParams["User"],$connectionParams["Password"]);
			}
			if ($db){
				try {
					echo "coonecté a mysql \n";
					mysql_select_db($dbDaTa["Database"],$db);
					echo "Base de donnee : ".$dbDaTa["Database"]."\n";
					$sql = "select value from ezsite_data where name='ezpublish-version'" ;
					$result = mysql_query($sql,$db);
					$aResult = mysql_fetch_array($result);
					$ezVersion = $aResult[0];
					echo "Version : $ezVersion \n";
					if (!isset($tables[$ezVersion])){
						echo "Liste des tables de la version $ezVersion \n";
						$tblResult = mysql_query("SHOW TABLES",$db);
						if (!$tblResult){
							die( "Erreur sql ". mysql_error($db));
						}
						echo "Droping tables for database : " . $dbDaTa["Database"] ." \n";
						while(($tbl = mysql_fetch_array($tblResult)) == true) {
							$tables[$ezVersion][] = $tbl[0];
							if (!$this->dropTable($tbl[0],$db)){
								$dropResult = false;
							}
						}
						
						
					}
					else {
						echo "Droping tables for database : " . $dbDaTa["Database"] ." \n";
						foreach ($tables[$ezVersion] as $tbl) {
							if (!$this->dropTable($tbl,$db)){
								$dropResult = false;
							}
							
						}
					}
					if ($dropResult){
						echo "All tables droped ! \n";
					}
					
					
					
					
					
				}
				catch(Exception $e){
					echo "ERROR \n";
					echo $e;
				}
				
			}
			
		}
		
		
	}
	function dropTable($tblName,$dbRessource){
		$query = "DROP TABLE ".$tblName;
		if (!mysql_query($query,$dbRessource)){
			echo "Can't drop table $tblName \n";
			return false;					
		}
		else {
			
			return true;
		}
	}
	
}

?>