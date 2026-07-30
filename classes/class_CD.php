<?php
/*
*******************************************************************
class_CD.php
This PHP file defines CD object class
NOTES
Date        Change
-------------------------------------------------------------
2015-12-02	Refactored and added Release Date, Run Time and UPC
2015-12-18 	Removed unneeded rootpath and includes
2016-07-04	Added Thumbnail, Purcahse Link, Short Name and Desc
2016-09-15	Added order by options to where clause
2025-11-16	Corrected mySQL issues
*******************************************************************
*/

//include DB Class		
include_once(CLASS_DIR . "/class_DB.php");

//include Error Class
include_once(CLASS_DIR . "/class_Error.php");

//include Song Class
include_once(CLASS_DIR . "/class_Song.php");

class CD
{

	var $DB;
	var $sqlResult;

	//CD Table Values
	var $nCDID = 0;
	var $sCDName = NULL;
	var $sCDShortName = NULL;
	var $sCDImage = NULL;
	var $sCDThumbnail = NULL;
	var $sCDDescription = NULL;
	var $dtReleaseDate = NULL;
	var $sUPC = NULL;
	var $sRunTime = NULL;
	var $nArtistID = 0;
	var $sPurchaseLink = NULL;
	var $dtLastUpdate = NULL;

	//Booleans used in Searching
	var $bFuzzyNameSearch = FALSE;
	var $bIncludeSingles = FALSE;
	var $sOrderBy = NULL;

	var $sErrorMessage = "";

	//Songs assigned to this CD
	var $oCDSongs = NULL;

	/*****************************
	 * Arrays of Related Objects *
	 *****************************/
	//CD Records matching search criteria
	var $aCDRecords = array();
	//CD Errors
	var $aCDErrors = array();

	//Constructor
	function __construct()
	{
		$this->DB = new Database();
	}


	/*
	 ********************************************************************************
	 * getCD()
	 * 
	 * This function retrieves CD data based on data in the properites and
	 * returns an array of CD Objects
	 ********************************************************************************
	 */
	function getCD()
	{

		$this->aCDRecords = [];

		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the CD)
		$sql = "SELECT CD.* ";
		$sql .= "  FROM CD ";
		$sql .= " WHERE 1=1 ";
		$this->buildWhereClause($sql);

//DEBUG
//echo "SQL={$sql}<BR>";

