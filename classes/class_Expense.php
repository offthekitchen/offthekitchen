<?php
/*
*******************************************************************
class_Expense.php
This PHP file defines Expense object class
NOTES
Date        Change
-------------------------------------------------------------
2015-05-06	Refactored
2015-10-22	Changed BuildWhereClause() to ignore category ID 0 
2015-12-19	Removed unneeded includes
2024-04-12	Added Artist ID
*******************************************************************
*/	

//include DB Class		
include_once (CLASS_DIR . "/class_DB.php");

//include Error Class
include_once (CLASS_DIR . "/class_Error.php");

class Expense
{

	var $DB;

	//EXPENSE Table Values
	var $nExpenseID = 0;
	var $dtExpenseDate = NULL;
	var $nExpenseAmount = 0.00;
	var $sExpenseDescription = NULL;
	var $nTourID = NULL;
	var $nProductID = NULL;
	var $nVendorID = NULL;
	var $dtLastUpdate = NULL;
	var $nExpenseYear = 0;

	//Variable used only for searching
	var $nExcludeTourID = NULL;
	var $dtStartDate = NULL;
	var $dtEndDate = NULL;
	var $nCategoryID = NULL;
	var $nArtistID = NULL;
	var $nTaxCategoryID = NULL;
	
	//Booleans used in Searching
	var $bPerformanceRelated = FALSE;
	var $bFuzzyNameSearch = FALSE;	var $bNoProduct = FALSE;	var $bMusicRelatedOnly = FALSE;

	var $sErrorMessage = "";

	//Expense Type Object for the Expense Type of this Expense
	var $thisExpense;
	
	/*****************************
	 * Arrays of Related Objects *
	 *****************************/
	//Expense Records matching search criteria
	var $aExpenseRecords = array();
	//Categories IDs
	var $aExpenseCategoryIDs = array();
	//Expense Errors
	var $aExpenseErrors = array();

	//Constructor
   function __construct() 
   {
		$this->DB = new Database();
   }
  

	/*
	 ********************************************************************************
	 * getExpense()
	 * 
	 * This function retrieves Expense data based on data in the properites and
	 * returns an array of Expense Objects
	 ********************************************************************************
	*/
	function getExpense()
	{
		$this->aExpenseRecords = array();
		
		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the product type)
		$sql =	"SELECT EXPENSE.* ";
		$sql .= "  FROM EXPENSE ";
		$sql .=	" WHERE 1=1 ";
		 
		$this->buildWhereClause($sql);   

// DEBUG
 echo "SQL=". $sql . "<BR>";
							
		if(!$this->executeSQL($sql,$result))
		{
			return -1;
		}
		else
		{
			$iExpenseCount = 0;

			while($row = mysqli_fetch_array($result))
			{

				$oNextExpense = new Expense();
				
				//Load each product into a Expense Object
				$this->loadExpenseObject($oNextExpense, $row);
				
				//Then add the object to the array of found products
				$this->aExpenseRecords[$iExpenseCount] = $oNextExpense;
				
				$iExpenseCount++;

			}
		
			$this->DB->closeDB();
			return TRUE;

		}

	}

	/*
	 ********************************************************************************
	 * getNumberOfExpenses()
	 * 
	 * This function retrieves the number of expenses matching values in the 
	 * properites.  It returns the number of matching expense records or a -1
	 * if there is a failure
	 ********************************************************************************
	*/
	function getNumberOfExpenses()
	{

		$this->aExpenseRecords = array();
		
		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the product type)
		$sql =	"SELECT COUNT(*) AS EXPENSE_COUNT ";
		$sql .= "  FROM EXPENSE ";
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
			$row = mysqli_fetch_array($result);
			
			$nNumberOfExpenses = $row['EXPENSE_COUNT'];
		
			$this->DB->closeDB();
			return $nNumberOfExpenses;
	
		}

	}

	/*
	 ********************************************************************************
	 * getExpenseAmountTotal()
	 * 
	 * This function retrieves the expense amount totoal of records matching values  
	 * in the properites.  
	 ********************************************************************************
	*/
	function getExpenseAmountTotal()
	{

		$this->aExpenseRecords = array();
		
		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the product type)
		$sql =	"SELECT SUM(EXPENSE_AMOUNT) AS TOTAL_AMOUNT ";
		$sql .= "  FROM EXPENSE ";

		// **************
		// * ARTRIST ID *
		// **************
		if ($this->nArtistID)
		{
			$sql .= " JOIN PRODUCT ON (EXPENSE.PRODUCT_ID = PRODUCT.PRODUCT_ID AND PRODUCT.ARTIST_ID = {$this->nArtistID})";
		}
		$sql .=	" WHERE 1=1 ";

		$this->buildWhereClause($sql); 
