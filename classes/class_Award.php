<?php
/*
*******************************************************************
class_Award.php
This PHP file defines Award object class
NOTES
Date        Change
-------------------------------------------------------------
*******************************************************************
*/	

//include DB Class		
include_once (CLASS_DIR . "/class_DB.php");

//include Error Class
include_once (CLASS_DIR . "/class_Error.php");

class Award
{

	var $DB;
	var $sqlResult;

	//Award Table Values
	var $nAwardID = 0;
	var $sAwardName = NULL;
	var $sAwardDescription = NULL;
	var $dtAwardDate = NULL;
	var $sAwardURL = NULL;
	var $nArtistID = 0;
	var $nCDID = 0;
	var $nSongID = 0;
	var $ctAwardDate = NULL;
	var $bPerformanceRelated = FALSE;
	var $sAwardImage = NULL;
	var $dtLastUpdate = NULL;
	var $sOrderBy = NULL;
	var $bFuzzyNameSearch = FALSE;
	
	var $sErrorMessage = "";

	/*****************************
	 * Arrays of Related Objects *
	 *****************************/
	//Award Records matching search criteria
	var $aAwardRecords = array();
	//Award Errors
	var $aAwardErrors = array();

	//Constructor
   function __construct() 
   {
		$this->DB = new Database();
   }
  

	/*
	 ********************************************************************************
	 * getAward()
	 * 
	 * This function retrieves Award data based on data in the properites and
	 * returns an array of Award Objects
	 ********************************************************************************
	*/
	function getAward()
	{
	
		$this->aAwardRecords = array();
		
		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the Award)
		$sql =	"SELECT AWARD.* ";
		$sql .= "  FROM AWARD ";
		$sql .=	" WHERE 1=1 ";
		$this->buildWhereClause($sql);

//DEBUG
//echo "SQL={$sql}<BR>";
							
		$result = NULL;
		
