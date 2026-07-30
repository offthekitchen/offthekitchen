<?php
/*
*******************************************************************
class_Product_Type.php
This PHP file defines the Product Type object class
NOTES
Date        Change
-------------------------------------------------------------
2015-12-19	removed unneeded includes
*******************************************************************
*/	

//include DB Class		
include_once (CLASS_DIR . "/class_DB.php");

//include Product Class		
include_once (CLASS_DIR . "/class_Product.php");

class ProductType
{

	var $DB;
	
	//Product Type Values
	var $nProductTypeID = 0;
	var $sProductTypeName = NULL;
	var $dtLastUpdate = NULL;

	var $bFuzzyNameSearch = FALSE;

	/*****************
	 * Object Arrays *
	 *****************/
	//Product Types
	var $aProductTypeRecords = array();
	//Products assigned to this Product Type
	var $aProductTypeProducts = array();
	//Product Type Errors
	var $aProdTypeErrors = array();
	
		
	//Error variables
	var $sErrorMessage = "";

	//Constructor
   function __construct() 
   {
		$this->DB = new Database();
   }

	/*
	 ********************************************************************************
	 * getProductType()
	 * 
	 * This function retrieves Product Type data based on data in the properites and
	 *  returns an array of Product Type Objects
	 ********************************************************************************
	*/
	function getProductType()
	{
	
		$this->aProductTypeRecords = array();
		
		//Begin the SQL SELECT STATEMENT
		$sql =	"SELECT  PRODUCT_TYPE.* ";
		$sql .=	" FROM  PRODUCT_TYPE PRODUCT_TYPE ";
		$sql .=	" WHERE 1=1 ";
	
	
		//Product Type ID
		if ($this->nProductTypeID > 0)
		{
			$sql .= " AND PRODUCT_TYPE.PRODUCT_TYPE_ID = {$this->nProductTypeID}";
		}
		else
		{
		
			if (!empty($this->sProductTypeName))
			{
				if ($this->bFuzzyNameSearch)
				{
					$sql .= " AND PRODUCT_TYPE.PRODUCT_TYPE_NAME LIKE '%{$this->sProductTypeName}%'";
				}
				else
				{
					$sql .= " AND PRODUCT_TYPE.PRODUCT_TYPE_NAME = '{$this->sProductTypeName}'";
				}
			}
			$sql .= " ORDER BY PRODUCT_TYPE.PRODUCT_TYPE_NAME";

		}		

//DEBUG
//echo "SQL={$sql}<BR>";

		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "PRT001 - FAILED TO OPEN DB: {$this->DB->dbError}";
			return FALSE;
		}

	
		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);
	
		//SQL Error
		if (!$result) {

			 $this->sErrorMessage = "PRT002 - " . mysqli_error($this->DB->dbConnection);
			 $this->DB->closeDB();
			 return FALSE;
		}
		else 
		{
			$iProductTypeCount = 0;

			//If multiple rows are returned, then load them into the array of search results
			while($row = mysqli_fetch_array($result))
			{
				$oNextProductType = new ProductType();
				
				//Load each Product Type into an Object
				$this->loadProductTypeObject($oNextProductType, $row);
										
				$this->aProductTypeRecords[$iProductTypeCount] = $oNextProductType;

				$iProductTypeCount++;
				
				
			}
			return TRUE;
		}
	}


	/*
	 ********************************************************************************
	 * getProductTypeProducts()
	 * 
	 * This function loads the array of product objects 
	 ********************************************************************************
	*/
	function getProductTypeProducts()
	{
		$oProductTypeProducts = new Product();	
		$oProductTypeProducts->nProductTypeID = $this->nProductTypeID;	
		
		if($oProductTypeProducts->getProduct())
		{
			$this->aProductTypeProducts = $oProductTypeProducts->aProductRecords;
		}
		else
		{
			$this->sErrorMessage = "PRT003 - {$oProductTypeProducts->sErrorMessage}";
			return FALSE;
		}
		
		return TRUE;

	}		
	
	/*
	 ********************************************************************************
	 * insertProductType()
	 * 
	 * This function inserts a Product Type record on the DB
	 ********************************************************************************
	*/
	function insertProductType()
	{
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "PRT004 - FAILED TO OPEN DB: ";
			return FALSE;
		}
			
		$this->escapeSpecialChars();
			
		$sql =	" INSERT INTO PRODUCT_TYPE";
		$sql .= " (PRODUCT_TYPE_NAME, LAST_UPDATE)";
		$sql .= " VALUES (";
		$sql .= "'{$this->sProductTypeName}',";	
		$sql .= " NOW()";
		$sql .= ")";	
		