//DEBUG
// echo "SQL=". $sql . "<BR>";
		
		if(!$this->executeSQL($sql,$result))
		{
			return -1;
		}
		else
		{
			$row = mysqli_fetch_array($result);
			
			$nTotalAmount = $row['TOTAL_AMOUNT'];
		
			$this->DB->closeDB();
			return $nTotalAmount;
	
		}

	}

	/*
	 ********************************************************************************
	 * buildWhereClause()
	 * 
	 * This function builds a where clause for retrieving Expense records  
	 ********************************************************************************
	*/
	function buildWhereClause(&$sql)
	{
		//If a specific Expense ID is being searched, match on the ID
		if ($this->nExpenseID > 0)
		{
			$sql .= " AND EXPENSE_ID = " . $this->nExpenseID;
		}
	
		// ********************
		// * EXPENSE_DATE *
		// ********************
		if (!empty($this->dtExpenseDate))
		{
			$sql .= " AND EXPENSE_DATE LIKE '" . $this->dtExpenseDate . "%'";
		}
		// ********************
		// * EXPENSE YEAR *
		// ********************
		if (!empty($this->nExpenseYear))
		{
			$sql .= " AND YEAR(EXPENSE_DATE) = " . $this->nExpenseYear ;
		}

		// **************
		// * START DATE *
		// **************
		if (!empty($this->dtStartDate))
		{
			$sql .= " AND EXPENSE_DATE >= '" . $this->dtStartDate . "'";
		}

		// ************
		// * END DATE *
		// ************
		if (!empty($this->dtEndDate))
		{
			$sql .= " AND EXPENSE_DATE <= '" . $this->dtEndDate . "'";
		}

		// ***********************
		// * EXPENSE DESCRIPTION *
		// ***********************
		if (!empty($this->sExpenseDescription))
		{
			//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
			if ($this->bFuzzyNameSearch)
			{
				$sql .= " AND EXPENSE_DESCRIPTION LIKE '%" . $this->sExpenseDescription . "%'";
			}
			else
			{
				$sql .= " AND EXPENSE_DESCRIPTION = '" . $this->sExpenseDescription . "'";
			}
		}
		
		// ******************
		// * EXPENSE_AMOUNT *
		// ******************
		if ($this->nExpenseAmount > 0)
		{
			$sql .= " AND EXPENSE_AMOUNT = " . $this->nExpenseAmount;
		}

		// *************
		// * TOUR_ID   *
		// *************
		if ($this->nTourID > 0)
		{
			$sql .= " AND TOUR_ID = " . $this->nTourID;
		}
		if ($this->nExcludeTourID > 0)
		{
			$sql .= " AND (ISNULL(TOUR_ID) OR TOUR_ID <> " . $this->nExcludeTourID . ")";
		}

		// ***********************
		// * PERFORMANCE RELATED *
		// ***********************
		if ($this->bPerformanceRelated)
		{
			$sql .= " AND EXISTS(SELECT * ";
			$sql .= "              FROM EXPENSE_CATEGORY_XREF ";
			$sql .= "             WHERE EXPENSE_CATEGORY_XREF.EXPENSE_ID  = EXPENSE.EXPENSE_ID ";
			$sql .= "               AND EXPENSE_CATEGORY_XREF.CATEGORY_ID = " . CATEGORY_PERFORMANCE . ")";
		}

		// ****************
		// * PRODUCT_ID   *
		// ****************
		//For some reason, a Product ID of 0 sometimes fails isset and sometimes doesn't.
		if(isset($this->nProductID) && $this->nProductID <> ''){
			if ($this->nProductID > 0)
			{
				$sql .= " AND EXPENSE.PRODUCT_ID = {$this->nProductID}";
			}
			else if ($this->nProductID == 0)
			{
				$sql .= " AND (EXPENSE.PRODUCT_ID IS NULL OR EXPENSE.PRODUCT_ID = '') ";
			}
		}
		else{
			if ($this->nProductID === 0)
			{
				$sql .= " AND (EXPENSE.PRODUCT_ID IS NULL OR EXPENSE.PRODUCT_ID = '') ";
			}
		}


		// ***************
		// * VENDOR_ID   *
		// ***************
		if ($this->nVendorID > 0)
		{
			$sql .= " AND VENDOR_ID = " . $this->nVendorID;
		}

		// *********************
		// * TAX_CATEGORY_ID   *
		// *********************
		if ($this->nTaxCategoryID > 0)
		{
			$sql .= " AND TAX_CATEGORY_ID = " . $this->nTaxCategoryID;
		}

		// ******************
		// * CATEGORY IDs   *
		// ******************
		if (sizeof($this->aExpenseCategoryIDs) > 0)
		{
			//$iCategoryCount = 0;
			foreach ($this->aExpenseCategoryIDs as $nExpenseCategoryID)
			{
				if(!empty($nExpenseCategoryID))
				{
					$sql .= " AND EXISTS(SELECT * ";
					$sql .= "              FROM EXPENSE_CATEGORY_XREF ";
					$sql .= "             WHERE EXPENSE_CATEGORY_XREF.EXPENSE_ID  = EXPENSE.EXPENSE_ID ";
					$sql .= "               AND EXPENSE_CATEGORY_XREF.CATEGORY_ID = " . $nExpenseCategoryID . ")";
				}
			}  
			
		}

		// ************
		// * ORDER BY *
		// ************
		//ALPHABETICAL BY NAME
		$sql .= " ORDER BY EXPENSE_DATE";		
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
			$this->sErrorMessage = "EXP001 - FAILED TO OPEN DB: " . $this->DB->dbError;
			return false;
		}

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);

		if (!$result) 
		{

			 $this->sErrorMessage = "EXP002 - " . mysqli_error($this->DB->dbConnection);
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
	 * insertExpense()
	 * 
	 * This function inserts an EXPENSE record 
	 ********************************************************************************
	*/
	function insertExpense()
	{
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "EXP003 - FAILED TO OPEN DB: ";
			return FALSE;
		}
		
		$this->escapeSpecialChars();
			
		$sql =	" INSERT INTO EXPENSE";
		$sql .= " (EXPENSE_DATE,";		
		$sql .= "  EXPENSE_AMOUNT,";		
		$sql .= "  EXPENSE_DESCRIPTION,";		
		$sql .= "  TOUR_ID,";		
		$sql .= "  PRODUCT_ID,";		
		$sql .= "  VENDOR_ID,";		
		$sql .= "  TAX_CATEGORY_ID,";		
		$sql .= "  LAST_UPDATE)";		

		$sql .= " VALUES (";
		$sql .= "'" . $this->dtExpenseDate . "',";	
		$sql .= 	  $this->nExpenseAmount . ",";	
		$sql .= "'" . $this->sExpenseDescription . "',";	
		if (is_numeric($this->nTourID))
		{
			$sql .=  $this->nTourID . ",";	
		}
		else
		{
			$sql .= "NULL,";	
		}

		if (is_numeric($this->nProductID))
		{
			$sql .=  $this->nProductID . ",";	
		}
		else
		{
			$sql .= "NULL,";	
		}
		if (is_numeric($this->nVendorID))
		{
			$sql .=  $this->nVendorID . ",";	
		}
		else
		{
			$sql .= "NULL,";	
		}
		if (is_numeric($this->nTaxCategoryID))
		{
			$sql .=  $this->nTaxCategoryID . ",";	
		}
		else
		{
			$sql .= "NULL,";	
		}
		$sql .= "SYSDATE()";	
		$sql .= ")";	
		
//DEBUG
//echo "SQL=". $sql . "<BR>";

	
		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);

