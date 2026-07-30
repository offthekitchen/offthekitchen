<?php
/*
*******************************************************************
class_Unavailable_Date.php
This PHP file defines the Unavailable Date object class
NOTES
Date        Change
-------------------------------------------------------------
2015-12-19	removed unneeded includes
*******************************************************************
*/
//include DB Class		
include_once(CLASS_DIR . "/class_DB.php");

//include Error Class
include_once(CLASS_DIR . "/class_Error.php");

class UnavailableDate
{

	var $DB;

	//PERFORMANCE Table Values
	var $nUnavailableID = 0;
	var $sReason = NULL;
	var $nYear = NULL;
	var $dtUnavailableDate = NULL;
	var $dtLastUpdate = NULL;

	var $sErrorMessage = "";

	//UnavailableDate Type Object for the UnavailableDate Type of this UnavailableDate
	var $thisUnavailableDate;

	//Date for Date Range
	var $dtUnavailableStartDate = null;
	var $dtUnavailableEndDate = null;

	var $bBookedDate = NULL;
	var $bFuzzyReasonSearch = false;


	/*****************************
	 * Arrays of Related Objects *
	 *****************************/
	//UNAVAILABLE_DATE Records matching search criteria
	var $aUnavailableDateRecords = array();
	//Unavailable Date Errors
	var $aUnavailableDateErrors = array();

	//Constructor
	function __construct()
	{
		$this->DB = new Database();
	}


	/*
	 ********************************************************************************
	 * getUnavailableDate()
	 * 
	 * This function retrieves Unavailable Date data based on data in the properites and
	 * returns an array of UnavailableDate Objects
	 ********************************************************************************
	 */
	function getUnavailableDate()
	{


		$nFoundRecordCount = 0;

		$nFieldCount = 0;
		$bWhereClause = false;

		$this->aUnavailableDateRecords = array();

		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the product type)
		$sql = "SELECT UNAVAILABLE_DATES.* ";
		$sql .= "  FROM UNAVAILABLE_DATES ";
		$sql .= " WHERE 1=1 ";


		//If a specific UnavailableDate ID is being searched, match on the ID
		if ($this->nUnavailableID > 0) {
			$sql .= " AND UNAVAILABLE_ID = {$this->nUnavailableID}";
		} else {
			//Otherwise build an SQL statement based on the values in the properties

			// **********
			// * REASON *
			// **********
			if (!empty($this->sReason)) {
				//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
				if ($this->bFuzzyReasonSearch) {
					$sql .= " AND REASON LIKE '%{$this->sReason}%'";
				} else {
					$sql .= " AND REASON = '{$this->sReason}'";
				}
			}


			// ********************
			// * UNAVAILABLE_DATE *
			// ********************
			if (!empty($this->dtUnavailableDate)) {
				$sql .= " AND UNAVAILABLE_DATE LIKE '{$this->dtUnavailableDate}%'";
			}

			// ********
			// * Year *
			// ********
			if (!empty($this->nYear)) {
				$sql .= " AND YEAR(UNAVAILABLE_DATE) = '{$this->nYear}%'";
			}

			// ***************
			// * BOOKED DATE *
			// ***************
			if (!is_null($this->bBookedDate)) {
				if ($this->bBookedDate) {
					$sql .= " AND EXISTS ( SELECT 1 FROM PERFORMANCE WHERE PERFORMANCE.PERFORMANCE_DATE = UNAVAILABLE_DATES.UNAVAILABLE_DATE ";
					$sql .= " AND PERFORMANCE.BOOKED_DATE IS NOT NULL)";
				} else {
					$sql .= " AND NOT EXISTS ( SELECT 1 FROM PERFORMANCE WHERE PERFORMANCE.PERFORMANCE_DATE = UNAVAILABLE_DATES.UNAVAILABLE_DATE)";
				}
			}

			// ************
			// * ORDER BY *
			// ************
			//ALPHABETICAL BY NAME
			$sql .= " ORDER BY UNAVAILABLE_DATE ASC";

		}
		//DEBUG
//echo "SQL={$sql}<BR>";

		//Open the DB
		if (!$this->DB->openDB()) {
			$this->sErrorMessage = "UDT001 - FAILED TO OPEN DB: {$this->DB->dbError}";
			return FALSE;
		}

		//Execute the SQL		
		$result = mysqli_query($this->DB->dbConnection, $sql);


