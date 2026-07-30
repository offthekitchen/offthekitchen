<?php
/*
*******************************************************************
class_SearchTerms.php
This PHP file defines Searchterm object class
NOTES
Date        Change
-------------------------------------------------------------
*******************************************************************
*/

//include DB Class		
include_once(CLASS_DIR . "/class_DB.php");


class SearchTerms
{

	var $DB;

	//SearchTerms Table Values
	var $nSearchTermsId = NULL;
	var $nWebsite = 0;
	var $sPageName = NULL;
	var $sPageURL = NULL;
	var $sSearchTerms = NULL;
	var $sOrderby = NULL;

	//Booleans used in Searching
	var $bFuzzyNameSearch = FALSE;

	var $aSearchTermsRecords = [];

	var $sErrorMessage = "";

	/*****************************
	 * Arrays of Related Objects *
	 *****************************/
	//SearchTerm Records matching search criteria
	var $aSearchTerms = array();

	//Constructor
	function __construct()
	{
		$this->DB = new Database();
	}

	/*
	 ********************************************************************************
	 * getSearchTerms()
	 * 
	 * This function retrieves SearchTerms data based on data in the properites and
	 * returns an array of SearchTerms Objects
	 ********************************************************************************
	*/
	function getSearchTerms()
	{

		$this->aSearchTermsRecords = array();

		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the SearchTerms)
		$sql =	"SELECT * ";
		$sql .= "  FROM SEARCH_TERMS ";
		$sql .=	" WHERE 1=1 ";

		$this->buildWhereClause($sql);

		//DEBUG
		//echo "SQL={$sql}<BR>";

		$result = NULL;

