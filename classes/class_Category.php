<?php
/*
*******************************************************************
class_Category.php
This PHP file defines Category object class
NOTES
Date        Change
-------------------------------------------------------------
2015-12-19	Refactored and removed unneeded includes
*******************************************************************
*/		
//include DB Class		
include_once (CLASS_DIR . "/class_DB.php");

//include Expense Class		
include_once (CLASS_DIR . "/class_Expense.php");


class Category
{

	var $DB;
	
	//Category Values
	var $nCategoryID = 0;
	var $sCategoryName = NULL;
	var $bExpenseRelated = FALSE;
	var $bRevenueRelated = FALSE;
	var $dtLastUpdate = NULL;

	var $bFuzzyNameSearch = FALSE;

	/*****************
	 * Object Arrays *
	 *****************/
	//Categories
	var $aCategoryRecords = array();
	//Expenses assigned to this Category
	var $aCategoryExpenses = array();
	//Expenses assigned to this Category
	var $aCategoryRevenues = array();
	//Category Errors
	var $aCategoryErrors = array();
	
		
	//Error variables
	var $sErrorMessage = "";

	//Constructor
   function __construct() 
   {
		$this->DB = new Database();
   }

	/*
	 ********************************************************************************
	 * getCategory()
	 * 
	 * This function retrieves Category data based on data in the properites and
	 *  returns an array of Category Objects
	 ********************************************************************************
	*/
	function getCategory()
	{
	
		$this->aCategoryRecords = array();
		
		//Begin the SQL SELECT STATEMENT
		$sql =	"SELECT  CATEGORY.* ";
		$sql .=	" FROM  CATEGORY ";
		$sql .=	" WHERE 1=1 ";
	
	
		//Category ID
		if ($this->nCategoryID > 0)
		{
			$sql .= " AND CATEGORY.CATEGORY_ID = {$this->nCategoryID}";
		}
		else
		{
		
			//Category Name
			if (!empty($this->sCategoryName))
			{
				if ($this->bFuzzyNameSearch)
				{
					$sql .= " AND CATEGORY.CATEGORY_NAME LIKE '%{$this->sCategoryName}%'";
				}
				else
				{
					$sql .= " AND CATEGORY.CATEGORY_NAME = '{$this->sCategoryName}'";
				}
			}
			
			//Expense Related
			if (!empty($this->bExpenseRelated))
			{
				if ($this->bExpenseRelated)
				{
						$sql .= " AND CATEGORY.EXPENSE_RELATED = TRUE ";
				}
				else
				{
						$sql .= " AND CATEGORY.EXPENSE_RELATED = FALSE ";
				}
			}
			//Revenue Related
			if (!empty($this->bRevenueRelated))
			{
				if ($this->bRevenueRelated)
				{
						$sql .= " AND CATEGORY.REVENUE_RELATED = TRUE ";
				}
				else
				{
						$sql .= " AND CATEGORY.REVENUE_RELATED = FALSE ";
				}
			}
						
			$sql .= " ORDER BY CATEGORY.CATEGORY_NAME";

		}		

//DEBUG
//echo "SQL={$sql}<BR>";

		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "CAT001 - FAILED TO OPEN DB: {$this->DB->dbError}";
			return FALSE;
		}

	
		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);
	
		//SQL Error
		if (!$result) {

			 $this->sErrorMessage = "CAT002 - " . mysqli_error($this->DB->dbConnection);
			 $this->DB->closeDB();
			 return FALSE;
		}
		else 
		{
			$iCategoryCount = 0;

			//If multiple rows are returned, then load them into the array of search results
			while($row = mysqli_fetch_array($result))
			{
				$oNextCategory = new Category();
				
				//Load each Category into an Object
				$this->loadCategoryObject($oNextCategory, $row);
										
				$this->aCategoryRecords[$iCategoryCount] = $oNextCategory;

				$iCategoryCount++;
				
			}

			return TRUE;
		}
	}

	/*
	 ********************************************************************************
	 * getCategoryExpenses()
	 * 
	 * This function loads the array of Expense objects 
	 ********************************************************************************
	*/
	function getCategoryExpenses()
	{
		$oCategoryExpenses = new Expense();	
		$oCategoryExpenses->nCategoryID = $this->nCategoryID;	
		
		if($oCategoryExpenses->getExpense())
		{
			$this->aCategoryExpenses = $oCategoryExpenses->aExpenseRecords;
		}
		else
		{
			$this->sErrorMessage = "CAT003 - Failed to get Expenses -{$oCategoryExpenses->sErrorMessage}";
			return FALSE;
		}

		return TRUE;

	}		

	/*
	 ********************************************************************************
	 * expenseExists()
	 * 
	 * This function determines if this category is associated to a particular expense
	 * as defined by a expense ID passed in as a parm or any expenses and returns 
	 * a boolean. 
	 ********************************************************************************
	*/
	function expenseExists($nCategoryExpenseID)
	{
		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the product type)
		$sql =	"SELECT * ";
		$sql .= "  FROM EXPENSE_CATEGORY_XREF ";
		$sql .=	" WHERE 1=1 ";
		   
		if ($this->nCategoryID > 0 )
		{
			$sql .= " AND CATEGORY_ID = {$this->nCategoryID}";
		}
		else
		{
			$this->sErrorMessage = "CAT017 - Missing Category ID ({$this->nCategoryID})";
			return FALSE;
		}

		//If a specific Expense ID is being searched, match on the ID
		if (!empty($nCategoryExpenseID))
		{
			$sql .= " AND EXPENSE_ID = {$nCategoryExpenseID}";
		}