//SQL Error
		if (!$result) 
		{

			 $this->sErrorMessage = "EXP004 - FAILED TO INSERT EXPENSE RECORD - " . mysqli_error($this->DB->dbConnection);
			 return FALSE;
		}
		else 
		{
			$this->nExpenseID = mysqli_insert_id($this->DB->dbConnection);
			return TRUE;	
		}
		

	}	

	/*
	 ********************************************************************************
	 * addExpenseCategory()
	 * 
	 * This function inserts an EXPENSE_CATEGORY_XREF record 
	 ********************************************************************************
	*/
	function addExpenseCategory($nCategoryID)
	{
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "EXP011 - FAILED TO OPEN DB: ";
			return FALSE;
		}
	
		if($this->nExpenseID > 0 && $nCategoryID > 0)
		{			
			$sql =	" INSERT INTO EXPENSE_CATEGORY_XREF";
			$sql .= " (EXPENSE_ID,";		
			$sql .= "  CATEGORY_ID)";		
			$sql .= " VALUES (";
			$sql .= $this->nExpenseID . ",";	
			$sql .= $nCategoryID ;	
			$sql .= ")";	
		
//DEBUG
//echo "SQL=". $sql . "<BR>";	

		
			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);

			if (!$result) 
			{	

				 $this->sErrorMessage = "EXP012 - FAILED TO INSERT EXPENSE_CATEGORY_XREF RECORD - " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}
		
		}
		else
		{
			$this->sErrorMessage = "EXP013 - Missing Expense ID (" . $this->nExpenseID . ") or Category ID (" . $nCategoryID . ")";
			return FALSE;
		}

	}	


	/*
	 ********************************************************************************
	 * updateExpense()
	 * 
	 * This Updates a EXPENSE record
	 ********************************************************************************
	*/
	function updateExpense()
	{
	
		if ($this->nExpenseID > 0)
		{

			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "EXP005 - FAILED TO OPEN DB: ";
				return FALSE;
			}
			
			$this->escapeSpecialChars();

			$sql =	" UPDATE EXPENSE ";
			$sql .= " SET EXPENSE_DATE =  '" . $this->dtExpenseDate . "'";
			$sql .= ", EXPENSE_AMOUNT = " . $this->nExpenseAmount;
			$sql .= ", EXPENSE_DESCRIPTION = '" . $this->sExpenseDescription . "'";
			if(is_numeric($this->nTourID))
			{
				$sql .= ", TOUR_ID = " . $this->nTourID;
			}
			else
			{
				$sql .= ", TOUR_ID = NULL ";
			}
			if(is_numeric($this->nProductID))
			{
				$sql .= ", PRODUCT_ID = " . $this->nProductID;
			}
			else
			{
				$sql .= ", PRODUCT_ID = NULL ";
			}
			if(is_numeric($this->nVendorID))
			{
				$sql .= ", VENDOR_ID = " . $this->nVendorID;
			}
			else
			{
				$sql .= ", VENDOR_ID = NULL ";
			}
			if(is_numeric($this->nTaxCategoryID))
			{
				$sql .= ", TAX_CATEGORY_ID = " . $this->nTaxCategoryID;
			}
			else
			{
				$sql .= ", TAX_CATEGORY_ID = NULL ";
			}
			$sql .= ", LAST_UPDATE = SYSDATE()";
			$sql .= " WHERE EXPENSE_ID  = " . $this->nExpenseID;
		
//DEBUG
//echo "<BR>SQL=". $sql . "<BR>";

			
			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);

