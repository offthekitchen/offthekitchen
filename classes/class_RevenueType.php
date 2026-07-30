<?php
/*
*******************************************************************
class_Revenue_Type.php
This PHP file defines the Revenue Type object class
NOTES
Date        Change
-------------------------------------------------------------
2015-12-19	removed unneeded includes
*******************************************************************
*/	

//include DB Class		
include_once (CLASS_DIR . "/class_DB.php");

class RevenueType
{

	var $DB;
	
	//Revenue Type Values
	var $nRevenueTypeID = 0;
	var $sRevenueTypeName = NULL;
	var $dtLastUpdate = NULL;

	var $bFuzzyNameSearch = FALSE;

	/*****************
	 * Object Arrays *
	 *****************/
	//Revenue Types
	var $aRevenueTypeRecords = array();
	//Revenue assigned to this Revenue Type
	var $aRevenueTypeRevenue = array();
	//Revenue Type Errors
	var $aRevenueTypeErrors = array();
	
		
	//Error variables
	var $sErrorMessage = "";

	//Constructor
   function __construct() 
   {
		$this->DB = new Database();
   }

	/*
	 ********************************************************************************
	 * getRevenueType()
	 * 
	 * This function retrieves Revenue Type data based on data in the properites and
	 *  returns an array of Revenue Type Objects
	 ********************************************************************************
	*/
	function getRevenueType()
	{
	
		$this->aRevenueTypeRecords = array();
		
		//Begin the SQL SELECT STATEMENT
		$sql =	"SELECT  REVENUE_TYPE.* ";
		$sql .=	" FROM  REVENUE_TYPE ";
		$sql .=	" WHERE 1=1 ";
	
	
		//Revenue Type ID
		if ($this->nRevenueTypeID > 0)
		{
			$sql .= " AND REVENUE_TYPE.REVENUE_TYPE_ID = {$this->nRevenueTypeID}";
		}
		else
		{
		
			if (!empty($this->sRevenueTypeName))
			{
				if ($this->bFuzzyNameSearch)
				{
					$sql .= " AND REVENUE_TYPE.REVENUE_TYPE_NAME LIKE '%{$this->sRevenueTypeName}%'";
				}
				else
				{
					$sql .= " AND REVENUE_TYPE.REVENUE_TYPE_NAME = '{$this->sRevenueTypeName}'";
				}
			}
			$sql .= " ORDER BY REVENUE_TYPE.REVENUE_TYPE_NAME";

		}		

//DEBUG
//echo "SQL={$sql}<BR>";

		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "RVT001 - FAILED TO OPEN DB: {$this->DB->dbError}";
			return FALSE;
		}

	
		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);
	
		//SQL Error
		if (!$result) {

			 $this->sErrorMessage = "RVT002 - " . mysqli_error($this->DB->dbConnection);
			 $this->DB->closeDB();
			 return FALSE;
		}
		else 
		{
			$iRevenueTypeCount = 0;

			//If multiple rows are returned, then load them into the array of search results
			while($row = mysqli_fetch_array($result))
			{
				$oNextRevenueType = new RevenueType();
				
				//Load each Revenue Type into an Object
				$this->loadRevenueTypeObject($oNextRevenueType, $row);
										
				$this->aRevenueTypeRecords[$iRevenueTypeCount] = $oNextRevenueType;

				$iRevenueTypeCount++;
				
				
			}
			return TRUE;
		}
	}


	/*
	 ********************************************************************************
	 * getRevenueTypeRevenue()
	 * 
	 * This function loads the array of Revenue objects 
	 ********************************************************************************
	*/
	function getRevenueTypeRevenue()
	{
		$oRevenueTypeRevenue = new Revenue();	
		$oRevenueTypeRevenue->nRevenueTypeID = $this->nRevenueTypeID;	
		
		if($oRevenueTypeRevenue->getRevenue())
		{
			$this->aRevenueTypeRevenue = $oRevenueTypeRevenue->aRevenueRecords;
		}
		else
		{
			$this->sErrorMessage = "RVT003 - {$oRevenueTypeRevenue->sErrorMessage}";
			return FALSE;
		}
		
		return TRUE;

	}		
	
	/*
	 ********************************************************************************
	 * insertRevenueType()
	 * 
	 * This function inserts a Revenue Type record on the DB
	 ********************************************************************************
	*/
	function insertRevenueType()
	{
	
		$sql =	" INSERT INTO REVENUE_TYPE";
		$sql .= " (REVENUE_TYPE_NAME, LAST_UPDATE)";
		$sql .= " VALUES (";
		$sql .= "'{$this->sRevenueTypeName}',";	
		$sql .= " NOW()";
		$sql .= ")";	
		
//DEBUG
//echo "SQL={$sql}<BR>";

		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "RVT004 - FAILED TO OPEN DB: ";
			return FALSE;
		}
			
	
		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);
	
		//SQL Error
		if (!$result) {

			 $this->sErrorMessage = "RVT005 - " . mysqli_error($this->DB->dbConnection);
			 return FALSE;
		}
		else 
		{
			$this->nRevenueTypeID = mysqli_insert_id($this->DB->dbConnection);
 
			return TRUE;	
		}
		
		if (!$this->DB->closeDB())
		{
			$this->sErrorMessage = "RVT006 -  FAILED TO CLOSE DB: ";
			return FALSE;
		}
	
	}

	/*
	 ********************************************************************************
	 * updateRevenueType()
	 * 
	 * This function inserts a Revenue Type record on the DB
	 ********************************************************************************
	*/
	function updateRevenueType()
	{

		if($this->nRevenueTypeID > 0)
		{	
			$sql =	" UPDATE REVENUE_TYPE ";
			$sql .= " SET REVENUE_TYPE_NAME = '{$this->sRevenueTypeName}'";
			$sql .= ", LAST_UPDATE = NOW()";
			$sql .= " WHERE REVENUE_TYPE_ID = {$this->nRevenueTypeID}";
			
//DEBUG
//echo "SQL={$sql}"<BR>";

			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "RVT007 - FAILED TO OPEN DB: ";
				return FALSE;
			}
			
			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result) {	

				 $this->sErrorMessage = "RVT008 - " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{	

				return TRUE;	
			}
			
			if (!$this->DB->closeDB())
			{
				$this->sErrorMessage = "RVT009 - FAILED TO CLOSE DB ";
				return FALSE;
			}
		}
		else
		{
				$this->sErrorMessage = "RVT010 - NO REVENUE_TYPE_ID SPECIFIED ";
				return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deleteRevenueType()
	 * 
	 * This function deletes a Revenue Type record from the DB
	 ********************************************************************************
	*/
	function deleteRevenueType()
	{

		$this->aRevenueTypeRevenue = array();
/*
		//Look for Assocciated Revenue
		if ($this->getRevenueTypeRevenue())
		{
			if (sizeof($this->aRevenueTypeRevenue) > 0)
			{
				$this->sErrorMessage = "RVT011 - Can not delete Revenue Type because it has " . sizeof($this->aRevenueTypeRevenue) . " Revenue assigned";
				return FALSE;
			}

		}
		else
		{
			$this->sErrorMessage = "RVT012 - Failed to Retrieve Revenue for Revenue Type";
			return FALSE;
		}
*/			
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "PRT13 - FAILED TO OPEN DB: " . mysqli_error($this->DB->dbConnection);
			return FALSE;
		}
		
	
		$sql =	" DELETE FROM REVENUE_TYPE ";
		$sql .= " WHERE REVENUE_TYPE_ID = {$this->nRevenueTypeID}";
		
//DEBUG
//echo "SQL={$sql}<BR>";

	
		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);
	
		//SQL Error
		if (!$result) {

			 $this->sErrorMessage = "RVT014 - Failed to Delete Revenue Type " . mysqli_error($this->DB->dbConnection);
			 return FALSE;
		}
		else 
		{
			return TRUE;	
		}
		
		if (!$this->DB->closeDB())
		{
			$this->sErrorMessage = "RVT015 -  FAILED TO CLOSE DB: ";
			return FALSE;
		}
	
	}

	/*
	 ********************************************************************************
	 * loadRevenueTypeObject()
	 * This function loads a given row from the REVENUE_TYPE table into a Revenue object.  
	 ********************************************************************************
	*/
	function loadRevenueTypeObject(&$oRevenueType, &$row)
	{
		$oRevenueType->nRevenueTypeID = $row['REVENUE_TYPE_ID'];			
		$oRevenueType->sRevenueTypeName = $row['REVENUE_TYPE_NAME'];	
		$oRevenueType->dtLastUpdate = $row['LAST_UPDATE'];			
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
		$this->sRevenueTypeName = mysqli_real_escape_string($this->DB->dbConnection,$this->sRevenueTypeName);	

	}

}
	
?>