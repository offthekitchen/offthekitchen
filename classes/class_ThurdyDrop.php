<?php

//This include defines the relative path to the root directory from this sub-directory
include_once ("root.inc.php");

//include DB Class		
include_once (CLASS_DIR . "/class_DB.php");
//include Error Class
include_once (CLASS_DIR . "/class_Error.php");


class ThurdyDrop
{

	var $DB;

	//thurdy_drop Table Values
	var $nDropID = NULL;
	var $sDropLocation = NULL;
	var $sDropDesc = NULL;
	var $sDropImage = NULL;
	var $dtDropDate = NULL;
	var $dtLastUpdate = NULL;
	var $sOrderBy = NULL;
		
	//Booleans used in Searching
	var $bFuzzyNameSearch = FALSE;
	

	var $sErrorMessage = "";

	/*****************************
	 * Arrays of Related Objects *
	 *****************************/
	//Drop Records matching search criteria
	var $aDropRecords = array();

	//Constructor
   function __construct() 
   {
		$this->DB = new Database();
   }
  

	/*
	 ********************************************************************************
	 * getThurdyDrop()
	 * 
	 * This function retrieves Drop data based on data in the properites and
	 * returns an array of ThrdyDrop Objects
	 ********************************************************************************
	*/
	function getThurdyDrop()
	{

	
		$this->aDropRecords = array();
		
		//Begin the SQL SELECT STATEMENT 
		$sql =	"SELECT * ";
		$sql .= "  FROM thurdy_drop ";
		$sql .=	" WHERE 1=1 ";

		$this->buildWhereClause($sql);   

//DEBUG
//echo "SQL=". $sql . "<BR>";

		if(!$this->executeSQL($sql,$result))
		{
			return -1;
		}
		else
		{
			$iDropCount = 0;

			while($row = mysqli_fetch_array($result))
			{

				$oNextDrop = new ThurdyDrop();
				
				//Load each product into a Drop Object
				$this->loadDropObject($oNextDrop, $row);
				
				//Then add the object to the array of found products
				$this->aDropRecords[$iDropCount] = $oNextDrop;
				
				$iDropCount++;

			}
		
			$this->DB->closeDB();
			return TRUE;

		}
		   
	}
	
