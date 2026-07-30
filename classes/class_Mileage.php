<?php
/*
*******************************************************************
class_Mileage.php
This PHP file defines Mileage object class
NOTES
Date        Change
-------------------------------------------------------------
2015-12-07	Created
2015-12-19	Refactored and removed unneeded includes
*******************************************************************
*/	

//include DB Class		
include_once (CLASS_DIR . "/class_DB.php");

class Mileage
{

	var $DB;

	//Mileage Table Values
	var $nMileageID = 0;
	var $nMileage = 0;
	var $sReason = NULL;
	var $dtMileageDate = NULL;
	var $nMileageYear = NULL;
	var $dtLastUpdate = NULL;
	var $sOrderby = NULL;
	
	//Booleans used in Searching
	var $bFuzzyNameSearch = FALSE;
		
	var $sErrorMessage = "";

	/*****************************
	 * Arrays of Related Objects *
	 *****************************/
	//Mileage Records matching search criteria
	var $aMileageRecords = array();
	//Mileage Errors
	var $aMileageErrors = array();

	//Constructor
	function __construct() 
   	{
		$this->DB = new Database();
	}

	/*
	 ********************************************************************************
	 * getMileage()
	 * 
	 * This function retrieves Mileage data based on data in the properites and
	 * returns an array of Mileage Objects
	 ********************************************************************************
	*/
	function getMileage()
	{
	
		$this->aMileageRecords = array();
		
		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the Mileage)
		$sql =	"SELECT * ";
		$sql .= "  FROM MILEAGE ";
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

			$iMileageCount = 0;

			while($row = mysqli_fetch_array($result))
			{

				$oNextMileage = new Mileage();
				
				//Load each product into a Mileage Object
				$this->loadMileageObject($oNextMileage, $row);

				//Then add the object to the array of found products
				$this->aMileageRecords[$iMileageCount] = $oNextMileage;
				
				$iMileageCount++;

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
		//If a specific Mileage ID is being searched, match on the ID
		if ($this->nMileageID > 0)
		{
			$sql .= " AND MILEAGE_ID = {$this->nMileageID}";
		}
		else
		{
		//Otherwise build an SQL statement based on the values in the properties
			
			// ***********
			// * Mileage *
			// ***********
			if (!empty($this->nMileage))
			{
				//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
				if ($this->bFuzzyNameSearch)
				{
					$sql .= " AND MILEAGE LIKE '%{$this->nMileage}%'";
				}
				else
				{
					$sql .= " AND MILEAGE = '{$this->nMileage}'";
				}
			}
			
			// ************
			// * REASON *
			// ************
			if (!empty($this->sReason))
			{
				$sql .= " AND REASON = '{$this->sReason}'";
			}

			// ****************
			// * MILEAGE DATE *
			// ****************
			if (!empty($this->dtMileageDate))
			{
				$sql .= " AND MILEAGE_DATE LIKE '%{$this->dtMileageDate}%'";
			}

			// ****************
			// * MILEAGE YEAR *
			// ****************
			if (!empty($this->nMileageYear))
			{
				$sql .= " AND YEAR(MILEAGE_DATE) = {$this->nMileageYear}";
			}

			// ************
			// * ORDER BY *
			// ************
			//ORDER BY
				$sql .= " ORDER BY MILEAGE_DATE";

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
			$this->sErrorMessage = "MIL001 - FAILED TO OPEN DB: {$this->DB->dbError}";
			return false;
		}

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);

		if (!$result) 
		{

			 $this->sErrorMessage = "MIL002 - " . mysqli_error($this->DB->dbConnection);
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
	 * Insert Mileage()
	 * 
	 * This function inserts a Mileage record 
	 ********************************************************************************
	*/
	function insertMileage()
	{
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "MIL003 - FAILED TO OPEN DB: ";
			return FALSE;
		}
			
		$this->escapeSpecialChars();
		
		$sql =	" INSERT INTO MILEAGE";
		$sql .= " (MILEAGE,";		
		$sql .= "  REASON,";
		$sql .= "  MILEAGE_DATE,";
		$sql .= "  LAST_UPDATE)";		

		$sql .= " VALUES (";
		$sql .= "'{$this->nMileage}',";	
		$sql .= "'{$this->sReason}',";	
		$sql .= "'{$this->dtMileageDate}',";	
		$sql .= "SYSDATE()";	
		$sql .= ")";	
		
//DEBUG
//echo "SQL={$sql}<BR>";

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);
	
		//SQL Error
		if (!$result) 
		{

			 $this->sErrorMessage = "MIL004 - FAILED TO INSERT Mileage RECORD - " . mysqli_error($this->DB->dbConnection);
			 return FALSE;
		}
		else 
		{
			$this->nMileageID = mysqli_insert_id($this->DB->dbConnection);
			return TRUE;	
		}
		

	}	


	/*
	 ********************************************************************************
	 * updateMileage()
	 * 
	 * This Updates a Mileage record
	 ********************************************************************************
	*/
	function updateMileage()
	{
	
		if ($this->nMileageID > 0)
		{
	
			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "MIL005 - FAILED TO OPEN DB: ";
				return FALSE;
			}

			$this->escapeSpecialChars();			

			$sql =	" UPDATE MILEAGE ";
			$sql .= "    SET MILEAGE = '{$this->nMileage}'";
			$sql .= "       ,REASON = '{$this->sReason}'";
			$sql .= "       ,MILEAGE_DATE = '{$this->dtMileageDate}'";
			$sql .= "       ,LAST_UPDATE = SYSDATE()";
			$sql .= "  WHERE MILEAGE_ID  = {$this->nMileageID}";
		
//DEBUG
//echo "SQL={$sql}<BR>";

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result) {	

				 $this->sErrorMessage = "MIL006 - FAILED TO UPDATE Mileage: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}
		
		}
		else
		{
			 $this->sErrorMessage = "MIL010 - NO MILEAGE_ID SPECIFIED ";
			 return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deleteMileage()
	 * 
	 * This function deletes a Mileage record
	 ********************************************************************************
	*/
	function deleteMileage()
	{
		if ($this->nMileageID > 0)
		{

			$sql =	" DELETE FROM Mileage";
			$sql .= " WHERE MILEAGE_ID = {$this->nMileageID}";
		
//DEBUG
//echo "SQL={$sql}<BR>";

			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "MIL007 - FAILED TO OPEN DB: ";
				return FALSE;
			}		

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result)
			{	
				 $this->sErrorMessage = "MIL008 - FAILED TO DELETE Mileage: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}

		}
		else
		{
			$this->sErrorMessage = "MIL009 - CAN NOT DELETE Mileage BECAUSE NO Mileage ID SUPPLIED. ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * loadMileageObject()
	 * This function loads a given row from the Mileage table into a Mileage object.  
	 ********************************************************************************
	*/
	function loadMileageObject(&$oMileage, &$row)
	{

			$oMileage->nMileageID = $row['MILEAGE_ID'];			
			$oMileage->nMileage = $row['MILEAGE'];
			$oMileage->sReason = $row['REASON'];
			$oMileage->dtMileageDate = $row['MILEAGE_DATE'];
			$oMileage->dtLastUpdate= $row['LAST_UPDATE'];			

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
		$this->nMileage = mysqli_real_escape_string($this->DB->dbConnection,$this->nMileage);	

	}

}
	
?>