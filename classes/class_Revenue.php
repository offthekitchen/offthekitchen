<?php
/*
*******************************************************************
class_Revenue.php
This PHP file defines Revenue object class
NOTES
Date        Change
-------------------------------------------------------------
2015-10-22	Changed BuildWhereClause() to ignore category ID 0 
2015-12-19	removed unneeded includes
2016-05-29  Fixed bug with buildWhereClause Year functions
2019-08-15  Added Artist ID
*******************************************************************
*/

//include DB Class		
include_once(CLASS_DIR . "/class_DB.php");
//include Product Class
include_once(CLASS_DIR . "/class_Product.php");
//include Error Class
include_once(CLASS_DIR . "/class_Error.php");

class Revenue
{
	var $DB;

	//REVENUE Table Values
	var $nRevenueID = 0;
	var $dtPaidDate = NULL;
	var $nRevenueAmount = 0.00;
	var $dtRevenueDate = NULL;
	var $sRevenueDescription = NULL;
	var $nPaymentID = NULL;
	var $nRevenueTypeID = NULL;
	var $nProductID = NULL;
	var $nProductQty = NULL;
	var $nPerformanceID = NULL;
	var $bColoradoRevenue = NULL;
	var $bElPasoRevenue = NULL;
	var $bCharitable = NULL;
	var $bResale = NULL;
	var $sProductName = NULL;
	var $dtLastUpdate = NULL;

	//Variable used only for searching
	var $nRevenueYear = 0;
	var $nPaidYear = 0;
	var $nExcludePerformanceID = NULL;
	var $nExcludePaymentID = NULL;
	var $nArtistID = 0;
	var $dtStartDate = NULL;
	var $dtEndDate = NULL;
	var $nCategoryID = NULL;
	var $sOrderBy = NULL;

	//Booleans used in Searching
	var $bFuzzyNameSearch = FALSE;
	var $bPerformanceRelated = FALSE;
	var $bIncludeProductInfo = FALSE;
	var $bIncludeRevenueTypeInfo = FALSE;
	var $bMusicRelatedOnly = FALSE;

	var $sErrorMessage = "";

	//Revenue Type Object 
	var $thisRevenue;

	/*****************************
	 * Arrays of Related Objects *
	 *****************************/
	//Revenue Records matching search criteria
	var $aRevenueRecords = array();
	//Categories IDs
	var $aRevenueCategoryIDs = array();
	//Revenue Errors
	var $aRevenueErrors = array();

	//Constructor
	function __construct()
	{
		$this->DB = new Database();
	}


	/*
	 ********************************************************************************
	 * getRevenue()
	 * 
	 * This function retrieves Revenue data based on data in the properites and
	 * returns an array of Revenue Objects
	 ********************************************************************************
	 */
	function getRevenue()
	{
		$this->aRevenueRecords = array();

		//Begin the SQL SELECT STATEMENT 
		$sql = "SELECT REVENUE.* ";

		if ($this->bIncludeProductInfo) {
			$sql .= " , PRODUCT.PRODUCT_NAME ";
		}

		if ($this->bIncludeRevenueTypeInfo) {
			$sql .= " , REVENUE_TYPE.REVENUE_TYPE_NAME ";
		}

		$sql .= "  FROM REVENUE ";

		if ($this->bIncludeProductInfo) {
			$sql .= " LEFT OUTER JOIN PRODUCT ON (REVENUE.PRODUCT_ID = PRODUCT.PRODUCT_ID) ";
		}

		if ($this->bIncludeRevenueTypeInfo) {
			$sql .= " INNER JOIN REVENUE_TYPE ON (REVENUE.REVENUE_TYPE_ID = REVENUE_TYPE.REVENUE_TYPE_ID) ";
		}


		$sql .= " WHERE 1=1 ";

		$this->buildWhereClause($sql);

		//DEBUG
//echo "SQL={$sql}<BR>";

		if (!$this->executeSQL($sql, $result)) {
			return -1;
		} else {
			$iRevenueCount = 0;

			while ($row = mysqli_fetch_array($result)) {

				$oNextRevenue = new Revenue();

				//Load each product into a Revenue Object
				$this->loadRevenueObject($oNextRevenue, $row);

				//Then add the object to the array of found products
				$this->aRevenueRecords[$iRevenueCount] = $oNextRevenue;

				$iRevenueCount++;

			}

			$this->DB->closeDB();
			return TRUE;

		}

	}

