<?php

//This include defines the relative path to the root directory from this sub-directory
include_once ("root.inc.php");

//inlcude web site settings
include_once ($ROOT . "/includes/WebsiteSettings.php");
//inlcude admin settings
include_once (ADMIN_DIR . "/includes/AdminSettings.php");

class Database
{

	var $dbConnection="";
	var $dbError = "";
	

	/*
 	********************************************************************************
	 * openDB
	 * 
	 * This function opens a mySQL database the name of which is passed in as a 
	 * paramter. 
	 ********************************************************************************
	*/
	function openDB()
	{
	    
//DEBUG
//echo "HOST: ". DB_HOST . "<BR>LOGIN: ". DB_LOGIN . "<BR>PASSWORD: " . DB_PASSWORD . "<BR>DB Name: " . DB_NAME;
		
		if (!$this->dbConnection = mysql_connect(DB_HOST,DB_LOGIN,DB_PASSWORD))  {
			$this->dbError="DB001 - Failed to Connect to " . DB_HOST;
			return false;
		}
		else {
			if (!mysql_select_db(DB_NAME,$this->dbConnection)) {
				$this->dbError= "DB002 - " . mysql_error($this->dbConnection);
				return false;
			}
			else {
				return true;
			}
	
		}
	
	}

	/*
	 ********************************************************************************
	 * closeDB
	 * 
	 * This function closes a DB connection which is passed in as a paramter.
	 ********************************************************************************
	*/
	function closeDB()
	{
	 
		mysql_close($this->dbConnection);
	
	}

	/*
	 ********************************************************************************
	 * getVersion
	 * 
	 * This function gets the Db Version.
	 ********************************************************************************
	*/
	function getVersion()
	{
	
		$this->openDB();
	 
		return mysql_get_server_info($this->dbConnection);	 

	}
		

}



