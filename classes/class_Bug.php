<?php
/*
*******************************************************************
class_Bug.php
This PHP file defines Bug object class
NOTES
Date        Change
-------------------------------------------------------------
2015-12-02	Refactored and added Release Date, Run Time and UPC
2015-12-18 	Removed unneeded rootpath and includes
2017-08-04	Added Cache ID
*******************************************************************
*/	

//include DB Class		
include_once (CLASS_DIR . "/class_DB.php");

//include Error Class
include_once (CLASS_DIR . "/class_Error.php");

class Bug
{

	var $DB;

	//Bug Table Values
	var $nBugId = 0;
	var $sBugName = NULL;
	var $sNickName = NULL;
	var $sPosition = NULL;	
	var $sCacheName = NULL;
	var $sCacheID = NULL;
	var $sTrackingNumber = NULL;
	var $nTrackableId = 0;
	var $sReferenceNumber = NULL;


	var $dtLastUpdate = NULL;
	
	//Booleans used in Searching
	var $bFuzzyNameSearch = FALSE;
		
	var $sErrorMessage = "";

	/*****************************
	 * Arrays of Related Objects *
	 *****************************/
	//Bug Records matching search criteria
	var $aBugRecords = array();
	//Bug Errors
	var $aBugErrors = array();

	//Constructor
   function __construct() 
   {
		$this->DB = new Database();
   }
  

	/*
	 ********************************************************************************
	 * getBug()
	 * 
	 * This function retrieves Bug data based on data in the properites and
	 * returns an array of Bug Objects
	 ********************************************************************************
	*/
	function getBug()
	{
	
		$this->aBugRecords = array();
		
		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the Bug)
		$sql =	"SELECT BUG.* ";
		$sql .= "  FROM BUG ";
		$sql .=	" WHERE 1=1 ";
		$this->buildWhereClause($sql);

//DEBUG
//echo "SQL={$sql}<BR>";
							
		$result = NULL;
		
		if(!$this->executeSQL($sql,$result))
		{
			return -1;
		}
		else
		{

			$iBugCount = 0;

			while($row = mysqli_fetch_array($result))
			{

				$oNextBug = new Bug();
				
				//Load each product into a Bug Object
				$this->loadBugObject($oNextBug, $row);

				//Then add the object to the array of found products
				$this->aBugRecords[$iBugCount] = $oNextBug;
				
				$iBugCount++;

			}
		
			$this->DB->closeDB();
			return TRUE;

		}

	}

	/*
	 ********************************************************************************
	 * buildWhereClause()
	 * 
	 * This function builds a where clause for retrieving payment records 
	 ********************************************************************************
	*/
	function buildWhereClause(&$sql)
	{
	
	//If a specific Bug ID is being searched, match on the ID
		if ($this->nBugId > 0)
		{
			$sql .= " AND BUG_ID = {$this->nBugId}";
		}
		else
		{
		//Otherwise build an SQL statement based on the values in the properties
			
			// ****************
			// * Bug NAME *
			// ****************
			if (!empty($this->sBugName))
			{
				//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
				if ($this->bFuzzyNameSearch)
				{
					$sql .= " AND BUG_NAME LIKE '%{$this->sBugName}%'";
				}
				else
				{
					$sql .= " AND BUG_NAME = '{$this->sBugName}'";
				}
			}
			
			if (!empty($this->sTrackingNumber))
			{
				$sql .= " AND TRACKING_NUMBER = '{$this->sTrackingNumber}'";
			}


			// *************
			// * NICK NAME *
			// *************
			if (!empty($this->sNickName))
			{
				//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
				if ($this->bFuzzyNameSearch)
				{
					$sql .= " AND NICK_NAME LIKE '%{$this->sNickName}%'";
				}
				else
				{
					$sql .= " AND NICK_NAME = '{$this->sNickName}'";
				}
			}

			
			// *************
			// * POSITION *
			// *************
			if (!empty($this->sNickName))
			{
				$sql .= " AND POSITION = '{$this->sPosition}'";
			}
			
			
			// **************
			// * CACHE NAME *
			// **************	
			if (!empty($this->sCacheName))
			{
				$sql .= " AND CACHE_NAME = '{$this->sCacheName}'";
			}

			// ************
			// * CACHE ID *
			// ************	
			if (!empty($this->sCacheId))
			{
				$sql .= " AND CACHE_ID = '{$this->sCacheId}'";
			}

			// *******************
			// * TRACKING NUMBER *
			// *******************	
			if (!empty($this->sTrackingNumber))
			{
				$sql .= " AND TRACKING_NUMBER = '{$this->sTrackingNumber}'";
			}

			// ****************
			// * TRACKABLE ID *
			// ****************	
			if (!empty($this->nTrackableId))
			{
				$sql .= " AND TRACKABLE_ID = {$this->nTrackableId}";
			}

			// *******************
			// * REFERENCE NUMBER *
			// *******************	
			if (!empty($this->sReferenceNumber))
			{
				$sql .= " AND TRACKING_NUMBER = '{$this->sReferenceNumber}'";
			}
			
			
			// ************
			// * ORDER BY *
			// ************
			//ALPHABETICAL BY NAME
			$sql .= " ORDER BY BUG_NAME DESC";	

		}
	}

	/*
	 ********************************************************************************
	 * executeSQL()
	 * 
	 * This function opens a DB connection and attempts to execute an SQL statement 
	 ********************************************************************************
	*/
	function executeSQL(&$sql, &$result)
	{
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "BUG001 - FAILED TO OPEN DB: {$this->DB->dbError}";
			return false;
		}

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);

		if (!$result) 
		{

			 $this->sErrorMessage = "BUG002 - " . mysqli_error($this->DB->dbConnection);
			 $this->DB->closeDB();

			 return false;
		}
		else
		{
			return true;
		}
	}
		
	/*
	 ********************************************************************************
	 * Insert Bug()
	 * 
	 * This function inserts a Bug record 
	 ********************************************************************************
	*/
	function insertBug()
	{
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "BUG003 - FAILED TO OPEN DB: ";
			return FALSE;
		}

		$this->escapeSpecialChars();

		$sql =	" INSERT INTO BUG";
		$sql .= " (BUG_NAME,";		
		$sql .= "  NICKNAME,";
		$sql .= "  POSITION,";
		$sql .= "  CACHE_NAME,";
		$sql .= "  CACHE_ID,";
		$sql .= "  TRACKING_NUMBER,";
		$sql .= "  TRACKABLE_ID,";
		$sql .= "  REFERENCE_NUMBER,";
		$sql .= "  LAST_UPDATE)";		
		$sql .= " VALUES (";
		$sql .= "'{$this->sBugName}',";	
		$sql .= "'{$this->sNickName}',";
		$sql .= "'{$this->sPosition}',";
		$sql .= "'{$this->sCacheName}',";
		$sql .= "'{$this->sCacheID}',";		
		$sql .= "'{$this->sTrackingNumber}',";	
		$sql .= "{$this->nTrackableId},";	
		$sql .= "'{$this->sReferenceNumber}',";		
		$sql .= "SYSDATE()";	
		$sql .= ")";	
		