//DEBUG
//echo "SQL={$sql}<BR>";

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);
	
		//SQL Error
		if (!$result) {

			 $this->sErrorMessage = "PRT005 - " . mysqli_error($this->DB->dbConnection);
			 return FALSE;
		}
		else 
		{
			$this->nProductTypeID = mysqli_insert_id($this->DB->dbConnection);
 
			return TRUE;	
		}
		
		if (!$this->DB->closeDB())
		{
			$this->sErrorMessage = "PRT006 -  FAILED TO CLOSE DB: ";
			return FALSE;
		}
	
	}

	/*
	 ********************************************************************************
	 * updateProductType()
	 * 
	 * This function inserts a Product Type record on the DB
	 ********************************************************************************
	*/
	function updateProductType()
	{

		if($this->nProductTypeID > 0)
		{	
			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "PRT007 - FAILED TO OPEN DB: ";
				return FALSE;
			}
			
			$this->escapeSpecialChars();

			$sql =	" UPDATE PRODUCT_TYPE ";
			$sql .= " SET PRODUCT_TYPE_NAME = '{$this->sProductTypeName}'";
			$sql .= ", LAST_UPDATE = NOW()";
			$sql .= " WHERE PRODUCT_TYPE_ID = {$this->nProductTypeID}";
			
//DEBUG
//echo "SQL={$sql}<BR>";

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result) {	

				 $this->sErrorMessage = "PRT008 - " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{	

				return TRUE;	
			}
			
			if (!$this->DB->closeDB())
			{
				$this->sErrorMessage = "PRT009 - FAILED TO CLOSE DB ";
				return FALSE;
			}
		}
		else
		{
				$this->sErrorMessage = "PRT010 - NO PRODUCT_TYPE_ID SPECIFIED ";
				return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deleteProductType()
	 * 
	 * This function deletes a Product Type record from the DB
	 ********************************************************************************
	*/
	function deleteProductType()
	{
	
		$this->aProductTypeProducts = array();

		//Look for Assocciated products
		if ($this->getProductTypeProducts())
		{
			if (sizeof($this->aProductTypeProducts) > 0)
			{
				$this->sErrorMessage = "PRT011 - Can not delete Product Type because it has " . sizeof($this->aProductTypeProducts) . " products assigned";
				return FALSE;
			}

		}
		else
		{
			$this->sErrorMessage = "PRT012 - Failed to Retrieve Products for Product Type";
			return FALSE;
		}
			
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "PRT013 - FAILED TO OPEN DB: " . mysqli_error($this->DB->dbConnection);
			return FALSE;
		}
		
	
		$sql =	" DELETE FROM PRODUCT_TYPE ";
		$sql .= " WHERE PRODUCT_TYPE_ID = {$this->nProductTypeID}";
		
//DEBUG
//echo "SQL={$sql}<BR>";

	
		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);
	
		//SQL Error
		if (!$result) {

			 $this->sErrorMessage = "PRT014 - Failed to Delete Product type " . mysqli_error($this->DB->dbConnection);
			 return FALSE;
		}
		else 
		{
			return TRUE;	
		}
		
		if (!$this->DB->closeDB())
		{
			$this->sErrorMessage = "PRT015 -  FAILED TO CLOSE DB: ";
			return FALSE;
		}
	
	}

	/*
	 ********************************************************************************
	 * loadProductTypeObject()
	 * This function loads a given row from the PRODUCT_TYPE table into a Product object.  
	 ********************************************************************************
	*/
	function loadProductTypeObject(&$oProductType, &$row)
	{
		$oProductType->nProductTypeID = $row['PRODUCT_TYPE_ID'];			
		$oProductType->sProductTypeName = $row['PRODUCT_TYPE_NAME'];	
		$oProductType->dtLastUpdate = $row['LAST_UPDATE'];			
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
		$this->sProductTypeName = mysqli_real_escape_string($this->DB->dbConnection,$this->sProductTypeName);	

	}

}
	
?>