//DEBUG
//echo "SQL={$sql}<BR>";
							
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "CAT018 - FAILED TO OPEN DB: {$this->DB->dbError}";
			return FALSE;
		}

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);

		if (!$result) {

			 $this->sErrorMessage = "CAT019 - " . mysqli_error($this->DB->dbConnection);
			 $this->DB->closeDB();
			 return FALSE;
		}
		else 
		{
			if (mysqli_num_rows($result) > 0 )
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
	 * insertCategory()
	 * 
	 * This function inserts a Category record on the DB
	 ********************************************************************************
	*/
	function insertCategory()
	{
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "CAT004 - FAILED TO OPEN DB: ";
			return FALSE;
		}

		$this->escapeSpecialChars();
	
		$sql =	" INSERT INTO CATEGORY";
		$sql .= " (CATEGORY_NAME, ";
		$sql .= "  EXPENSE_RELATED, ";
		$sql .= "  REVENUE_RELATED, ";
		$sql .= "  LAST_UPDATE)";
		$sql .= " VALUES (";
		$sql .= "'{$this->sCategoryName}',";
		if ($this->bExpenseRelated)
		{
			$sql .= "TRUE,";		
		}
		else
		{
			$sql .= "FALSE,";		
		}
		if ($this->bRevenueRelated)
		{
			$sql .= "TRUE,";		
		}
		else
		{
			$sql .= "FALSE,";		
		}
		$sql .= " NOW()";
		$sql .= ")";	
		
//DEBUG
//echo "SQL={sql}<BR>";

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);
	
		//SQL Error
		if (!$result) {

			 $this->sErrorMessage = "CAT005 - " . mysqli_error($this->DB->dbConnection);
			 return FALSE;
		}
		else 
		{
			$this->nCategoryID = mysqli_insert_id($this->DB->dbConnection);
 
			return TRUE;	
		}
		
		if (!$this->DB->closeDB())
		{
			$this->sErrorMessage = "CAT006 -  FAILED TO CLOSE DB: ";
			return FALSE;
		}
	
	}

	/*
	 ********************************************************************************
	 * updateCategory()
	 * 
	 * This function inserts a Category record on the DB
	 ********************************************************************************
	*/
	function updateCategory()
	{

		if($this->nCategoryID > 0)
		{	

			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "CAT007 - FAILED TO OPEN DB: ";
				return FALSE;
			}
			
			$this->escapeSpecialChars();


			$sql =	" UPDATE CATEGORY ";
			$sql .= " SET CATEGORY_NAME = '{$this->sCategoryName}'";
			if ($this->bExpenseRelated)
			{
				$sql .= ", EXPENSE_RELATED = TRUE";
			}
			else
			{
				$sql .= ", EXPENSE_RELATED = FALSE";
			}
			if ($this->bRevenueRelated)
			{
				$sql .= ", REVENUE_RELATED = TRUE";
			}
			else
			{
				$sql .= ", REVENUE_RELATED = FALSE";
			}
			$sql .= ", LAST_UPDATE = NOW()";
			$sql .= " WHERE CATEGORY_ID = {$this->nCategoryID}";
			
//DEBUG
//echo "SQL={$sql}<BR>";

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result) {	

				 $this->sErrorMessage = "CAT008 - " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{	

				return TRUE;	
			}
			
			if (!$this->DB->closeDB())
			{
				$this->sErrorMessage = "CAT009 - FAILED TO CLOSE DB ";
				return FALSE;
			}
		}
		else
		{
				$this->sErrorMessage = "CAT010 - NO CATEGORY_ID SPECIFIED ";
				return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deleteCategory()
	 * 
	 * This function deletes a Category record from the DB
	 ********************************************************************************
	*/
	function deleteCategory()
	{
			//Look for Associated expenses
			$oCategoryExpenses = new Expense();
			$oCategoryExpenses->aExpenseCategoryIDs[0] = $this->nCategoryID;
			$nNumberOfCategoryExpenses = $oCategoryExpenses->getNumberOfExpenses();	

			if ($nNumberOfCategoryExpenses == -1)
			{
				$this->sErrorMessage = "CAT012 - Failed to Retrieve Expenses for Category: {$oCategoryExpenses->sErrorMessage}";
				return FALSE;
			}
			elseif ($nNumberOfCategoryExpenses > 0)
			{
				$this->sErrorMessage = "CAT013 - Can not delete Category because it has ";
				$this->sErrorMessage .= "<A HREF='" . ADMIN_DIR . "/ExpenseMaintenance.php?CATEGORY_ID={$this->nCategoryID}'>";		
				$this->sErrorMessage .= $nNumberOfCategoryExpenses . " expenses.</A>";
				return FALSE;
			}
	
		//Look for any associated expenses
/*		if ($this->expenseExists(NULL))
		{
			$this->sErrorMessage = "CAT011 - Can not delete Category because it has expenses assigned";
			return FALSE;
		}
*/			
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "CAT016 - FAILED TO OPEN DB: " . mysqli_error($this->DB->dbConnection);
			return FALSE;
		}
	
		$sql =	" DELETE FROM CATEGORY ";
		$sql .= " WHERE CATEGORY_ID = {$this->nCategoryID}";
		
//DEBUG
//echo "SQL={$sql}<BR>";

	
		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);
	
		//SQL Error
		if (!$result) {

			 $this->sErrorMessage = "CAT014 - Failed to Delete Category " . mysqli_error($this->DB->dbConnection);
			 return FALSE;
		}
		else 
		{
			return TRUE;	
		}
		
		if (!$this->DB->closeDB())
		{
			$this->sErrorMessage = "CAT015 -  FAILED TO CLOSE DB: ";
			return FALSE;
		}
	
	}

	/*
	 ********************************************************************************
	 * loadCategoryObject()
	 * This function loads a given row from the CATEGORY table into a Expense object.  
	 ********************************************************************************
	*/
	function loadCategoryObject(&$oCategory, &$row)
	{
		$oCategory->nCategoryID = $row['CATEGORY_ID'];			
		$oCategory->sCategoryName = $row['CATEGORY_NAME'];	
		$oCategory->bExpenseRelated = $row['EXPENSE_RELATED'];	
		$oCategory->bRevenueRelated = $row['REVENUE_RELATED'];	
		$oCategory->dtLastUpdate = $row['LAST_UPDATE'];			
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
	
		$this->sCategoryName = mysqli_real_escape_string($this->DB->dbConnection,$this->sCategoryName);	

	}


}
	
?>