	/*
	 ********************************************************************************
	 * buildWhereClause()
	 * 
	 * This function builds a where clause for retrieving Drop records  
	 ********************************************************************************
	*/
	function buildWhereClause(&$sql)
	{

		//If a specific ID is being searched, match on the ID
		if ($this->nDropID > 0)
		{
			$sql .= " AND DROP_ID = " . $this->nDropID;
		}
		else
		{
		//Otherwise build an SQL statement based on the values in the properties
			
			// *****************
			// * DROP_LOCATION *
			// *****************
			if (!empty($this->sDropLocation))
			{
				//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
				if ($this->bFuzzyNameSearch)
				{
					$sql .= " AND DROP_LOCATION LIKE '%" . $this->sDropLocation . "%'";
				}
				else
				{
					$sql .= " AND DROP_LOCATION = '" . $this->sDropLocation . "'";
				}
			}			
			
			// *************
			// * DROP_DESC *
			// *************
			if (!empty($this->sDropDesc))
			{
				$sql .= " AND DROP_DESC = " . $this->sDropDesc;
			}	
							

			// **************
			// * DROP_DATE *
			// **************
			if (!empty($this->dtDropDate))
			{
				$sql .= " AND DROP_DATE = " . $this->dtDropDate;
			}	
							
			// **************
			// * DROP_IMAGE *
			// **************
			if (!empty($this->sDropImage))
			{
				$sql .= " AND DROP_IMAGE = " . $this->sDropImage;
			}	
										
			// ************
			// * ORDER BY *
			// ************
			//ORDER BY
			switch($this->sOrderBy)
			{
				case DATE_ORDER:
				$sql .= " ORDER BY DROP_DATE";	
				break;
				
				default:
				$sql .= " ORDER BY DROP_ID";
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
	function executeSQL(&$sql, &$result)
	{
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "THURDYDROP011 - FAILED TO OPEN DB: " . $this->DB->dbError;
			return false;
		}

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);

		if (!$result) 
		{

			 $this->sErrorMessage = "THURDYDROP012 - " . mysqli_error($this->DB->dbConnection);
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
	 * insertDrop
	 * 
	 * This function inserts a THURDY_DROP record 
	 ********************************************************************************
	*/
	function insertDrop()
	{
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "THURDYDROP003 - FAILED TO OPEN DB: ";
			return FALSE;
		}

		$this->escapeSpecialChars();

		$sql =	" INSERT INTO thurdy_drop";
		$sql .= " (DROP_LOCATION,";		
		$sql .= "  DROP_DESC,";		
		$sql .= "  DROP_DATE,";		
		$sql .= "  DROP_IMAGE,";		
		$sql .= "  LAST_UPDATE)";		

		$sql .= " VALUES (";
		$sql .= "'" . $this->sDropLocation . "',";	
		$sql .= "'" . $this->sDropDesc . "',";	
		$sql .= "'" . $this->dtDropDate . "',";	
		$sql .= "'" . $this->sDropImage . "',";	
		$sql .= "SYSDATE()";	
		$sql .= ")";	
		
//DEBUG
//echo "SQL=". $sql . "<BR>";

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);

	
		//SQL Error
		if (!$result) 
		{

			 $this->sErrorMessage = "THURDYDROP004 - FAILED TO INSERT DROP RECORD - " . mysqli_error($this->DB->dbConnection);
			 return FALSE;
		}
		else 
		{
			$this->nDropID = mysqli_insert_id($this->DB->dbConnection);
			return TRUE;	
		}
	}	


	/*
	 ********************************************************************************
	 * updateDrop()
	 * 
	 * This Updates a DROP record
	 ********************************************************************************
	*/
	function updateDrop()
	{	
		if ($this->nDropID > 0)
		{
			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "THURDYDROP005 - FAILED TO OPEN DB: ";
				return FALSE;
			}
			$this->escapeSpecialChars();

			$sql =	" UPDATE thurdy_drop ";
			$sql .= " SET DROP_LOCATION = '" . $this->sDropLocation . "'";
			$sql .= ", DROP_DESC = '" . $this->sDropDesc . "'";
			$sql .= ", DROP_DATE = '" . $this->dtDropDate . "'";
			$sql .= ", DROP_IMAGE = '" . $this->sDropImage . "'";
			$sql .= ", LAST_UPDATE = SYSDATE()";
			$sql .= " WHERE DROP_ID  = " . $this->nDropID;
		
//DEBUG
//echo "SQL=". $sql . "<BR>";

			
			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result) {	

				 $this->sErrorMessage = "THURDYDROP006 - FAILED TO UPDATE DROP: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}
		
		}
		else
		{
			 $this->sErrorMessage = "THURDYDROP010 - NO DROP_ID SPECIFIED ";
			 return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deleteDrop()
	 * 
	 * This function deletes a Drop record
	 ********************************************************************************
	*/
	function deleteDrop()
	{
		if ($this->nDropID > 0)
		{
	
			$sql =	" DELETE FROM thurdy_drop";
			$sql .= " WHERE DROP_ID = " . $this->nDropID;
		
//DEBUG
//echo "SQL=". $sql . "<BR>";

			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "THURDYDROP007 - FAILED TO OPEN DB: ";
				return FALSE;
			}		

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result)
			{	
				 $this->sErrorMessage = "THURDYDROP008 - FAILED TO DELETE DROP: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}

		}
		else
		{
			$this->sErrorMessage = "THURDYDROP009 - CAN NOT DELETE DROP BECAUSE NO DROP ID SUPPLIED. ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * loadDropObject()
	 * This function loads a given row from the DROP table into a Drop object.  
	 ********************************************************************************
	*/
	function loadDropObject(&$oDrop, &$row)
	{

			$oDrop->nDropID = $row['DROP_ID'];			
			$oDrop->sDropLocation = $row['DROP_LOCATION'];
			$oDrop->sDropDesc = $row['DROP_DESC'];
			$oDrop->dtDropDate = $row['DROP_DATE'];
			$oDrop->sDropImage = $row['DROP_IMAGE'];		
			$oDrop->dtLastUpdate= $row['LAST_UPDATE'];			

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
		$this->sDropLocation = mysqli_real_escape_string($this->DB->dbConnection,$this->sDropLocation);	
		$this->sDropDesc = mysqli_real_escape_string($this->DB->dbConnection,$this->sDropDesc);	
		$this->sDropImage = mysqli_real_escape_string($this->DB->dbConnection,$this->sDropImage);	
	}
	
}
	
?>