//DEBUG
//echo "SQL={$sql}<BR>";

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);
	
		//SQL Error
		if (!$result) 
		{

			 $this->sErrorMessage = "BUG004 - FAILED TO INSERT Bug RECORD - " . mysqli_error($this->DB->dbConnection);
			 return FALSE;
		}
		else 
		{
			$this->nBugId = mysqli_insert_id($this->DB->dbConnection);
			return TRUE;	
		}
		

	}	


	/*
	 ********************************************************************************
	 * updateBug()
	 * 
	 * This Updates a Bug record
	 ********************************************************************************
	*/
	function updateBug()
	{
	
		if ($this->nBugId > 0)
		{

			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "BUG005 - FAILED TO OPEN DB: ";
				return FALSE;
			}

			$this->escapeSpecialChars();
				
			$sql =	" UPDATE Bug ";
			$sql .= "    SET BUG_NAME = '{$this->sBugName}'";
			$sql .= "       ,NICKNAME = '{$this->sNickName}'";
			$sql .= "       ,POSITION = '{$this->sPosition}'";
			$sql .= "       ,CACHE_NAME = '{$this->sCacheName}'";
			$sql .= "       ,CACHE_ID = '{$this->sCacheID}'";
			$sql .= "       ,TRACKING_NUMBER = '{$this->sTrackingNumber}'";
			$sql .= "       ,TRACKING_NUMBER = '{$this->sTrackingNumber}'";
			$sql .= "       ,TRACKING_NUMBER = '{$this->sTrackingNumber}'";
			$sql .= "       ,LAST_UPDATE = SYSDATE()";
			$sql .= "  WHERE BUG_ID  = {$this->nBugId}";
		
//DEBUG
//echo "SQL={$sql}<BR>";

			
			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result) {	

				 $this->sErrorMessage = "BUG006 - FAILED TO UPDATE BUG: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}
		
		}
		else
		{
			 $this->sErrorMessage = "BUG010 - NO BUG_ID SPECIFIED ";
			 return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deleteBug()
	 * 
	 * This function deletes a Bug record
	 ********************************************************************************
	*/
	function deleteBug()
	{
		if ($this->nBugId > 0)
		{
				
			$sql =	" DELETE FROM BUG";
			$sql .= " WHERE BUG_ID = {$this->nBugId}";
		
//DEBUG
//echo "SQL={$sql}<BR>";

			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "BUG007 - FAILED TO OPEN DB: ";
				return FALSE;
			}		

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result)
			{	
				 $this->sErrorMessage = "BUG008 - FAILED TO DELETE BUG: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}

		}
		else
		{
			$this->sErrorMessage = "BUG009 - CAN NOT DELETE Bug BECAUSE NO BUG ID SUPPLIED. ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * loadBugObject()
	 * This function loads a given row from the Bug table into a Bug object.  
	 ********************************************************************************
	*/
	function loadBugObject(&$oBug, &$row)
	{

			$oBug->nBugId = $row['BUG_ID'];			
			$oBug->sBugName = $row['BUG_NAME'];
			$oBug->sNickName = $row['NICKNAME'];
			$oBug->sPosition = $row['POSITION'];
			$oBug->sCacheName = $row['CACHE_NAME'];
			$oBug->sCacheID = $row['CACHE_ID'];
			$oBug->sTrackingNumber = $row['TRACKING_NUMBER'];
			$oBug->nTrackableId = $row['TRACKABLE_ID'];
			$oBug->sReferenceNumber = $row['REFERENCE_NUMBER'];
	}
	/*
	 ********************************************************************************
	 * escapeSpecialChars()
	 * This function escapes any special characters in the objects properties
	 * prior to a DB update.  The DB connection must be established prior to 
	 * calling this function.  
	 ********************************************************************************
	*/
	function escapeSpecialChars()
	{
	
		$this->sBugName = mysqli_real_escape_string($this->DB->dbConnection,$this->sBugName);	
		$this->sBugName = mysqli_real_escape_string($this->DB->dbConnection,$this->sNickName);	
		$this->sBugName = mysqli_real_escape_string($this->DB->dbConnection,$this->sCacheName);	
		$this->sBugName = mysqli_real_escape_string($this->DB->dbConnection,$this->sPosition);	

	}


}
	
?>