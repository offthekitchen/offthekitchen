<?php
/*
*******************************************************************
class_PerformanceSongs.php
This PHP file defines the PerformanceSongs object class
NOTES
Date        Change
-------------------------------------------------------------
2020-04-27	Created
2020-11-07	Added Popular flag
2023-11-24	Added Original flag
*******************************************************************
*/


//include DB Class		
include_once(CLASS_DIR . "/class_DB.php");


class PerformanceSongs
{

	var $DB;

	//PerformanceSongs Table Values
	var $nPerformanceSongID = 0;
	var $sArtist = null;
	var $sTitle = null;
	var $sTuning = null;
	var $sCapo = null;
	var $sEffect = null;
	var $sNotes = null;
	var $nRating = null;
	var $nLowestRating = null;
	var $bTabs = null;
	var $bDemo = null;
	var $bLearned = null;
	var $bPopular = null;
	var $bClean = null;
	var $bOriginal = null;
	var $tEstimatedTime = null;
	var $dtLastUpdate = null;

	//Booleans used in Searching
	var $bFuzzyTitleSearch = false;
	var $bFuzzyArtistSearch = false;
	var $bExcludeUke = false;
	var $bExcludePiano = false;
	var $sOrderBy = NULL;


	var $sErrorMessage = "";

	/*****************************
	 * Arrays of Related Objects *
	 *****************************/
	//PerformanceSongs Records matching search criteria
	var $aPerformanceSongsRecords = array();
	//PerformanceSongs Errors
	var $aPerformanceSongsErrors = array();

	//Constructor
	function __construct()
	{
		$this->DB = new Database();
	}


	/*
	 ********************************************************************************
	 * getPerformanceSongs()
	 * 
	 * This function retrieves PerformanceSongs data based on data in the properites and
	 * returns an array of PerformanceSongs Objects
	 ********************************************************************************
	 */
	function getPerformanceSongs()
	{

		$this->aPerformanceSongsRecords = array();

		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the PerformanceSongs type)
		$sql = "SELECT * ";
		$sql .= "  FROM PERFORMANCE_SONGS ";
		$sql .= " WHERE 1=1";

		$this->buildWhereClause($sql);

		//DEBUG
//echo "SQL=". $sql . "<BR>";

		$result = NULL;

