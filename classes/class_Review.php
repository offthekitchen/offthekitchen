<?php
/*
*******************************************************************
class_Review.php
This PHP file defines Review object class
NOTES
Date        Change
-------------------------------------------------------------
2017-09-06	Added Internal Review Flag
*******************************************************************
*/

//include DB Class		
include_once(CLASS_DIR . "/class_DB.php");

//include Error Class
include_once(CLASS_DIR . "/class_Error.php");

class Review
{

	var $DB;
	var $sqlResult;

	//Review Table Values
	var $nReviewID = 0;
	var $sReviewText = NULL;
	var $sReviewExcerpt = NULL;
	var $sAuthor = NULL;
	var $sSource = NULL;
	var $dtReviewDate = NULL;
	var $sReviewURL = NULL;
	var $sReviewAuthor = NULL;
	var $sReviewSource = NULL;
	var $bInternalReviewUrl = FALSE;
	var $nArtistID = 0;
	var $nCDID = 0;
	var $nSongID = 0;
	var $nRating = 0;
	var $bPerformanceRelated = FALSE;
	var $bGeneral = FALSE;
	var $dtLastUpdate = NULL;
	var $sOrderBy = NULL;
	var $bFuzzyNameSearch = false;

	var $sErrorMessage = "";

	/*****************************
	 * Arrays of Related Objects *
	 *****************************/
	//Review Records matching search criteria
	var $aReviewRecords = array();
	//Review Errors
	var $aReviewErrors = array();

	//Constructor
	function __construct()
	{
		$this->DB = new Database();
	}


	/*
	 ********************************************************************************
	 * getReview()
	 * 
	 * This function retrieves Review data based on data in the properites and
	 * returns an array of Review Objects
	 ********************************************************************************
	 */
	function getReview()
	{

		$this->aReviewRecords = array();

		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the Review)
		$sql = "SELECT REVIEW.* ";
		$sql .= "  FROM REVIEW ";
		$sql .= " WHERE 1=1 ";
		$this->buildWhereClause($sql);

		//DEBUG
//echo "SQL={$sql}<BR>";

		$result = NULL;