		if (!$this->executeSQL($sql, $result)) {
			return -1;
		} else {

			$iSearchTermsCount = 0;

			while ($row = mysqli_fetch_array($result)) {

				$oNextSearchTerms = new SearchTerms();

				//Load each product into a SearchTerms Object
				$this->loadSearchTermsObject($oNextSearchTerms, $row);

				//Then add the object to the array of found products
				$this->aSearchTermsRecords[$iSearchTermsCount] = $oNextSearchTerms;

				$iSearchTermsCount++;
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
		if ($this->nSearchTermsId > 0) {
			$sql .= " AND SEARCH_TERMS_ID = {$this->nSearchTermsId}";
		} else {
			//Otherwise build an SQL statement based on the values in the properties

			// ************
			// * Website  *
			// ************
			if (!empty($this->nWebsite)) {
				//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
				$sql .= " AND WEBSITE = {$this->nWebsite}";
			}

			// *************
			// * Page Name *
			// *************
			if (!empty($this->sPageName)) {
				//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
				if ($this->bFuzzyNameSearch) {
					$sql .= " AND PAGE_NAME LIKE '%{$this->sPageName}%'";
				} else {
					$sql .= " AND PAGE_NAME = '{$this->sPageName}'";
				}
			}

			// ************
			// * Page URL *
			// ************
			if (!empty($this->sPageURL)) {
				//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
				$sql .= " AND PAGE_URL LIKE '%{$this->sPageURL}%'";
			}


			// ****************
			// * SEARCH_TERMS *
			// ****************
			if (!empty($this->sSearchTerms)) {
				$sql .= " AND SEARCH_TERMS LIKE '%{$this->sSearchTerms}%'";
			}

			// ************
			// * ORDER BY *
			// ************
			//ORDER BY
			$sql .= " ORDER BY PAGE_NAME";
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
		if (!$this->DB->openDB()) {
			$this->sErrorMessage = "STM001 - FAILED TO OPEN DB: {$this->DB->dbError}";
			return false;
		}

		//Execute the SQL		
		$result = mysqli_query($this->DB->dbConnection, $sql);

		if (!$result) {

			$this->sErrorMessage = "STM002 - " . mysqli_error($this->DB->dbConnection);
			$this->DB->closeDB();

			return false;
		} else {
			return true;
		}
	}

	/*
	 ********************************************************************************
	 * Insert SearchTerms()
	 * 
	 * This function inserts a SearchTerms record 
	 ********************************************************************************
	*/
	function insertSearchTerms()
	{
		//Open the DB
		if (!$this->DB->openDB()) {
			$this->sErrorMessage = "STM003 - FAILED TO OPEN DB: ";
			return FALSE;
		}

		$this->escapeSpecialChars();

		$sql =	" INSERT INTO SEARCH_TERMS";
		$sql .= " (WEBSITE,";
		$sql .= "  PAGE_NAME,";
		$sql .= "  PAGE_URL,";
		$sql .= "  SEARCH_TERMS)";

		$sql .= " VALUES (";
		$sql .= "{$this->nWebsite},";
		$sql .= "'{$this->sPageName}',";
		$sql .= "'{$this->sPageURL}',";
		$sql .= "'{$this->sSearchTerms}'";
		$sql .= ")";

		//DEBUG
		//echo "SQL={$sql}<BR>";

		//Execute the SQL		
		$result = mysqli_query($this->DB->dbConnection, $sql);

		//SQL Error
		if (!$result) {

			$this->sErrorMessage = "STM004 - FAILED TO INSERT SEARCHTERMS RECORD - " . mysqli_error($this->DB->dbConnection);
			return FALSE;
		} else {
			$this->nSearchTermsId = mysqli_insert_id($this->DB->dbConnection);
			return TRUE;
		}
	}


	/*
	 ********************************************************************************
	 * updateSearchTerms()
	 * 
	 * This Updates a SearchTerms record
	 ********************************************************************************
	*/
	function updateSearchTerms()
	{

		//Open the DB
		if (!$this->DB->openDB()) {
			$this->sErrorMessage = "STM005 - FAILED TO OPEN DB: ";
			return FALSE;
		}

		$this->escapeSpecialChars();

		$sql =	" UPDATE SEARCH_TERMS ";
		$sql .= "    SET WEBSITE = {$this->nWebsite}";
		$sql .= "       ,PAGE_NAME = '{$this->sPageName}'";
		$sql .= "       ,PAGE_URL = '{$this->sPageURL}'";
		$sql .= "       ,SEARCH_TERMS = '{$this->sSearchTerms}'";

		$sql .= "  WHERE SEARCH_TERMS_ID  = {$this->nSearchTermsId}";

		//DEBUG
		//echo "SQL={$sql}<BR>";

		//Execute the SQL		
		$result = mysqli_query($this->DB->dbConnection, $sql);

		//SQL Error
		if (!$result) {

			$this->sErrorMessage = "STM006 - FAILED TO UPDATE SEARCHTERMS: " . mysqli_error($this->DB->dbConnection);
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/*
	 ********************************************************************************
	 * deleteSearchTerms()
	 * 
	 * This function deletes a SearchTerms record
	 ********************************************************************************
	*/
	function deleteSearchTerms()
	{
		$sql =	" DELETE FROM SEARCH_TERMS";
		$sql .= "  WHERE SEARCH_TERMS_ID  = {$this->nSearchTermsId}";

		//DEBUG
		//echo "SQL={$sql}<BR>";

		//Open the DB
		if (!$this->DB->openDB()) {
			$this->sErrorMessage = "STM007 - FAILED TO OPEN DB: ";
			return FALSE;
		}

		//Execute the SQL		
		$result = mysqli_query($this->DB->dbConnection, $sql);

		//SQL Error
		if (!$result) {
			$this->sErrorMessage = "STM008 - FAILED TO DELETE SEARCH_TERMS: " . mysqli_error($this->DB->dbConnection);
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/*
	 ********************************************************************************
	 * loadSearchTermsObject()
	 * This function loads a given row from the SearchTerms table into a SearchTerms object.  
	 ********************************************************************************
	*/
	function loadSearchTermsObject(&$oSearchTerms, &$row)
	{

		$oSearchTerms->nSearchTermsId = $row['SEARCH_TERMS_ID'];
		$oSearchTerms->nWebsite = $row['WEBSITE'];
		$oSearchTerms->sPageName = $row['PAGE_NAME'];
		$oSearchTerms->sPageURL = $row['PAGE_URL'];
		$oSearchTerms->sSearchTerms = $row['SEARCH_TERMS'];
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
		$this->sPageName = mysqli_real_escape_string($this->DB->dbConnection, $this->sPageName);
		$this->sPageURL = mysqli_real_escape_string($this->DB->dbConnection, $this->sPageURL);
		$this->sSearchTerms = mysqli_real_escape_string($this->DB->dbConnection, $this->sSearchTerms);
	}
}