		if(!$this->executeSQL($sql))
		{
			error_log('SNG002 - ERRROR EXECUTING SQL IN SONG CLASS.  SQL:' . $sql | 'EMPTY SQL');
			return -1;
		}
		else
		{

			$iAwardCount = 0;

			while($row = mysqli_fetch_array($this->sqlResult))
			{

				$oNextAward = new Award();
				
				//Load each product into a Award Object
				$this->loadAwardObject($oNextAward, $row);

				//Then add the object to the array of found products
				$this->aAwardRecords[$iAwardCount] = $oNextAward;
				
				$iAwardCount++;

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
	
	//If a specific Award ID is being searched, match on the ID
		if ($this->nAwardID > 0)
		{
			$sql .= " AND AWARD_ID = {$this->nAwardID}";
		}
		else
		{
		//Otherwise build an SQL statement based on the values in the properties
			
			// ***************
			// * AWARD NAME *
			// ***************
			if (!empty($this->sAwardName))
			{
				//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
				if ($this->bFuzzyNameSearch)
				{
					$sql .= " AND AWARD_NAME LIKE '%{$this->sAwardName}%'";
				}
				else
				{
					$sql .= " AND AWARD_NAME = '{$this->sAwardName}'";
				}
			}
			// *********************
			// * AWARD DESCRIPTION *
			// *********************
			if (!empty($this->sAwardDescription))
			{
				$sql .= " AND AWARD_DESCRIPTION LIKE '%{$this->sAwardDescription}%'";
			}

			// ****************
			// * AWARD DATE *
			// ****************
			if (!empty($this->dtAwardDate))
			{
				$sql .= " AND AWARD_DATE LIKE '%{$this->ctAwardDate}%'";
			}
			
			// **************
			// * AWARD URL *
			// **************
			if (!empty($this->sAwardURL))
			{
				$sql .= " AND AWARD_URL = '{$this->sAwardURL}'";
			}

			// *************
			// * ARTIST_ID *
			// *************
			if (!empty($this->nArtistID))
			{
				$sql .= " AND ARTIST_ID = {$this->nArtistID}";
			}

			// *********
			// * CD_ID *
			// *********
			if (!empty($this->nCDID))
			{
				$sql .= " AND CD_ID = {$this->nCDID}";
			}

			// ***********************
			// * PERFORMANCE RELATED *
			// ***********************
			if ($this->bPerformanceRelated)
			{
				$sql .= " AND PERFORMANCE_RELATED = {$this->bPerformanceRelated}";
			}
			
			// ***********
			// * SONG_ID *
			// ***********
			if (!empty($this->nSongID))
			{
				$sql .= " AND SONG_ID = {$this->nSongID}";
			}

		
			// ************
			// * ORDER BY *
			// ************
			//ORDER BY
			switch($this->sOrderBy)
			{
				case NAME_ORDER:
				$sql .= " ORDER BY AWARD_NAME";	
				break;

				case DATE_ORDER:
				$sql .= " ORDER BY AWARD_DATE DESC";	
				break;

				case UPDATE_ORDER:
				$sql .= " ORDER BY LAST_UPDATE DESC";	
				break;
				
				default:
				$sql .= " ORDER BY AWARD_DATE DESC";	
				break;	

			}
		}
	}

	/*
	 ********************************************************************************
	 * executeSQL()
	 * 
	 * This function opens a DB connection and attempts to execute an SQL statement 
	 ********************************************************************************
	*/
	function executeSQL(&$sql): bool
	{
		//Open the DB
		if (!$this->DB->openDB()) {
			$this->sErrorMessage = "AWARD01A ERROR - FAILED TO OPEN DB: {$this->DB->dbError}";
			return false;
		}

		try {
			//Execute the SQL		
			$this->sqlResult = mysqli_query($this->DB->dbConnection, $sql);
		} catch (Exception $e) {
			// Handle the exception
			error_log('AWARD01B ERROR - FAILED TO EXECUTE SQL' . $e->getMessage() . ' - SQL: ' . $sql);
			return false;
		} finally {
			if (empty($this->sqlResult)) {
				$this->sErrorMessage = 'AWARD01C ERROR: - ' . mysqli_error($this->DB->dbConnection) . ' SQL: ' . $sql;
				error_log($this->sErrorMessage);
				$this->DB->closeDB();
				return false;
			} else {
				return true;
			}
		}

	}
		
	/*
	 ********************************************************************************
	 * Insert Award()
	 * 
	 * This function inserts a Award record 
	 ********************************************************************************
	*/
	function insertAward()
	{
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "AWARD003 - FAILED TO OPEN DB: ";
			return FALSE;
		}

		$this->escapeSpecialChars();

		$sql =	" INSERT INTO AWARD";
		$sql .= " (AWARD_NAME,";		
		$sql .= "  AWARD_DESCRIPTION,";
		$sql .= "  AWARD_DATE,";
		$sql .= "  AWARD_URL,";
		$sql .= "  ARTIST_ID,";
		$sql .= "  CD_ID,";
		$sql .= "  SONG_ID,";
		$sql .= "  PERFORMANCE_RELATED,";		
		$sql .= "  AWARD_IMAGE,";		
		$sql .= "  LAST_UPDATE)";		
		$sql .= " VALUES (";
		$sql .= "'{$this->sAwardName}',";	
		$sql .= "'{$this->sAwardDescription}',";	
		$sql .= "'{$this->dtAwardDate}',";	
		$sql .= "'{$this->sAwardURL}',";	
		if(empty($this->nArtistID))
		{
			$sql .= "NULL,";	
		}
		else
		{
			$sql .= "{$this->nArtistID},";	
		}

		if(empty($this->nCDID))
		{
			$sql .= "NULL,";	
		}
		else
		{
			$sql .= "{$this->nCDID},";	
		}
		if(empty($this->nSongID))
		{
			$sql .= "NULL,";	
		}
		else
		{
			$sql .= "{$this->nSongID},";	
		}

		if ($this->bPerformanceRelated)
		{
			$sql .=  "TRUE,";	
		}
		else
		{
			$sql .=  "FALSE,";	
		}		
		$sql .= "'{$this->sAwardImage}',";	
		$sql .= "SYSDATE()";	
		$sql .= ")";	
		
//DEBUG
//echo "SQL={$sql}<BR>";

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);
	