	/*
	 ********************************************************************************
	 * getNumberOfRevenues()
	 * 
	 * This function retrieves the number of revenues matching values in the 
	 * properites.  It returns the number of matching expense records or a -1
	 * if there is a failure
	 ********************************************************************************
	 */
	function getNumberOfRevenues()
	{

		$this->aRevenueRecords = array();

		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the product type)
		$sql = "SELECT COUNT(*) AS REVENUE_COUNT ";
		$sql .= "  FROM REVENUE ";
		$sql .= " WHERE 1=1 ";

		$this->buildWhereClause($sql);

		//DEBUG
//echo "SQL={$sql}<BR>";

		if (!$this->executeSQL($sql, $result)) {
			return -1;
		} else {
			$row = mysqli_fetch_array($result);

			$nNumberOfRevenues = $row['REVENUE_COUNT'];

			$this->DB->closeDB();
			return $nNumberOfRevenues;

		}

	}

	/*
	 ********************************************************************************
	 * getRevenueAmountTotal()
	 * 
	 * This function retrieves the revenue amount totoal of records matching values  
	 * in the properites.  
	 ********************************************************************************
	 */
	function getRevenueAmountTotal()
	{

		$this->aRevenueRecords = array();

		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the product type)
		$sql = "SELECT SUM(REVENUE_AMOUNT) AS TOTAL_AMOUNT ";
		$sql .= "  FROM REVENUE ";
		$sql .= " WHERE 1=1 ";

		$this->buildWhereClause($sql);
		//DEBUG
//echo "SQL={$sql}<BR>";


		$result = NULL;

		if (!$this->executeSQL($sql, $result)) {
			return -1;
		} else {
			$row = mysqli_fetch_array($result);

			$nTotalAmount = $row['TOTAL_AMOUNT'];

			$this->DB->closeDB();
			return $nTotalAmount;

		}

	}

	/*
	 ********************************************************************************
	 * getProductQuantityTotal()
	 * 
	 * This function retrieves the product quantity totoal of records matching values  
	 * in the properites.  
	 ********************************************************************************
	 */
	function getProductQuantityTotal()
	{

		$this->aRevenueRecords = array();

		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the product type)
		$sql = "SELECT SUM(PRODUCT_QTY) AS TOTAL_PRODUCTS ";
		$sql .= "  FROM REVENUE ";
		$sql .= " WHERE 1=1 ";

		$this->buildWhereClause($sql);
		//DEBUG