		if (!$this->executeSQL($sql, $result)) {
			return -1;
		} else {

			$iPerformanceSongsCount = 0;

			while ($row = mysqli_fetch_array($result)) {

				$oNextPerformanceSongs = new PerformanceSongs();

				//Load each product into a PerformanceSongs Object
				$this->loadPerformanceSongsObject($oNextPerformanceSongs, $row);

				//Then add the object to the array of found products
				$this->aPerformanceSongsRecords[$iPerformanceSongsCount] = $oNextPerformanceSongs;

				$iPerformanceSongsCount++;

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
		//If a specific Performnance Songs ID is being searched, match on the ID
		if ($this->nPerformanceSongID > 0) {
			$sql .= " AND PERFORMANCE_SONGS.PERFORMANCE_SONG_ID = {$this->nPerformanceSongID}";
		} else {
			//Otherwise build an SQL statement based on the values in the properties

			// *********
			// * TITLE *
			// *********
			if (!empty($this->sTitle)) {
				//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
				if ($this->bFuzzyTitleSearch) {
					$sql .= " AND TITLE LIKE '%{$this->sTitle}%'";
				} else {
					$sql .= " AND TITLE = '{$this->sTitle}'";
				}
			}

			// **********
			// * ARTIST *
			// **********
			if (!empty($this->sArtist)) {
				//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
				if ($this->bFuzzyArtistSearch) {
					$sql .= " AND ARTIST LIKE '%{$this->sArtist}%'";
				} else {
					$sql .= " AND ARTIST = '{$this->sArtist}'";
				}
			}

			// **********
			// * TUNING *
			// **********
			if (!empty($this->sTuning)) {
				$sql .= " AND TUNING = '{$this->sTuning}'";
			}

			// ***********
			// * LEARNED *
			// ***********
			if (!is_null($this->bLearned)) {
				$sql .= " AND LEARNED = {$this->bLearned}";
			}

			// ********
			// * TABS *
			// ********
			if (!empty($this->bTabs)) {
				$sql .= " AND TABS = '{$this->bTabs}'";
			}

			// ********
			// * DEMO *
			// ********
			if (!empty($this->bDemo)) {
				$sql .= " AND DEMO = '{$this->bDemo}'";
			}

			// ***********
			// * CLEAN *
			// ***********
			if (!is_null($this->bClean)) {
				$sql .= " AND CLEAN = {$this->bClean}";
			}

			// ***********
			// * POPULAR *
			// ***********
			if (!is_null($this->bPopular)) {
				$sql .= " AND POPULAR = {$this->bPopular}";
			}

			// ************
			// * ORIGINAL *
			// ************
			if (!is_null($this->bOriginal)) {
				$sql .= " AND ORIGINAL = {$this->bOriginal}";
			}

			// ********
			// * CAPO *
			// ********
			if (!empty($this->sCapo)) {
				$sql .= " AND CAPO = {$this->sCapo}";
			}

			// **********
			// * RATING *
			// **********
			if ($this->nRating > 0) {
				$sql .= " AND RATING = {$this->nRating}";
			}

			// *****************
			// * LOWEST RATING *
			// *****************
			if ($this->nLowestRating > 0) {
				$sql .= " AND RATING <= {$this->nLowestRating}";
			}

			// *********************
			// * EXCLUDE UKE SONGS *
			// *********************
			if ($this->bExcludeUke) {
				$sql .= " AND TUNING <> 'UKULELE'";
			}

			// ***********************
			// * EXCLUDE PIANO SONGS *
			// ***********************
			if ($this->bExcludePiano) {
				$sql .= " AND TUNING <> 'PIANO'";
			}

			// ******************
			// * ESTIMATED_TIME *
			// ******************
			if (!empty($this->tEstimatedTime)) {
				$sql .= " AND ESTIMATED_TIME = '" . $this->tEstimatedTime . "'";
			}

			// *********
			// * NOTES *
			// *********
			if (!empty($this->sNotes)) {
				$sql .= " AND NOTES = '{$this->sNotes}'";
			}

			// ************
			// * ORDER BY *
			// ************
			//ALPHABETICAL BY NAME
			if ($this->sOrderBy = RATING_ORDER){
				$sql .= " ORDER BY PERFORMANCE_SONGS.RATING, PERFORMANCE_SONGS.TITLE";
			}
			else {
			$sql .= " ORDER BY PERFORMANCE_SONGS.TITLE";

			}
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
		if (!$this->DB->openDB()) {
			$this->sErrorMessage = "PFS015 - FAILED TO OPEN DB: " . $this->DB->dbError;
			return false;
		}

		//Execute the SQL		
		$result = mysqli_query($this->DB->dbConnection, $sql);

		if (!$result) {

			$this->sErrorMessage = "PFS016 - " . mysqli_error($this->DB->dbConnection);
			$this->DB->closeDB();

			return false;
		} else {
			return true;
		}
	}

	/*
	 ********************************************************************************
	 * InsertPerformanceSongs()
	 * 
	 * This function inserts a PerformanceSongs record 
	 ********************************************************************************
	 */
	function insertPerformanceSongs()
	{
		//Open the DB
		if (!$this->DB->openDB()) {
			$this->sErrorMessage = "PFS003 - FAILED TO OPEN DB: ";
			return FALSE;
		}

		$this->escapeSpecialChars();

		$sql = " INSERT INTO PERFORMANCE_SONGS";
		$sql .= " (TITLE,";
		$sql .= " ARTIST,";
		$sql .= " TUNING,";
		$sql .= " CAPO,";
		$sql .= " LEARNED,";
		$sql .= " TABS,";
		$sql .= " DEMO,";
		$sql .= " CLEAN,";
		$sql .= " POPULAR,";
		$sql .= " ORIGINAL,";
		$sql .= " RATING,";
		$sql .= " ESTIMATED_TIME,";
		$sql .= " EFFECT,";
		$sql .= " NOTES,";
		$sql .= " LAST_UPDATE)";

		$sql .= " VALUES (";
		$sql .= "'{$this->sTitle}',";
		$sql .= "'{$this->sArtist}',";
		$sql .= "'{$this->sTuning}',";
		$sql .= "'{$this->sCapo}',";

		if ($this->bLearned) {
			$sql .= "TRUE,";
		} else {
			$sql .= "FALSE,";
		}
		$sql .= "'{$this->bTabs}',";
		$sql .= "'{$this->bDemo}',";
		if ($this->bClean) {
			$sql .= "TRUE,";
		} else {
			$sql .= "FALSE,";
		}
		if ($this->bPopular) {
			$sql .= "TRUE,";
		} else {
			$sql .= "FALSE,";
		}
		if ($this->bOriginal) {
			$sql .= "TRUE,";
		} else {
			$sql .= "FALSE,";
		}
		if (is_numeric($this->nRating)) {
			$sql .= $this->nRating . ",";
		} else {
			$sql .= "NULL,";
		}
		$sql .= "'{$this->tEstimatedTime}',";
		$sql .= "'{$this->sEffect}',";
		$sql .= "'{$this->sNotes}',";
		$sql .= "SYSDATE()";
		$sql .= ")";

		//DEBUG
//echo "SQL={$sql}<BR>";

		//Execute the SQL		
		$result = mysqli_query($this->DB->dbConnection, $sql);

		//SQL Error
		if (!$result) {

			$this->sErrorMessage = "PFS004 - FAILED TO INSERT PerformanceSongs RECORD - " . mysqli_error($this->DB->dbConnection);
			return FALSE;
		} else {
			$this->nPerformanceSongID = mysqli_insert_id($this->DB->dbConnection);
			return TRUE;
		}

	}


	/*
	 ********************************************************************************
	 * updatePerformanceSongs()
	 * 
	 * This Updates a PerformanceSongs record
	 ********************************************************************************
	 */
	function updatePerformanceSongs()
	{

		if ($this->nPerformanceSongID > 0) {
			//Open the DB
			if (!$this->DB->openDB()) {
				$this->sErrorMessage = "PFS005 - FAILED TO OPEN DB: ";
				return FALSE;
			}

			$this->escapeSpecialChars();

			$sql = " UPDATE PERFORMANCE_SONGS ";
			$sql .= " SET  TITLE  = '{$this->sTitle}'";
			$sql .= "   ,  ARTIST = '{$this->sArtist}'";
			$sql .= "   ,  TUNING = '{$this->sTuning}'";
			$sql .= "   ,  CAPO = '{$this->sCapo}'";
			if ($this->bLearned) {
				$sql .= ", LEARNED = TRUE";
			} else {
				$sql .= ", LEARNED = FALSE";
			}
			$sql .= "   ,  TABS = '{$this->bTabs}'";
			$sql .= "   ,  DEMO = '{$this->bDemo}'";
			if ($this->bClean) {
				$sql .= ", CLEAN = TRUE";
			} else {
				$sql .= ", CLEAN = FALSE";
			}
			if ($this->bPopular) {
				$sql .= ", POPULAR = TRUE";
			} else {
				$sql .= ", POPULAR = FALSE";
			}
			if ($this->bOriginal) {
				$sql .= ", ORIGINAL = TRUE";
			} else {
				$sql .= ", ORIGINAL = FALSE";
			}
			$sql .= "   ,  RATING = {$this->nRating}";
			$sql .= "   ,  ESTIMATED_TIME = '{$this->tEstimatedTime}'";
			$sql .= "   ,  EFFECT = '{$this->sEffect}'";
			$sql .= "   ,  NOTES = '{$this->sNotes}'";
			$sql .= ", LAST_UPDATE = SYSDATE()";
			$sql .= " WHERE PERFORMANCE_SONG_ID  = {$this->nPerformanceSongID}";

			//DEBUG
//echo "SQL={$sql}<BR>";

			//Execute the SQL		
			$result = mysqli_query($this->DB->dbConnection, $sql);

			//SQL Error
			if (!$result) {

				$this->sErrorMessage = "PFS006 - FAILED TO UPDATE PerformanceSongs: " . mysqli_error($this->DB->dbConnection);
				return FALSE;
			} else {
				return TRUE;
			}

		} else {
			$this->sErrorMessage = "PFS010 - NO PerformanceSongs_ID SPECIFIED ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deletePerformanceSongs()
	 * 
	 * This function deletes a PerformanceSongs record
	 ********************************************************************************
	 */
	function deletePerformanceSongs()
	{
		if ($this->nPerformanceSongID > 0) {
			$sql = " DELETE FROM PERFORMANCE_SONGS";
			$sql .= " WHERE PERFORMANCE_SONG_ID = {$this->nPerformanceSongID}";

			//DEBUG
//echo "SQL={$sql}<BR>";

			//Open the DB
			if (!$this->DB->openDB()) {
				$this->sErrorMessage = "PFS007 - FAILED TO OPEN DB: ";
				return FALSE;
			}

			//Execute the SQL		
			$result = mysqli_query($this->DB->dbConnection, $sql);

			//SQL Error
			if (!$result) {
				$this->sErrorMessage = "PFS008 - FAILED TO DELETE PerformanceSongs: " . mysqli_error($this->DB->dbConnection);
				return FALSE;
			} else {
				return TRUE;
			}


		} else {
			$this->sErrorMessage = "PFS009 - CAN NOT DELETE PerformanceSongs BECAUSE NO PerformanceSongs ID SUPPLIED. ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * loadPerformanceSongsObject()
	 * This function loads a given row from the PERFORMANCE table into a Performance object.  
	 ********************************************************************************
	 */
	function loadPerformanceSongsObject(&$oPerformanceSongs, &$row)
	{

		$oPerformanceSongs->nPerformanceSongID = $row['PERFORMANCE_SONG_ID'];
		$oPerformanceSongs->sTitle = $row['TITLE'];
		$oPerformanceSongs->sArtist = $row['ARTIST'];
		$oPerformanceSongs->sTuning = $row['TUNING'];
		$oPerformanceSongs->sCapo = $row['CAPO'];
		$oPerformanceSongs->bLearned = $row['LEARNED'];
		$oPerformanceSongs->bTabs = $row['TABS'];
		$oPerformanceSongs->bDemo = $row['DEMO'];
		$oPerformanceSongs->bClean = $row['CLEAN'];
		$oPerformanceSongs->bPopular = $row['POPULAR'];
		$oPerformanceSongs->bOriginal = $row['ORIGINAL'];
		$oPerformanceSongs->nRating = $row['RATING'];
		$oPerformanceSongs->tEstimatedTime = $row['ESTIMATED_TIME'];
		$oPerformanceSongs->sEffect = $row['EFFECT'];
		$oPerformanceSongs->sNotes = $row['NOTES'];

		$oPerformanceSongs->dtLastUpdate = $row['LAST_UPDATE'];


	}

	/*
	 ********************************************************************************
	 * getPerformanceSongsErrors()
	 * 
	 * This function creates an array of PerformanceSongss that have errors 
	 ********************************************************************************
	 */
	function getPerformanceSongsErrors()
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
		$this->sTitle = mysqli_real_escape_string($this->DB->dbConnection, $this->sTitle);
		$this->sArtist = mysqli_real_escape_string($this->DB->dbConnection, $this->sArtist);
		$this->sTuning = mysqli_real_escape_string($this->DB->dbConnection, $this->sTuning);
		$this->sCapo = mysqli_real_escape_string($this->DB->dbConnection, $this->sCapo);
		$this->sEffect = mysqli_real_escape_string($this->DB->dbConnection, $this->sEffect);
		$this->sNotes = mysqli_real_escape_string($this->DB->dbConnection, $this->sNotes);
	}


}

?>