		//SQL Error
		if (!$result) 
		{

			 $this->sErrorMessage = "AWARD004 - FAILED TO INSERT AWARD RECORD - " . mysqli_error($this->DB->dbConnection);
			 return FALSE;
		}
		else 
		{
			$this->nAwardID = mysqli_insert_id($this->DB->dbConnection);
			return TRUE;	
		}
		

	}	


	/*
	 ********************************************************************************
	 * updateAward()
	 * 
	 * This Updates a Award record
	 ********************************************************************************
	*/
	function updateAward()
	{
	
		if ($this->nAwardID > 0)
		{

			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "AWARD005 - FAILED TO OPEN DB: ";
				return FALSE;
			}

			$this->escapeSpecialChars();
				
			$sql =	" UPDATE AWARD ";
			$sql .= "    SET AWARD_NAME = '{$this->sAwardName}'";
			$sql .= "       ,AWARD_DESCRIPTION = '{$this->sAwardDescription}'";
			$sql .= "       ,AWARD_DATE = '{$this->dtAwardDate}'";
			$sql .= "       ,AWARD_URL = '{$this->sAwardURL}'";
			if (empty($this->nArtistID))
			{ 
				$sql .= "       ,ARTIST_ID = NULL";
			}
			else
			{
				$sql .= "       ,ARTIST_ID = {$this->nArtistID}";
			}
			if (empty($this->nCDID)) 
			{ 
				$sql .= "       ,CD_ID = NULL";
			}
			else
			{
				$sql .= "       ,CD_ID = {$this->nCDID}";
			}				
			if (empty($this->nSongID)) 
			{ 
				$sql .= "       ,SONG_ID = NULL";
			}
			else
			{
				$sql .= "       ,SONG_ID = {$this->nSongID}";
			}
		
			if($this->bPerformanceRelated)
			{
				$sql .= ", PERFORMANCE_RELATED = TRUE ";
			}
			else
			{
				$sql .= ", PERFORMANCE_RELATED = FALSE ";				
			}			
			$sql .= "       ,AWARD_IMAGE = '{$this->sAwardImage}'";
			$sql .= "       ,LAST_UPDATE = SYSDATE()";
			$sql .= "  WHERE AWARD_ID  = {$this->nAwardID}";
		
//DEBUG
//echo "SQL={$sql}<BR>";

			
			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result) {	

				 $this->sErrorMessage = "AWARD006 - FAILED TO UPDATE AWARD: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}
		
		}
		else
		{
			 $this->sErrorMessage = "AWARD010 - NO AWARD_ID SPECIFIED ";
			 return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deleteAward()
	 * 
	 * This function deletes a Award record
	 ********************************************************************************
	*/
	function deleteAward()
	{
		if ($this->nAwardID > 0)
		{
			$sql =	" DELETE FROM AWARD";
			$sql .= " WHERE AWARD_ID = {$this->nAwardID}";
		
//DEBUG
//echo "SQL={$sql}<BR>";

			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "AWARD007 - FAILED TO OPEN DB: ";
				return FALSE;
			}		

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result)
			{	
				 $this->sErrorMessage = "AWARD008 - FAILED TO DELETE Award: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}

		}
		else
		{
			$this->sErrorMessage = "AWARD009 - CAN NOT DELETE Award BECAUSE NO Award ID SUPPLIED. ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * loadAwardObject()
	 * This function loads a given row from the Award table into a Award object.  
	 ********************************************************************************
	*/
	function loadAwardObject(&$oAward, &$row)
	{

			$oAward->nAwardID = $row['AWARD_ID'];			
			$oAward->sAwardName = $row['AWARD_NAME'];
			$oAward->sAwardDescription = $row['AWARD_DESCRIPTION'];
			$oAward->dtAwardDate = $row['AWARD_DATE'];
			$oAward->sAwardURL = $row['AWARD_URL'];
			$oAward->nArtistID = $row['ARTIST_ID'];
			$oAward->nCDID = $row['CD_ID'];
			$oAward->nSongID = $row['SONG_ID'];
			$oAward->bPerformanceRelated = $row['PERFORMANCE_RELATED'];
			$oAward->sAwardImage = $row['AWARD_IMAGE'];
			$oAward->dtLastUpdate= $row['LAST_UPDATE'];			

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
	
		$this->sAwardName = mysqli_real_escape_string($this->DB->dbConnection,$this->sAwardName);	
		$this->sAwardDescription = mysqli_real_escape_string($this->DB->dbConnection,$this->sAwardDescription);	

	}


}
	
?>