		//SQL Error
		if (!$result) {

			$this->sErrorMessage = "UDT002 - " . mysqli_error($this->DB->dbConnection);
			$this->DB->closeDB();
			return FALSE;
		} else {

			$iUnavailableDateCount = 0;

			while ($row = mysqli_fetch_array($result)) {

				$oNextUnavailableDate = new UnavailableDate();

				//Load each product into a UnavailableDate Object
				$this->loadUnavailableDateObject($oNextUnavailableDate, $row);

				//Then add the object to the array of found products
				$this->aUnavailableDateRecords[$iUnavailableDateCount] = $oNextUnavailableDate;

				$iUnavailableDateCount++;

			}

			$this->DB->closeDB();
			return TRUE;

		}

	}


	/*
	 ********************************************************************************
	 * insertUnavailableDate()
	 * 
	 * This function inserts an UNAVAILABLE_DATE record 
	 ********************************************************************************
	 */
	function insertUnavailableDate()
	{
		$aUnavailableDates = array();

		if (!empty($this->dtUnavailableStartDate) && !empty($this->dtUnavailableEndDate)) {
			$this->getDateRange($aUnavailableDates);
		} else {
			$aUnavailableDates[0] = $this->dtUnavailableDate;
		}

		//Open the DB
		if (!$this->DB->openDB()) {
			$this->sErrorMessage = "UDT003 - FAILED TO OPEN DB: ";
			return FALSE;
		}

		foreach ($aUnavailableDates as $sUnavailableDate) {

			$this->escapeSpecialChars();

			$sql = " INSERT INTO UNAVAILABLE_DATES";
			$sql .= " (UNAVAILABLE_DATE,";
			$sql .= "  REASON,";
			$sql .= "  LAST_UPDATE)";

			$sql .= " VALUES (";
			$sql .= "'{$sUnavailableDate}',";
			$sql .= "'{$this->sReason}',";
			$sql .= "SYSDATE()";
			$sql .= ")";

			//DEBUG
//echo "SQL={$sql}<BR>";

			//Execute the SQL		
			$result = mysqli_query($this->DB->dbConnection, $sql);

			//SQL Error
			if (!$result) {

				$this->sErrorMessage = "UDT004 - FAILED TO INSERT UNAVAILABLE RECORD - " . mysqli_error($this->DB->dbConnection) . "\n";
				return FALSE;
			} else {
				$this->nUnavailableID = mysqli_insert_id($this->DB->dbConnection);
			}

		}

		return TRUE;
	}


	/*
	 ********************************************************************************
	 * updateUnavailableDate()
	 * 
	 * This Updates a PERFORMANCE record
	 ********************************************************************************
	 */
	function updateUnavailableDate()
	{

		if ($this->nUnavailableID > 0) {

			//Open the DB
			if (!$this->DB->openDB()) {
				$this->sErrorMessage = "UDT005 - FAILED TO OPEN DB: ";
				return FALSE;
			}

			$this->escapeSpecialChars();

			$sql = " UPDATE UNAVAILABLE_DATES ";
			$sql .= " SET UNAVAILABLE_DATE = '{$this->dtUnavailableDate}'";
			$sql .= ", REASON = '{$this->sReason}'";
			$sql .= ", LAST_UPDATE = SYSDATE()";
			$sql .= " WHERE UNAVAILABLE_ID  = {$this->nUnavailableID}";

			//DEBUG
//echo "SQL={$sql}<BR>";

			//Execute the SQL		
			$result = mysqli_query($this->DB->dbConnection, $sql);

			//SQL Error
			if (!$result) {

				$this->sErrorMessage = "UDT006 - FAILED TO UPDATE UNAVAILABLE_DATE: " . mysqli_error($this->DB->dbConnection);
				return FALSE;
			} else {
				return TRUE;
			}

		} else {
			$this->sErrorMessage = "UDT010 - NO UNAVAILABLE_ID SPECIFIED ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deleteUnavailableDate()
	 * 
	 * This function deletes a UnavailableDate record
	 ********************************************************************************
	 */
	function deleteUnavailableDate()
	{
		if ($this->nUnavailableID > 0) {

			$sql = " DELETE FROM UNAVAILABLE_DATES";
			$sql .= " WHERE UNAVAILABLE_ID = {$this->nUnavailableID}";

			//DEBUG
//echo "SQL={$sql}<BR>";

			//Open the DB
			if (!$this->DB->openDB()) {
				$this->sErrorMessage = "UDT007 - FAILED TO OPEN DB: ";
				return FALSE;
			}

			//Execute the SQL		
			$result = mysqli_query($this->DB->dbConnection, $sql);

			//SQL Error
			if (!$result) {
				$this->sErrorMessage = "UDT008 - FAILED TO DELETE UNAVAILABLE_DATE: " . mysqli_error($this->DB->dbConnection);
				return FALSE;
			} else {
				return TRUE;
			}

		} else {
			$this->sErrorMessage = "UDT009 - CAN NOT DELETE UNAVAILABLE_DATE BECAUSE NO UNAVAILABLE_ID SUPPLIED. ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * loadUnavailableDateObject()
	 * This function loads a given row from the PERFORMANCE table into a UnavailableDate object.  
	 ********************************************************************************
	 */
	function loadUnavailableDateObject(&$oUnavailableDate, &$row)
	{

		$oUnavailableDate->nUnavailableID = $row['UNAVAILABLE_ID'];
		$oUnavailableDate->dtUnavailableDate = $row['UNAVAILABLE_DATE'];
		$oUnavailableDate->sReason = $row['REASON'];
		$oUnavailableDate->dtLastUpdate = $row['LAST_UPDATE'];

	}

	/*
	 ********************************************************************************
	 * getDateRange()
	 * 
	 * This function determines all dates between a start and end date inclusively
	 * and fills an array of those dates 
	 ********************************************************************************
	 */
	function getDateRange(&$aUnavailableDates)
	{

		$nDateFrom = mktime(1, 0, 0, substr($this->dtUnavailableStartDate, 5, 2), substr($this->dtUnavailableStartDate, 8, 2), substr($this->dtUnavailableStartDate, 0, 4));
		$nDateTo = mktime(1, 0, 0, substr($this->dtUnavailableEndDate, 5, 2), substr($this->dtUnavailableEndDate, 8, 2), substr($this->dtUnavailableEndDate, 0, 4));

		if ($nDateTo >= $nDateFrom) {
			array_push($aUnavailableDates, date('Y-m-d', $nDateFrom)); // first entry
			while ($nDateFrom < $nDateTo) {
				$nDateFrom += 86400; // add 24 hours
				array_push($aUnavailableDates, date('Y-m-d', $nDateFrom));
			}
		}

	}

	/*
	 ********************************************************************************
	 * getUnavailableDateErrors()
	 * 
	 * This function creates an array of UnavailableDates that have errors 
	 ********************************************************************************
	 */
	function getUnavailableDateErrors()
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
		$this->sReason = mysqli_real_escape_string($this->DB->dbConnection, $this->sReason);

	}


}

?>