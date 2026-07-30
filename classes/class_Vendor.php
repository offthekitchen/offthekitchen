<?php
/*
*******************************************************************
class_Vendor.php
This PHP file defines the Vendor object class
NOTES
Date        Change
-------------------------------------------------------------
2015-12-15	Refactored
2015-12-19	removed unneeded includes
*******************************************************************
*/	
//include Expense Class		
include_once (CLASS_DIR . "/class_Expense.php");

//include Payment Class		
include_once (CLASS_DIR . "/class_Payment.php");

//include DB Class		
include_once (CLASS_DIR . "/class_DB.php");


class Vendor
{

	var $DB;

	//VENDOR Table Values
	var $nVendorID = 0;
	var $sVendorName = NULL;
	var $dtLastUpdate = NULL;
	
	//Booleans used in Searching
	var $bFuzzyNameSearch = FALSE;

	var $sErrorMessage = "";

	/*****************************
	 * Arrays of Related Objects *
	 *****************************/
	//Vendor Records matching search criteria
	var $aVendorRecords = array();
	//Vendor Errors
	var $aVendorErrors = array();

	//Constructor
   function __construct() 
   {
		$this->DB = new Database();
   }
  

	/*
	 ********************************************************************************
	 * getVendor()
	 * 
	 * This function retrieves Vendor data based on data in the properites and
	 * returns an array of Vendor Objects
	 ********************************************************************************
	*/
	function getVendor()
	{

		$this->aVendorRecords = array();
		
		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the vendor type)
		$sql =	"SELECT VENDOR.* ";
		$sql .= "  FROM VENDOR ";
		$sql .=	" WHERE 1=1";
		   
		$this->buildWhereClause($sql);

//DEBUG
//echo "SQL=". $sql . "<BR>";
							
		$result = NULL;
		