		if (!$this->executeSQL($sql)) {
			error_log('REV010 - FAILED TO EXECUTE SQL: ' . $sql);
			return -1;
		} else {

			$iReviewCount = 0;

			while ($row = mysqli_fetch_array($this->sqlResult)) {

				$oNextReview = new Review();

				//Load each product into a Review Object
				$this->loadReviewObject($oNextReview, $row);

				//Then add the object to the array of found products
				$this->aReviewRecords[$iReviewCount] = $oNextReview;

				$iReviewCount++;

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

		//If a specific Review ID is being searched, match on the ID
		if ($this->nReviewID > 0) {
			$sql .= " AND REVIEW_ID = {$this->nReviewID}";
		} else {
			//Otherwise build an SQL statement based on the values in the properties

			// ***************
			// * REVIEW NAME *
			// ***************
			if (!empty($this->sReviewName)) {
				$sql .= " AND (REVIEW_TEXT LIKE '%{$this->sReviewText}%'";
				$sql .= " OR REVIEW_EXCERPT LIKE '%{$this->sReviewExcerpt}%')";
			}

			// *****************
			// * REVIEW AUTHOR *
			// *****************
			if (!empty($this->sReviewAuthor)) {
				$sql .= " AND REVIEW_AUTHOR = '{$this->sReviewAuthor}'";
			}

			// *****************
			// * REVIEW SOURCE *
			// *****************
			if (!empty($this->sReviewSource)) {
				$sql .= " AND REVIEW_SOURCE = '{$this->sReviewSource}'";
			}

			// ****************
			// * REVIEW DATE *
			// ****************
			if (!empty($this->dtReviewDate)) {
				$sql .= " AND REVIEW_DATE LIKE '%{$this->ctReviewDate}%'";
			}

			// **************
			// * REVIEW URL *
			// **************
			if (!empty($this->sReviewURL)) {
				$sql .= " AND REVIEW_URL = '{$this->sReviewURL}'";
			}


			// ***********************
			// * INTERNAL REVIEW URL *
			// ***********************
			if ($this->bInternalReviewUrl) {
				$sql .= " AND INTERNAL_REVIEW_URL = {$this->bInternalReviewUrl}";
			}

			// *************
			// * ARTIST_ID *
			// *************
			if (!empty($this->nArtistID)) {
				$sql .= " AND ARTIST_ID = {$this->nArtistID}";
			}

			// *********
			// * CD_ID *
			// *********
			if (!empty($this->nCDID)) {
				$sql .= " AND CD_ID = {$this->nCDID}";
			}

			// ***********************
			// * PERFORMANCE RELATED *
			// ***********************
			if ($this->bPerformanceRelated) {
				$sql .= " AND PERFORMANCE_RELATED = {$this->bPerformanceRelated}";
			}

			// ***********
			// * GENERAL *
			// ***********
			if ($this->bGeneral) {
				$sql .= " AND GENERAL = {$this->bGeneral}";
			}

			// ***********
			// * SONG_ID *
			// ***********
			if (!empty($this->nSongID)) {
				$sql .= " AND SONG_ID = {$this->nSongID}";
			}

			// **********
			// * RATING *
			// **********
			if (!empty($this->nRating)) {
				$sql .= " AND RATING = {$this->nRating}";
			}


			// ************
			// * ORDER BY *
			// ************
			//ORDER BY
			switch ($this->sOrderBy) {
				case DATE_ORDER:
					$sql .= " ORDER BY REVIEW_DATE";
					break;

				default:
					$sql .= " ORDER BY RATING ASC";
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

		//DEBUG
		//echo "SQL={$sql}<BR>";

		//Open the DB
		if (!$this->DB->openDB()) {
			$this->sErrorMessage = "REV02A ERROR - FAILED TO OPEN DB: {$this->DB->dbError}";
			return false;
		}

		try {
			//Execute the SQL		
			$this->sqlResult = mysqli_query($this->DB->dbConnection, $sql);
		} catch (Exception $e) {
			// Handle the exception
			error_log('REV02BA ERROR - FAILED TO EXECUTE SQL: ' . $e->getMessage() . ' - SQL: ' . $sql);
		} finally {
			if (empty($this->sqlResult)) {
				$this->sErrorMessage = 'REV02C ERROR - EMPTY RESULT: ' . mysqli_error($this->DB->dbConnection) . ' - SQL: ' . $sql;
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
	 * Insert Review()
	 * 
	 * This function inserts a Review record 
	 ********************************************************************************
	 */
	function insertReview()
	{
		//Open the DB
		if (!$this->DB->openDB()) {
			$this->sErrorMessage = "REVIEW003 - FAILED TO OPEN DB: ";
			return FALSE;
		}

		$this->escapeSpecialChars();

		$sql = " INSERT INTO REVIEW";
		$sql .= " (REVIEW_TEXT,";
		$sql .= "  REVIEW_EXCERPT,";
		$sql .= "  REVIEW_AUTHOR,";
		$sql .= "  REVIEW_SOURCE,";
		$sql .= "  REVIEW_DATE,";
		$sql .= "  REVIEW_URL,";
		$sql .= "  INTERNAL_REVIEW_URL,";
		$sql .= "  ARTIST_ID,";
		$sql .= "  CD_ID,";
		$sql .= "  SONG_ID,";
		$sql .= "  RATING,";
		$sql .= "  PERFORMANCE_RELATED,";
		$sql .= "  GENERAL,";
		$sql .= "  LAST_UPDATE)";
		$sql .= " VALUES (";
		$sql .= "'{$this->sReviewText}',";
		$sql .= "'{$this->sReviewExcerpt}',";
		$sql .= "'{$this->sReviewAuthor}',";
		$sql .= "'{$this->sReviewSource}',";
		$sql .= "'{$this->dtReviewDate}',";
		$sql .= "'{$this->sReviewURL}',";
		if ($this->bInternalReviewUrl) {
			$sql .= "TRUE,";
		} else {
			$sql .= "FALSE,";
		}
		if (empty($this->nArtistID)) {
			$sql .= "NULL,";
		} else {
			$sql .= "{$this->nArtistID},";
		}

		if (empty($this->nCDID)) {
			$sql .= "NULL,";
		} else {
			$sql .= "{$this->nCDID},";
		}

		if (empty($this->nSongID)) {
			$sql .= "NULL,";
		} else {
			$sql .= "{$this->nSongID},";
		}

		if (empty($this->nRating)) {
			$sql .= "NULL,";
		} else {
			$sql .= "{$this->nRating},";
		}

		if ($this->bPerformanceRelated) {
			$sql .= "TRUE,";
		} else {
			$sql .= "FALSE,";
		}

		if ($this->bGeneral) {
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

			$this->sErrorMessage = "REVIEW004 - FAILED TO INSERT REVIEW RECORD - " . mysqli_error($this->DB->dbConnection);
			return FALSE;
		} else {
			$this->nReviewID = mysqli_insert_id($this->DB->dbConnection);
			return TRUE;
		}


	}


	/*
	 ********************************************************************************
	 * updateReview()
	 * 
	 * This Updates a Review record
	 ********************************************************************************
	 */
	function updateReview()
	{

		if ($this->nReviewID > 0) {

			//Open the DB
			if (!$this->DB->openDB()) {
				$this->sErrorMessage = "REVIEW005 - FAILED TO OPEN DB: ";
				return FALSE;
			}

			$this->escapeSpecialChars();

			$sql = " UPDATE REVIEW ";
			$sql .= "    SET REVIEW_TEXT = '{$this->sReviewText}'";
			$sql .= "       ,REVIEW_EXCERPT = '{$this->sReviewExcerpt}'";
			$sql .= "       ,REVIEW_AUTHOR = '{$this->sReviewAuthor}'";
			$sql .= "       ,REVIEW_SOURCE = '{$this->sReviewSource}'";
			$sql .= "       ,REVIEW_DATE = '{$this->dtReviewDate}'";
			$sql .= "       ,REVIEW_URL = '{$this->sReviewURL}'";
			if ($this->bInternalReviewUrl) {
				$sql .= ", INTERNAL_REVIEW_URL = TRUE ";
			} else {
				$sql .= ", INTERNAL_REVIEW_URL = FALSE ";
			}
			if (empty($this->nArtistID)) {
				$sql .= "       ,ARTIST_ID = NULL";
			} else {
				$sql .= "       ,ARTIST_ID = {$this->nArtistID}";
			}
			if (empty($this->nCDID)) {
				$sql .= "       ,CD_ID = NULL";
			} else {
				$sql .= "       ,CD_ID = {$this->nCDID}";
			}
			if (empty($this->nSongID)) {
				$sql .= "       ,SONG_ID = NULL";
			} else {
				$sql .= "       ,SONG_ID = {$this->nSongID}";
			}
			if (empty($this->nRating)) {
				$sql .= "       ,RATING = NULL";
			} else {
				$sql .= "       ,RATING = {$this->nRating}";
			}
			if ($this->bPerformanceRelated) {
				$sql .= ", PERFORMANCE_RELATED = TRUE ";
			} else {
				$sql .= ", PERFORMANCE_RELATED = FALSE ";
			}
			if ($this->bGeneral) {
				$sql .= ", GENERAL = TRUE ";
			} else {
				$sql .= ", GENERAL = FALSE ";
			}
			$sql .= "       ,LAST_UPDATE = SYSDATE()";
			$sql .= "  WHERE REVIEW_ID  = {$this->nReviewID}";


			//DEBUG
//echo "SQL={$sql}<BR>";

			//Execute the SQL		
			$result = mysqli_query($this->DB->dbConnection, $sql);

			//SQL Error
			if (!$result) {

				$this->sErrorMessage = "REVIEW006 - FAILED TO UPDATE REVIEW: " . mysqli_error($this->DB->dbConnection);
				return FALSE;
			} else {
				return TRUE;
			}

		} else {
			$this->sErrorMessage = "REVIEW010 - NO REVIEW_ID SPECIFIED ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deleteReview()
	 * 
	 * This function deletes a Review record
	 ********************************************************************************
	 */
	function deleteReview()
	{
		if ($this->nReviewID > 0) {
			$sql = " DELETE FROM REVIEW";
			$sql .= " WHERE REVIEW_ID = {$this->nReviewID}";

			//DEBUG
//echo "SQL={$sql}<BR>";

			//Open the DB
			if (!$this->DB->openDB()) {
				$this->sErrorMessage = "REVIEW007 - FAILED TO OPEN DB: ";
				return FALSE;
			}

			//Execute the SQL		
			$result = mysqli_query($this->DB->dbConnection, $sql);

			//SQL Error
			if (!$result) {
				$this->sErrorMessage = "REVIEW008 - FAILED TO DELETE Review: " . mysqli_error($this->DB->dbConnection);
				return FALSE;
			} else {
				return TRUE;
			}

		} else {
			$this->sErrorMessage = "REVIEW009 - CAN NOT DELETE Review BECAUSE NO Review ID SUPPLIED. ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * loadReviewObject()
	 * This function loads a given row from the Review table into a Review object.  
	 ********************************************************************************
	 */
	function loadReviewObject(&$oReview, &$row)
	{
		$oReview->nReviewID = $row['REVIEW_ID'];
		$oReview->sReviewText = $row['REVIEW_TEXT'];
		$oReview->sReviewExcerpt = $row['REVIEW_EXCERPT'];
		$oReview->sReviewAuthor = $row['REVIEW_AUTHOR'];
		$oReview->sReviewSource = $row['REVIEW_SOURCE'];
		$oReview->dtReviewDate = $row['REVIEW_DATE'];
		$oReview->sReviewURL = $row['REVIEW_URL'];
		$oReview->bInternalReviewUrl = $row['INTERNAL_REVIEW_URL'];
		$oReview->nArtistID = $row['ARTIST_ID'];
		$oReview->nCDID = $row['CD_ID'];
		$oReview->nSongID = $row['SONG_ID'];
		$oReview->nRating = $row['RATING'];
		$oReview->bPerformanceRelated = $row['PERFORMANCE_RELATED'];
		$oReview->bGeneral = $row['GENERAL'];
		$oReview->dtLastUpdate = $row['LAST_UPDATE'];

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

		$this->sReviewText = mysqli_real_escape_string($this->DB->dbConnection, $this->sReviewText);
		$this->sReviewExcerpt = mysqli_real_escape_string($this->DB->dbConnection, $this->sReviewExcerpt);
		$this->sReviewAuthor = mysqli_real_escape_string($this->DB->dbConnection, $this->sReviewAuthor);
		$this->sReviewSource = mysqli_real_escape_string($this->DB->dbConnection, $this->sReviewSource);

	}


}

?>