//SQL Error
			if (!$result) {	

				 $this->sErrorMessage = "EXP006 - FAILED TO UPDATE EXPENSE: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}
		
		}
		else
		{
			 $this->sErrorMessage = "EXP010 - NO EXPENSE_ID SPECIFIED ";
			 return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deleteExpense()
	 * 
	 * This function deletes a Expense record
	 ********************************************************************************
	*/
	function deleteExpense()
	{
		if ($this->nExpenseID > 0)
		{
		
			$sql =	" DELETE FROM EXPENSE";
			$sql .= " WHERE EXPENSE_ID = " . $this->nExpenseID;
		
//DEBUG
//echo "SQL=". $sql . "<BR>";

			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "EXP007 - FAILED TO OPEN DB: ";
				return FALSE;
			}		

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);

//SQL Error
			if (!$result)
			{	
				 $this->sErrorMessage = "EXP008 - FAILED TO DELETE EXPENSE: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				//If Expense delete was successful, delete all Category xref records
				if($this->removeExpenseCategory(NULL))
				{
					return TRUE;	
				}
				else
				{
				 	$this->sErrorMessage = "DELETED EXPENSE BUT FAILED TO DELETE CATEGORY ASSOCIATIONS" . $this->sErrorMessage;
					return FALSE;
				}
			}
	
		}
		else
		{
			$this->sErrorMessage = "EXP009 - CAN NOT DELETE EXPENSE BECAUSE NO EXPENSE ID SUPPLIED. ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * removeExpenseCategory()
	 * 
	 * This function deletes an Expense Category Xref record
	 ********************************************************************************
	*/
	function removeExpenseCategory($nCategoryID)
	{
		if ($this->nExpenseID > 0)
		{
	
			$sql =	" DELETE FROM EXPENSE_CATEGORY_XREF";
			$sql .= " WHERE EXPENSE_ID = " . $this->nExpenseID;
			if(!empty($nCategoryID))
			{
				$sql .= "   AND CATEGORY_ID = " . $nCategoryID;
			}
//DEBUG
//echo "SQL=". $sql . "<BR>";

			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "EXP014 - FAILED TO OPEN DB: ";
				return FALSE;
			}		

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);

//SQL Error
			if (!$result)
			{	
				 $this->sErrorMessage = "EXP015 - FAILED TO DELETE EXPENSE_CATEGORY_XREF: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}

		}
		else
		{
			$this->sErrorMessage = "EXP016 - Missing Expense ID (" . $this->nExpenseID . ") or Category ID (" . $nCategoryID . ")";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * categoryExists()
	 * 
	 * This function determines if an expense is associated to a category
	 * as defined by a category ID or any categories  and returns a boolean. 
	 ********************************************************************************
	*/
	function categoryExists($nExpenseCategoryID)
	{
		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the product type)
		$sql =	"SELECT * ";
		$sql .= "  FROM EXPENSE_CATEGORY_XREF ";
		$sql .=	" WHERE 1=1 ";
		   

		//If a specific Expense ID is being searched, match on the ID
		if ($this->nExpenseID > 0 )
		{
			$sql .= " AND EXPENSE_ID = " . $this->nExpenseID;
			if(!empty($nExpenseCategoryID))
			{
				$sql .= " AND CATEGORY_ID = " . $nExpenseCategoryID;
			}
		}
		else
		{
			$this->sErrorMessage = "EXP017 - Missing Expense ID (" . $this->nExpenseID . ") or Category ID (" . $nExpenseCategoryID . ")";
			return FALSE;
		}
//DEBUG
//echo "SQL=". $sql . "<BR>";
							
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "EXP018 - FAILED TO OPEN DB: " . $this->DB->dbError;
			return FALSE;
		}

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);