		if(!$this->executeSQL($sql,$result))
		{
			return -1;
		}
		else
		{

			$iVendorCount = 0;

			while($row = mysqli_fetch_array($result))
			{

				$oNextVendor = new Vendor();
				
				//Load each product into a Vendor Object
				$this->loadVendorObject($oNextVendor, $row);

				//Then add the object to the array of found products
				$this->aVendorRecords[$iVendorCount] = $oNextVendor;
				
				$iVendorCount++;

			}
		
			$this->DB->closeDB();
			return TRUE;

		}
	}	
	
	/*
	 ********************************************************************************
	 * buildWhereClause()
	 * 
	 * This function builds a where clause for retrieving product records 
	 ********************************************************************************
	*/
	function buildWhereClause(&$sql)
	{
		//If a specific Vendor ID is being searched, match on the ID
		if ($this->nVendorID > 0)
		{
			$sql .= " AND VENDOR.VENDOR_ID = {$this->nVendorID}";
		}
		else
		{
		//Otherwise build an SQL statement based on the values in the properties
			
			// ****************
			// * VENDOR NAME *
			// ****************
			if (!empty($this->sVendorName))
			{
				//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
				if ($this->bFuzzyNameSearch)
				{
					$sql .= " AND VENDOR_NAME LIKE '%{$this->sVendorName}%'";
				}
				else
				{
					$sql .= " AND VENDOR_NAME = '{$this->sVendorName}'";
				}
			}
			
			// ************
			// * ORDER BY *
			// ************
			//ALPHABETICAL BY NAME
			$sql .= " ORDER BY VENDOR.VENDOR_NAME";	

		}
//DEBUG
//echo "SQL={$sql}<BR>";
							
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
			$this->sErrorMessage = "VND015 - FAILED TO OPEN DB: " . $this->DB->dbError;
			return false;
		}

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);

		if (!$result) 
		{

			 $this->sErrorMessage = "VND016 - " . mysqli_error($this->DB->dbConnection);
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
	 * Insert Vendor()
	 * 
	 * This function inserts a VENDOR record 
	 ********************************************************************************
	*/
	function insertVendor()
	{
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "VND003 - FAILED TO OPEN DB: ";
			return FALSE;
		}
			
		$this->escapeSpecialChars();

		$sql =	" INSERT INTO VENDOR";
		$sql .= " (VENDOR_NAME,";		
		$sql .= "  LAST_UPDATE)";		

		$sql .= " VALUES (";
		$sql .= "'{$this->sVendorName}',";	
		$sql .= "SYSDATE()";	
		$sql .= ")";	
		
//DEBUG
//echo "SQL={$sql}<BR>";

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);
	
		//SQL Error
		if (!$result) 
		{

			 $this->sErrorMessage = "VND004 - FAILED TO INSERT VENDOR RECORD - " . mysqli_error($this->DB->dbConnection);
			 return FALSE;
		}
		else 
		{
			$this->nVendorID = mysqli_insert_id($this->DB->dbConnection);
			return TRUE;	
		}

	}	


	/*
	 ********************************************************************************
	 * updateVendor()
	 * 
	 * This Updates a VENDOR record
	 ********************************************************************************
	*/
	function updateVendor()
	{
	
		if ($this->nVendorID > 0)
		{
			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "VND005 - FAILED TO OPEN DB: ";
				return FALSE;
			}
			
			$this->escapeSpecialChars();
	
			$sql =	" UPDATE VENDOR ";
			$sql .= " SET VENDOR_NAME = '{$this->sVendorName}'";
			$sql .= ", LAST_UPDATE = SYSDATE()";
			$sql .= " WHERE VENDOR_ID  = {$this->nVendorID}";
		
//DEBUG
//echo "SQL={$sql}<BR>";

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result) {	

				 $this->sErrorMessage = "VND006 - FAILED TO UPDATE VENDOR: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}
		
		}
		else
		{
			 $this->sErrorMessage = "VND010 - NO VENDOR_ID SPECIFIED ";
			 return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deleteVendor()
	 * 
	 * This function deletes a Vendor record
	 ********************************************************************************
	*/
	function deleteVendor()
	{
		if ($this->nVendorID > 0)
		{
			//Look for Associated expenses
			$oVendorExpenses = new Expense();
			$oVendorExpenses->nVendorID = $this->nVendorID;
			$nNumberOfVendorExpenses = $oVendorExpenses->getNumberOfExpenses();		

			if ($nNumberOfVendorExpenses == -1)
			{
				$this->sErrorMessage = "VND011 - Failed to Retrieve Expenses for Vendor: {$oVendorExpenses->sErrorMessage}";
				return FALSE;
			}
			elseif ($nNumberOfVendorExpenses > 0)
			{
				$this->sErrorMessage = "VND012 - Can not delete Vendor because it has ";
				$this->sErrorMessage .= "<A HREF='" . ADMIN_DIR . "/ExpenseMaintenance.php?VENDOR_ID={$this->nVendorID}'>";		
				$this->sErrorMessage .= "{$nNumberOfVendorExpenses} expenses.</A>";
				return FALSE;
			}

			//Look for associated payments
			$oVendorPayments = new Payment();
			$oVendorPayments->nVendorID = $this->nVendorID;
			$nNumberOfVendorPayments = $oVendorPayments->getNumberOfPayments();		

			if ($nNumberOfVendorPayments == -1)
			{
				$this->sErrorMessage = "VND013 - Failed to Retrieve Expenses for Vendor: {$oVendorExpenses->sErrorMessage}";
				return FALSE;
			}
			elseif ($nNumberOfVendorPayments > 0)
			{
				$this->sErrorMessage = "VND014 - Can not delete Vendor because it has ";
				$this->sErrorMessage .= "<A HREF='" . ADMIN_DIR . "/PaymentMaintenance.php?VENDOR_ID={$this->nVendorID}'>";		
				$this->sErrorMessage .= $nNumberOfVendorPayments . " revenues.</A>";
				return FALSE;
			}
	
			$sql =	" DELETE FROM VENDOR";
			$sql .= " WHERE VENDOR_ID = {$this->nVendorID}";
		
//DEBUG
//echo "SQL={$sql}<BR>";

			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "VND007 - FAILED TO OPEN DB: ";
				return FALSE;
			}		

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result)
			{	
				 $this->sErrorMessage = "VND008 - FAILED TO DELETE VENDOR: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}

		}
		else
		{
			$this->sErrorMessage = "VND009 - CAN NOT DELETE VENDOR BECAUSE NO VENDOR ID SUPPLIED. ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * loadVendorObject()
	 * This function loads a given row from the VENDOR table into a Vendor object.  
	 ********************************************************************************
	*/
	function loadVendorObject(&$oVendor, &$row)
	{

			$oVendor->nVendorID = $row['VENDOR_ID'];			
			$oVendor->sVendorName = $row['VENDOR_NAME'];
			$oVendor->dtLastUpdate= $row['LAST_UPDATE'];			

	}

	/*
	 ********************************************************************************
	 * getVendorErrors()
	 * 
	 * This function creates an array of Vendors that have errors 
	 ********************************************************************************
	*/
	function getVendorErrors()
	{
		return TRUE;		
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
		$this->sVendorName = mysqli_real_escape_string($this->DB->dbConnection,$this->sVendorName);	

	}

	
}
	
?>