		if (!$this->executeSQL($sql)) {
			return -1;
		} else {

			$iCDCount = 0;

			while ($row = mysqli_fetch_array($this->sqlResult)) {

				$oNextCD = new CD();

				//Load each product into a CD Object
				$this->loadCDObject($oNextCD, $row);

				//Then add the object to the array of found products
				$this->aCDRecords[$iCDCount] = $oNextCD;

				$iCDCount++;

			}

			$this->DB->closeDB();
			return TRUE;

		}

	}

	/*
	 ********************************************************************************
	 * getCDSongs()
	 * 
	 * This function loads the array of Song objects associated with this CD 
	 ********************************************************************************
	 */
	function getCDSongs()
	{
		$this->oCDSongs = new Song();
		$this->oCDSongs->nCDID = $this->nCDID;
		if (!$this->oCDSongs->getSong()) {
			$this->sErrorMessage = "CD011 - Failed to retrieve Songs: " . $this->oCDSongs->sErrorMessage;
			return false;
		} else {
			return true;
		}
	}

	/*
	 ********************************************************************************
	 * getReviews()
	 * This function returns a Review object loaded with reviews for this CD  
	 ********************************************************************************
	 */
	function getReviews()
	{
		$oReviews = new Review();
		$oReviews->nCDID = $this->nCDID;
		if (!$oReviews->getReview()) {
			//TODO: ERROR
		} else if (sizeof($oReviews->aReviewRecords) < 1) {
			$oReviews->nReviewID = DEFAULT_REVIEW_ID;
			if (!$oReviews->getReview()) {
				//TODO: ERROR
			}
		}

		return $oReviews;
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

		//If a specific CD ID is being searched, match on the ID
		if ($this->nCDID > 0) {
			$sql .= " AND CD_ID = {$this->nCDID}";
		} else {
			//Otherwise build an SQL statement based on the values in the properties

			// ****************
			// * CD NAME *
			// ****************
			if (!empty($this->sCDName)) {
				//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
				if ($this->bFuzzyNameSearch) {
					$sql .= " AND CD_NAME LIKE '%{$this->sCDName}%'";
				} else {
					$sql .= " AND CD_NAME = '{$this->sCDName}'";
				}
			}

			// *****************
			// * CD SHORT NAME *
			// *****************
			if (!empty($this->sCDShortName)) {
				$sql .= " AND CD_SHORT_NAME = '{$this->sCDShortName}'";
			}

			// ******************
			// * CD DESCRIPTION *
			// ******************
			if (!empty($this->sCDDescription)) {
				$sql .= " AND CD_DESCRIPTION = '{$this->sCDDescription}'";
			}

			// ************
			// * CD IMAGE *
			// ************
			if (!empty($this->sCDImage)) {
				$sql .= " AND CD_IMAGE = '{$this->sCDImage}'";
			}

			// ****************
			// * CD THUMBNAIL *
			// ****************
			if (!empty($this->sCDThumbnail)) {
				$sql .= " AND CD_THUMBNAIL = '{$this->sCDThumbnail}'";
			}

			// ****************
			// * RELEASE DATE *
			// ****************
			if (!empty($this->dtReleaseDate)) {
				$sql .= " AND RELEASE_DATE LIKE '%{$this->dtReleaseDate}%'";
			}

			// ************
			// * RUN_TIME *
			// ************
			if (!empty($this->sRunTime)) {
				$sql .= " AND RUN_TIME = '{$this->sRunTime}'";
			}

			// *******
			// * UPC *
			// *******
			if (!empty($this->sUPC)) {
				$sql .= " AND UPC LIKE '%{$this->sUPC}%'";
			}

			// *************
			// * ARTIST_ID *
			// *************
			if (!empty($this->nArtistID)) {
				$sql .= " AND ARTIST_ID = {$this->nArtistID}";
			}

			// *****************
			// * PURCHASE LINK *
			// *****************
			if (!empty($this->sPurchaseLink)) {
				$sql .= " AND PURCHASE_LINK = '{$this->sPurchaseLink}'";
			}

			// *******************
			// * INCLUDE SINGLES *
			// *******************
			if (!$this->bIncludeSingles) {
				$sql .= " AND CD_ID <> " . SINGLES_CD_ID;
			}

			// ************
			// * ORDER BY *
			// ************
			switch ($this->sOrderBy) {
				case NAME_ORDER:
					$sql .= " ORDER BY CD_NAME";
					break;

				case DATE_ORDER:
					$sql .= " ORDER BY RELEASE_DATE DESC";
					break;

				case UPDATE_ORDER:
					$sql .= " ORDER BY LAST_UPDATE DESC";
					break;

				default:
					$sql .= " ORDER BY RELEASE_DATE DESC";
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
	function executeSQL(&$sql)
	{
		//Open the DB
		if (!$this->DB->openDB()) {
			$this->sErrorMessage = "CD001 - FAILED TO OPEN DB: {$this->DB->dbError}";
			return false;
		}

		try {
			//Execute the SQL		
			$this->sqlResult = mysqli_query($this->DB->dbConnection, $sql);
		} catch (Exception $e) {
			// Handle the exception
			error_log('CD01A ERROR - FAILED TO EXECUTE SQL' . $e->getMessage() . ' - SQL: ' . $sql);
			return false;
		} finally {
			if (empty($this->sqlResult)) {
				$this->sErrorMessage = 'CD01B ERROR: - ' . mysqli_error($this->DB->dbConnection) . ' SQL: ' . $sql;
				error_log($this->sErrorMessage);
				$this->DB->closeDB();
				return false;
			} else {
				return true;
			}
		}
	}

	/*
	 ********************************************************************************
	 * Insert CD()
	 * 
	 * This function inserts a CD record 
	 ********************************************************************************
	 */
	function insertCD()
	{
		//Open the DB
		if (!$this->DB->openDB()) {
			$this->sErrorMessage = "CD003 - FAILED TO OPEN DB: ";
			return FALSE;
		}

		$this->escapeSpecialChars();

		$sql = " INSERT INTO CD";
		$sql .= " (CD_NAME,";
		$sql .= "  CD_SHORT_NAME,";
		$sql .= "  CD_DESCRIPTION,";
		$sql .= "  CD_IMAGE,";
		$sql .= "  CD_THUMBNAIL,";
		$sql .= "  RELEASE_DATE,";
		$sql .= "  RUN_TIME,";
		$sql .= "  UPC,";
		$sql .= "  ARTIST_ID,";
		$sql .= "  PURCHASE_LINK,";
		$sql .= "  LAST_UPDATE)";
		$sql .= " VALUES (";
		$sql .= "'{$this->sCDName}',";
		$sql .= "'{$this->sCDShortName}',";
		$sql .= "'{$this->sCDDescription}',";
		$sql .= "'{$this->sCDImage}',";
		$sql .= "'{$this->sCDThumbnail}',";
		$sql .= "'{$this->dtReleaseDate}',";
		$sql .= "'{$this->sRunTime}',";
		$sql .= "'{$this->sUPC}',";
		$sql .= "{$this->nArtistID},";
		$sql .= "'{$this->sPurchaseLink}',";
		$sql .= "SYSDATE()";
		$sql .= ")";

		//DEBUG
//echo "SQL={$sql}<BR>";

		//Execute the SQL		
		$result = mysqli_query($this->DB->dbConnection, $sql);

		//SQL Error
		if (!$result) {

			$this->sErrorMessage = "CD004 - FAILED TO INSERT CD RECORD - " . mysqli_error($this->DB->dbConnection);
			return FALSE;
		} else {
			$this->nCDID = mysqli_insert_id($this->DB->dbConnection);
			return TRUE;
		}


	}


	/*
	 ********************************************************************************
	 * updateCD()
	 * 
	 * This Updates a CD record
	 ********************************************************************************
	 */
	function updateCD()
	{

		if ($this->nCDID > 0) {

			//Open the DB
			if (!$this->DB->openDB()) {
				$this->sErrorMessage = "CD005 - FAILED TO OPEN DB: ";
				return FALSE;
			}

			$this->escapeSpecialChars();

			$sql = " UPDATE CD ";
			$sql .= "    SET CD_NAME = '{$this->sCDName}'";
			$sql .= "       ,CD_IMAGE = '{$this->sCDImage}'";
			$sql .= "       ,CD_SHORT_NAME = '{$this->sCDShortName}'";
			$sql .= "       ,CD_DESCRIPTION = '{$this->sCDDescription}'";
			$sql .= "       ,CD_THUMBNAIL = '{$this->sCDThumbnail}'";
			$sql .= "       ,RELEASE_DATE = '{$this->dtReleaseDate}'";
			$sql .= "       ,RUN_TIME = '{$this->sRunTime}'";
			$sql .= "       ,UPC = '{$this->sUPC}'";
			$sql .= "       ,ARTIST_ID = {$this->nArtistID}";
			$sql .= "       ,PURCHASE_LINK = '{$this->sPurchaseLink}'";
			$sql .= "       ,LAST_UPDATE = SYSDATE()";
			$sql .= "  WHERE CD_ID  = {$this->nCDID}";

			//DEBUG
//echo "SQL={$sql}<BR>";


			//Execute the SQL		
			$result = mysqli_query($this->DB->dbConnection, $sql);

			//SQL Error
			if (!$result) {

				$this->sErrorMessage = "CD006 - FAILED TO UPDATE CD: " . mysqli_error($this->DB->dbConnection);
				return FALSE;
			} else {
				return TRUE;
			}

		} else {
			$this->sErrorMessage = "CD010 - NO CD_ID SPECIFIED ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deleteCD()
	 * 
	 * This function deletes a CD record
	 ********************************************************************************
	 */
	function deleteCD()
	{
		if ($this->nCDID > 0) {
			//Look for Associated Songs
			if ($this->getCDSongs()) {
				if (sizeof($this->oCDSongs->aSongRecords) > 0) {
					$this->sErrorMessage = "CD0013 - Can not delete CD because it has ";
					$this->sErrorMessage .= "<A HREF='" . ADMIN_DIR . "/SongMaintenance.php?CD_ID=" . $this->nCDID;
					$this->sErrorMessage .= "'>" . sizeof($this->oCDSongs->aSongRecords) . " songs.</A>.";
					return FALSE;
				}

			} else {
				$this->sErrorMessage = "CD0014 - Failed to retrieve Songs for this CD: " . $this->oCDSongs->sErrorMessage;
				return FALSE;
			}

			$sql = " DELETE FROM CD";
			$sql .= " WHERE CD_ID = {$this->nCDID}";

			//DEBUG
//echo "SQL={$sql}<BR>";

			//Open the DB
			if (!$this->DB->openDB()) {
				$this->sErrorMessage = "CD007 - FAILED TO OPEN DB: ";
				return FALSE;
			}

			//Execute the SQL		
			$result = mysqli_query($this->DB->dbConnection, $sql);

			//SQL Error
			if (!$result) {
				$this->sErrorMessage = "CD008 - FAILED TO DELETE CD: " . mysqli_error($this->DB->dbConnection);
				return FALSE;
			} else {
				return TRUE;
			}

		} else {
			$this->sErrorMessage = "CD009 - CAN NOT DELETE CD BECAUSE NO CD ID SUPPLIED. ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * loadCDObject()
	 * This function loads a given row from the CD table into a CD object.  
	 ********************************************************************************
	 */
	function loadCDObject(&$oCD, &$row)
	{

		$oCD->nCDID = $row['CD_ID'];
		$oCD->sCDName = $row['CD_NAME'];
		$oCD->sCDShortName = $row['CD_SHORT_NAME'];
		$oCD->sCDDescription = $row['CD_DESCRIPTION'];
		$oCD->sCDImage = $row['CD_IMAGE'];
		$oCD->sCDThumbnail = $row['CD_THUMBNAIL'];
		$oCD->dtReleaseDate = $row['RELEASE_DATE'];
		$oCD->sRunTime = $row['RUN_TIME'];
		$oCD->sUPC = $row['UPC'];
		$oCD->nArtistID = $row['ARTIST_ID'];
		$oCD->sPurchaseLink = $row['PURCHASE_LINK'];
		$oCD->dtLastUpdate = $row['LAST_UPDATE'];

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

		$this->sCDName = mysqli_real_escape_string($this->DB->dbConnection, $this->sCDName);
		$this->sCDDescription = mysqli_real_escape_string($this->DB->dbConnection, $this->sCDDescription);

	}


}

?>