//SQL Error
		if (!$result) {

			 $this->sErrorMessage = "EXP019 - " . mysqli_error($this->DB->dbConnection);
			 $this->DB->closeDB();
			 return FALSE;
		}
		else 
		{
			if (mysqli_num_rows($result) == 1 )
			{
				$this->DB->closeDB();
				return TRUE;
			}
			else
			{
				$this->DB->closeDB();
				return FALSE;
			}
		
		}

	}

	/*
	 ********************************************************************************
	 * loadExpenseObject()
	 * This function loads a given row from the EXPENSE table into a Expense object.  
	 ********************************************************************************
	*/
	function loadExpenseObject(&$oExpense, &$row)
	{

			$oExpense->nExpenseID = $row['EXPENSE_ID'];			
			$oExpense->dtExpenseDate = $row['EXPENSE_DATE'];			
			$oExpense->nExpenseAmount = $row['EXPENSE_AMOUNT'];			
			$oExpense->sExpenseDescription = $row['EXPENSE_DESCRIPTION'];
			$oExpense->nTourID = $row['TOUR_ID'];			
			$oExpense->nProductID = $row['PRODUCT_ID'];		
			$oExpense->nVendorID = $row['VENDOR_ID'];			
			$oExpense->nTaxCategoryID = $row['TAX_CATEGORY_ID'];			
			$oExpense->dtLastUpdate= $row['LAST_UPDATE'];			
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
		$this->sExpenseDescription = mysqli_real_escape_string($this->DB->dbConnection,$this->sExpenseDescription);	
	}
	/*
	 ********************************************************************************
	 * getExpenseErrors()
	 * 
	 * This function creates an array of Expenses that have errors 
	 ********************************************************************************
	*/
	function getExpenseErrors()
	{
		return TRUE;		
	}
	
}
	
?>