//echo "SQL={$sql}<BR>";

		$result = NULL;

		if (!$this->executeSQL($sql, $result)) {
			return -1;
		} else {
			$row = mysqli_fetch_array($result);

			$nTotalProducts = $row['TOTAL_PRODUCTS'];

			$this->DB->closeDB();
			return $nTotalProducts;

		}

	}

	/*
	 ********************************************************************************
	 * buildWhereClause()
	 * 
	 * This function builds a where clause for selecting Revenue records 
	 ********************************************************************************
	 */
	function buildWhereClause(&$sql)
	{

		//If a specific Revenue ID is being searched, match on the ID
		if ($this->nRevenueID > 0) {
			$sql .= " AND REVENUE.REVENUE_ID = {$this->nRevenueID}";
		}

		// ****************
		// * REVENUE_DATE *
		// ****************
		if (!empty($this->dtRevenueDate)) {
			$sql .= " AND REVENUE.REVENUE_DATE LIKE '{$this->dtRevenueDate}%'";
		}

		// *************
		// * PAID_DATE *
		// *************
		if (!empty($this->dtPaidDate)) {
			$sql .= " AND REVENUE.PAID_DATE LIKE '{$this->dtPaidDate}%'";
		}

		// ********************
		// * REVENUE YEAR *
		// ********************
		if (!empty($this->nRevenueYear)) {
			$sql .= " AND YEAR(REVENUE.REVENUE_DATE) = {$this->nRevenueYear}";
		}

		// ********************
		// * PAID YEAR *
		// ********************
		if (!empty($this->nPaidYear)) {
			$sql .= " AND YEAR(REVENUE.PAID_DATE) = {$this->nPaidYear}";
		}

		// **************
		// * START DATE *
		// **************
		if (!empty($this->dtStartDate)) {
			$sql .= " AND REVENUE.PAID_DATE >= '{$this->dtStartDate}'";
		}

		// ************
		// * END DATE *
		// ************
		if (!empty($this->dtEndDate)) {
			$sql .= " AND REVENUE.PAID_DATE <= '" . $this->dtEndDate . "'";
		}

		// ***********************
		// * REVENUE DESCRIPTION *
		// ***********************
		if (!empty($this->sRevenueDescription)) {
			//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
			if ($this->bFuzzyNameSearch) {
				$sql .= " AND REVENUE.REVENUE_DESCRIPTION LIKE '%{$this->sRevenueDescription}%'";
			} else {
				$sql .= " AND REVENUE.REVENUE_DESCRIPTION = '{$this->sRevenueDescription}'";
			}
		}

		// ******************
		// * REVENUE_AMOUNT *
		// ******************
		if ($this->nRevenueAmount > 0) {
			$sql .= " AND REVENUE.REVENUE_AMOUNT = {$this->nRevenueAmount}";
		}

		// ***************
		// * PAYMENT_ID  *
		// ***************
		if ($this->nPaymentID > 0) {
			$sql .= " AND REVENUE.PAYMENT_ID = {$this->nPaymentID}";
		}
		if ($this->nExcludePaymentID > 0) {
			$sql .= " AND ( ISNULL(REVENUE.PAYMENT_ID) OR REVENUE.PAYMENT_ID <> {$this->nExcludePaymentID} )";
		}

		// ********************
		// * REVENUE_TYPE_ID  *
		// ********************
		if ($this->nRevenueTypeID > 0) {
			$sql .= " AND REVENUE.REVENUE_TYPE_ID = {$this->nRevenueTypeID}";
		}

		// *******************
		// * PERFORMANCE_ID  *
		// *******************
		if ($this->nPerformanceID > 0) {
			$sql .= " AND REVENUE.PERFORMANCE_ID = {$this->nPerformanceID}";
		}
		if ($this->nExcludePerformanceID > 0) {
			$sql .= " AND ( ISNULL(REVENUE.PERFORMANCE_ID) OR REVENUE.PERFORMANCE_ID <> {$this->nExcludePerformanceID} )";
		}

		// ***********************
		// * PERFORMANCE RELATED *
		// ***********************
		if ($this->bPerformanceRelated) {
			$sql .= " AND !ISNULL(REVENUE.PERFORMANCE_ID)";
			$sql .= " AND REVENUE.PERFORMANCE_ID > 0";
		}

		// *********************
		// * COLORADO_REVENUE  *
		// *********************
		if (!is_null($this->bColoradoRevenue)) {
			$sql .= " AND REVENUE.COLORADO_REVENUE = {$this->bColoradoRevenue}";
		}

		// ********************
		// * EL_PASO_REVENUE  *
		// ********************
		if (!is_null($this->bElPasoRevenue)) {
			$sql .= " AND REVENUE.EL_PASO_REVENUE = {$this->bElPasoRevenue}";
		}

		// ***************
		// * CHARITABLE  *
		// ***************
		if (!is_null($this->bCharitable)) {
			$sql .= " AND REVENUE.CHARITABLE = {$this->bCharitable}";
		}

		// ***********
		// * RESALE  *
		// ***********
		if (!is_null($this->bResale)) {
			$sql .= " AND REVENUE.RESALE = {$this->bResale}";
		}

		// ****************
		// * PRODUCT_ID   *
		// ****************

		if ($this->nProductID > 0) {
			//echo "Looking for Revenues with product ID {$this->nProductID}<br>";
			$sql .= " AND REVENUE.PRODUCT_ID = {$this->nProductID}";
		}
		//If the Product ID was set to 0, it's usually a string
		else if ($this->nProductID === '0' || $this->nProductID === 0) {
			//echo "Looking for revenues with NO product<br>";
			$sql .= " AND (REVENUE.PRODUCT_ID IS NULL OR REVENUE.PRODUCT_ID = '') ";
		} else {
			//echo "Looking for revenues REGARDLESS of product<br>";
		}

		// ****************
		// * ARTIST_ID   *
		// ****************
		if ($this->nArtistID > 0) {
			$sql .= " AND EXISTS(SELECT * ";
			$sql .= "              FROM PRODUCT ";
			$sql .= "             WHERE PRODUCT.PRODUCT_ID  = REVENUE.PRODUCT_ID ";
			$sql .= "               AND PRODUCT.ARTIST_ID = {$this->nArtistID})";
		}


		// *****************
		// * PRODUCT_QTY   *
		// *****************
		if ($this->nProductQty > 0) {
			$sql .= " AND REVENUE.PRODUCT_QTY = {$this->nProductQty}";
		}

		// ******************
		// * CATEGORY IDs   *
		// ******************
		if (sizeof($this->aRevenueCategoryIDs) > 0) {
			foreach ($this->aRevenueCategoryIDs as $nRevenueCategoryID) {
				if (!empty($nRevenueCategoryID)) {
					$sql .= " AND EXISTS(SELECT * ";
					$sql .= "              FROM REVENUE_CATEGORY_XREF ";
					$sql .= "             WHERE REVENUE_CATEGORY_XREF.REVENUE_ID  = REVENUE.REVENUE_ID ";
					$sql .= "               AND REVENUE_CATEGORY_XREF.CATEGORY_ID = {$nRevenueCategoryID})";
				}
			}

		}

		// ************
		// * ORDER BY *
		// ************
		$sql .= " ORDER BY REVENUE.REVENUE_DATE";

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
		if (!$this->DB->openDB()) {
			$this->sErrorMessage = "REV001 - FAILED TO OPEN DB: {$this->DB->dbError}";
			return false;
		}

		//Execute the SQL		
		$result = mysqli_query($this->DB->dbConnection, $sql);

		if (!$result) {

			$this->sErrorMessage = "REV002 - " . mysqli_error($this->DB->dbConnection);
			$this->DB->closeDB();

			return false;
		} else {
			return true;
		}
	}

	/*
	 ********************************************************************************
	 * insertRevenue()
	 * 
	 * This function inserts an REVENUE record 
	 ********************************************************************************
	 */
	function insertRevenue()
	{
		//Open the DB
		if (!$this->DB->openDB()) {
			$this->sErrorMessage = "REV003 - FAILED TO OPEN DB: ";
			return FALSE;
		}

		$this->escapeSpecialChars();

		$sql = " INSERT INTO REVENUE";
		$sql .= " (REVENUE_DATE,";
		$sql .= "  PAID_DATE,";
		$sql .= "  REVENUE_AMOUNT,";
		$sql .= "  REVENUE_DESCRIPTION,";
		$sql .= "  PAYMENT_ID,";
		$sql .= "  REVENUE_TYPE_ID,";
		$sql .= "  PERFORMANCE_ID,";
		$sql .= "  PRODUCT_ID,";
		$sql .= "  PRODUCT_QTY,";
		$sql .= "  COLORADO_REVENUE,";
		$sql .= "  EL_PASO_REVENUE,";
		$sql .= "  CHARITABLE,";
		$sql .= "  RESALE,";
		$sql .= "  LAST_UPDATE)";

		$sql .= " VALUES (";
		$sql .= "'{$this->dtRevenueDate}',";
		$sql .= "'{$this->dtPaidDate}',";
		$sql .= " {$this->nRevenueAmount},";
		$sql .= "'{$this->sRevenueDescription}',";
		if (is_numeric($this->nPaymentID)) {
			$sql .= "{$this->nPaymentID},";
		} else {
			$sql .= "NULL,";
		}

		if (is_numeric($this->nRevenueTypeID)) {
			$sql .= "{$this->nRevenueTypeID},";
		} else {
			$sql .= "NULL,";
		}

		if (is_numeric($this->nPerformanceID)) {
			$sql .= "{$this->nPerformanceID},";
		} else {
			$sql .= "NULL,";
		}

		if (is_numeric($this->nProductID)) {
			$sql .= "{$this->nProductID},";
		} else {
			$sql .= "0,";
		}

		if (is_numeric($this->nProductQty)) {
			$sql .= $this->nProductQty . ",";
		} else {
			$sql .= "NULL,";
		}

		if ($this->bColoradoRevenue) {
			$sql .= "TRUE,";
		} else {
			$sql .= "FALSE,";
		}

		if ($this->bElPasoRevenue) {
			$sql .= "TRUE,";
		} else {
			$sql .= "FALSE,";
		}

		if ($this->bCharitable) {
			$sql .= "TRUE,";
		} else {
			$sql .= "FALSE,";
		}

		if ($this->bResale) {
			$sql .= "TRUE,";
		} else {
			$sql .= "FALSE,";
		}

		$sql .= "SYSDATE()";
		$sql .= ")";

		//DEBUG
//echo "SQL={$sql}<BR>";

		//Execute the SQL		
		$result = mysqli_query($this->DB->dbConnection, $sql);

		//SQL Error
		if (!$result) {
			$this->sErrorMessage = "REV004 - FAILED TO INSERT REVENUE RECORD - " . mysqli_error($this->DB->dbConnection);
			return FALSE;
		} else {
			$this->nRevenueID = mysqli_insert_id($this->DB->dbConnection);
			return TRUE;
		}

	}

	/*
	 ********************************************************************************
	 * addRevenueCategory()
	 * 
	 * This function inserts an REVENUE_CATEGORY_XREF record 
	 ********************************************************************************
	 */
	function addRevenueCategory($nCategoryID)
	{
		//Open the DB
		if (!$this->DB->openDB()) {
			$this->sErrorMessage = "REV011 - FAILED TO OPEN DB: ";
			return FALSE;
		}

		if ($this->nRevenueID > 0 && $nCategoryID > 0) {
			$sql = " INSERT INTO REVENUE_CATEGORY_XREF";
			$sql .= " (REVENUE_ID,";
			$sql .= "  CATEGORY_ID)";
			$sql .= " VALUES (";
			$sql .= $this->nRevenueID . ",";
			$sql .= $nCategoryID;
			$sql .= ")";

			//DEBUG
//echo "SQL={$sql}<BR>";


			//Execute the SQL		
			$result = mysqli_query($this->DB->dbConnection, $sql);

			if (!$result) {

				$this->sErrorMessage = "REV012 - FAILED TO INSERT REVENUE_CATEGORY_XREF RECORD - " . mysqli_error($this->DB->dbConnection);
				return FALSE;
			} else {
				return TRUE;
			}

		} else {
			$this->sErrorMessage = "REV013 - Missing Revenue ID ({$this->nRevenueID}) or Category ID ({$nRevenueCategoryID})";
			return FALSE;
		}

	}

	/*
	 ********************************************************************************
	 * updateRevenue()
	 * 
	 * This Updates a REVENUE record
	 ********************************************************************************
	 */
	function updateRevenue()
	{

		if ($this->nRevenueID > 0) {

			//Open the DB
			if (!$this->DB->openDB()) {
				$this->sErrorMessage = "REV005 - FAILED TO OPEN DB: ";
				return FALSE;
			}

			$this->escapeSpecialChars();

			$sql = " UPDATE REVENUE ";
			$sql .= " SET REVENUE_DATE =  '{$this->dtRevenueDate}'";
			$sql .= ", PAID_DATE =  '{$this->dtPaidDate}'";
			$sql .= ", REVENUE_AMOUNT = {$this->nRevenueAmount}";
			$sql .= ", REVENUE_DESCRIPTION = '{$this->sRevenueDescription}'";
			if (is_numeric($this->nPaymentID)) {
				$sql .= ", PAYMENT_ID = {$this->nPaymentID}";
			} else {
				$sql .= ", PAYMENT_ID = NULL ";
			}
			if (is_numeric($this->nRevenueTypeID)) {
				$sql .= ", REVENUE_TYPE_ID = {$this->nRevenueTypeID}";
			} else {
				$sql .= ", REVENUE_TYPE_ID = NULL ";
			}
			if (is_numeric($this->nPerformanceID)) {
				$sql .= ", PERFORMANCE_ID = {$this->nPerformanceID}";
			} else {
				$sql .= ", PERFORMANCE_ID = NULL ";
			}
			if (is_numeric($this->nProductID)) {
				$sql .= ", PRODUCT_ID = {$this->nProductID}";
			} else {
				$sql .= ", PRODUCT_ID = NULL ";
			}
			if (is_numeric($this->nProductQty)) {
				$sql .= ", PRODUCT_QTY = {$this->nProductQty}";
			} else {
				$sql .= ", PRODUCT_QTY = NULL ";
			}

			if ($this->bColoradoRevenue) {
				$sql .= ", COLORADO_REVENUE = TRUE ";
			} else {
				$sql .= ", COLORADO_REVENUE = FALSE ";
			}

			if ($this->bElPasoRevenue) {
				$sql .= ", EL_PASO_REVENUE = TRUE ";
			} else {
				$sql .= ", EL_PASO_REVENUE = FALSE ";
			}

			if ($this->bCharitable) {
				$sql .= ", CHARITABLE = TRUE ";
			} else {
				$sql .= ", CHARITABLE = FALSE ";
			}

			if ($this->bResale) {
				$sql .= ", RESALE = TRUE ";
			} else {
				$sql .= ", RESALE = FALSE ";
			}

			$sql .= ", LAST_UPDATE = SYSDATE()";
			$sql .= " WHERE REVENUE_ID  = {$this->nRevenueID}";

			//DEBUG
//echo "SQL={$sql}<BR>";

			//Execute the SQL		
			$result = mysqli_query($this->DB->dbConnection, $sql);

			//SQL Error
			if (!$result) {

				$this->sErrorMessage = "REV006 - FAILED TO UPDATE REVENUE: " . mysqli_error($this->DB->dbConnection);
				return FALSE;
			} else {
				return TRUE;
			}

		} else {
			$this->sErrorMessage = "REV010 - NO REVENUE_ID SPECIFIED ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deleteRevenue()
	 * 
	 * This function deletes a Revenue record
	 ********************************************************************************
	 */
	function deleteRevenue()
	{
		if ($this->nRevenueID > 0) {

			$sql = " DELETE FROM REVENUE";
			$sql .= " WHERE REVENUE_ID = {$this->nRevenueID}";

			//DEBUG
//echo "SQL={$sql}<BR>";

			//Open the DB
			if (!$this->DB->openDB()) {
				$this->sErrorMessage = "REV007 - FAILED TO OPEN DB: ";
				return FALSE;
			}

			//Execute the SQL		
			$result = mysqli_query($this->DB->dbConnection, $sql);

			//SQL Error
			if (!$result) {
				$this->sErrorMessage = "REV008 - FAILED TO DELETE REVENUE: " . mysqli_error($this->DB->dbConnection);
				return FALSE;
			} else {
				//If Revenue delete was successful, delete all Category xref records
				if ($this->removeRevenueCategory(NULL)) {
					return TRUE;
				} else {
					$this->sErrorMessage = "DELETED REVENUE BUT FAILED TO DELETE CATEGORY ASSOCIATIONS: {$this->sErrorMessage}";
					return FALSE;
				}
			}

		} else {
			$this->sErrorMessage = "REV009 - CAN NOT DELETE REVENUE BECAUSE NO REVENUE ID SUPPLIED. ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * removeRevenueCategory()
	 * 
	 * This function deletes an Revenue Category Xref record
	 ********************************************************************************
	 */
	function removeRevenueCategory($nCategoryID)
	{
		if ($this->nRevenueID > 0) {

			$sql = " DELETE FROM REVENUE_CATEGORY_XREF";
			$sql .= " WHERE REVENUE_ID = {$this->nRevenueID}";
			if (!empty($nCategoryID)) {
				$sql .= "   AND CATEGORY_ID = {$nCategoryID}";
			}
			//DEBUG
//echo "SQL={$sql}<BR>";

			//Open the DB
			if (!$this->DB->openDB()) {
				$this->sErrorMessage = "REV014 - FAILED TO OPEN DB: ";
				return FALSE;
			}

			//Execute the SQL		
			$result = mysqli_query($this->DB->dbConnection, $sql);

			//SQL Error
			if (!$result) {
				$this->sErrorMessage = "REV015 - FAILED TO DELETE REVENUE_CATEGORY_XREF: " . mysqli_error($this->DB->dbConnection);
				return FALSE;
			} else {
				return TRUE;
			}

		} else {
			$this->sErrorMessage = "REV016 - Missing Revenue ID ({$this->nRevenueID}) or Category ID ({$nRevenueCategoryID})";
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
	function categoryExists($nRevenueCategoryID)
	{
		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the product type)
		$sql = "SELECT * ";
		$sql .= "  FROM REVENUE_CATEGORY_XREF ";
		$sql .= " WHERE 1=1 ";


		//If a specific Revenue ID is being searched, match on the ID
		if ($this->nRevenueID > 0) {
			$sql .= " AND REVENUE_ID = {$this->nRevenueID}";
			if (!empty($nRevenueCategoryID)) {
				$sql .= " AND CATEGORY_ID = {$nRevenueCategoryID}";
			}
		} else {
			$this->sErrorMessage = "REV017 - Missing Revenue ID ({$this->nRevenueID}) or Category ID ({$nRevenueCategoryID})";
			return FALSE;
		}
		//DEBUG
//echo "SQL={$sql}<BR>";

		//Open the DB
		if (!$this->DB->openDB()) {
			$this->sErrorMessage = "REV018 - FAILED TO OPEN DB: {$this->DB->dbError}";
			return FALSE;
		}

		//Execute the SQL		
		$result = mysqli_query($this->DB->dbConnection, $sql);

		//SQL Error
		if (!$result) {

			$this->sErrorMessage = "REV019 - " . mysqli_error($this->DB->dbConnection);
			$this->DB->closeDB();
			return FALSE;
		} else {
			if (mysqli_num_rows($result) == 1) {
				$this->DB->closeDB();
				return TRUE;
			} else {
				$this->DB->closeDB();
				return FALSE;
			}

		}

	}

	/*
	 ********************************************************************************
	 * loadRevenueObject()
	 * This function loads a given row from the REVENUE table into a Revenue object.  
	 ********************************************************************************
	 */
	function loadRevenueObject(&$oRevenue, &$row)
	{

		$oRevenue->nRevenueID = $row['REVENUE_ID'];
		$oRevenue->dtPaidDate = $row['PAID_DATE'];
		$oRevenue->nRevenueAmount = $row['REVENUE_AMOUNT'];
		$oRevenue->sRevenueDescription = $row['REVENUE_DESCRIPTION'];
		$oRevenue->dtRevenueDate = $row['REVENUE_DATE'];
		$oRevenue->nPaymentID = $row['PAYMENT_ID'];
		$oRevenue->nRevenueTypeID = $row['REVENUE_TYPE_ID'];
		$oRevenue->nPerformanceID = $row['PERFORMANCE_ID'];
		$oRevenue->nProductID = $row['PRODUCT_ID'];
		$oRevenue->nProductQty = $row['PRODUCT_QTY'];
		$oRevenue->bColoradoRevenue = $row['COLORADO_REVENUE'];
		$oRevenue->bElPasoRevenue = $row['EL_PASO_REVENUE'];
		$oRevenue->bCharitable = $row['CHARITABLE'];
		$oRevenue->bResale = $row['RESALE'];
		if (isset($row['PRODUCT_NAME'])) {
			$oRevenue->sProductName = $row['PRODUCT_NAME'];
		}
		$oRevenue->dtLastUpdate = $row['LAST_UPDATE'];

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
		$this->sRevenueDescription = mysqli_real_escape_string($this->DB->dbConnection, $this->sRevenueDescription);
	}
	/*
	 ********************************************************************************
	 * getRevenueErrors()
	 * 
	 * This function creates an array of Revnues that have errors 
	 ********************************************************************************
	 */
	function getRevenueErrors()
	{
		$iErrorCount = 0;
		//Open the DB
		if (!$this->DB->openDB()) {
			$this->sErrorMessage = "REV00X - FAILED TO OPEN DB: ";
			return FALSE;
		}

		$this->escapeSpecialChars();

		//Find all Revenues with amounts unequal to their revenues
		$sql = " SELECT * FROM REVENUE";
		$sql .= " WHERE REVENUE.PAID_DATE = '0000-00-00'";
		$sql .= " OR REVENUE.REVENUE_DATE = '0000-00-00'";
		//DEBUG
		//echo "SQL=". $sql . "<BR>";

		$result = NULL;

		if (!$this->executeSQL($sql, $result)) {
			return -1;
		} else {
			while ($row = mysqli_fetch_array($result)) {
				$description = "Revenue: " . $row['REVENUE_DESCRIPTION'] . ": $" . $row['REVENUE_AMOUNT'];

				$oNextError = new ErrorObject($row['REVENUE_ID'], 'REVENUE', 'Revenue date and/or paid date is 0000-00-00', $description, 3);

				//Then add the object to the array of found products
				$this->aRevenueErrors[$iErrorCount] = $oNextError;

				$iErrorCount++;

			}
		}

		//Find all Revenues with no matching payment
		$sql = " SELECT * FROM REVENUE";
		$sql .= " WHERE NOT EXISTS(SELECT * FROM PAYMENT WHERE PAYMENT.PAYMENT_ID = REVENUE.PAYMENT_ID)";
		//DEBUG
		//echo "SQL=". $sql . "<BR>";

		$result = NULL;

		if (!$this->executeSQL($sql, $result)) {
			return -1;
		} else {
			while ($row = mysqli_fetch_array($result)) {
				$description = "Revenue: " . $row['REVENUE_DESCRIPTION'] . ": $" . $row['REVENUE_AMOUNT'];

				$oNextError = new ErrorObject($row['REVENUE_ID'], 'REVENUE', 'Revenue has no matching payment', $description, 3);

				//Then add the object to the array of found products
				$this->aRevenueErrors[$iErrorCount] = $oNextError;

				$iErrorCount++;

			}
		}

		$this->DB->closeDB();
		return TRUE;

	}

}

?>