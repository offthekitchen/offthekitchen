<?php

//This include defines the relative path to the root directory from this sub-directory
include_once ("root.inc.php");

//include DB Class		
include_once (CLASS_DIR . "/class_DB.php");


class ThurdySongData
{

	var $DB;

    //Thurdy_Song_Data Table Values
    var $nThurdySongDataId = 0;
	var $nSongId = 0;
	var $sSongTitle = NULL;
	var $sVideoLink = NULL;
	var $sVideoSubmittedBy = NULL;
    var $sLiveVersion = NULL;
	var $nDropId = 0;
	var $sArtImage = 0;
	var $dtLastUpdate = NULL;
	var $sOrderByField = NULL;

	var $sErrorMessage = "";

	/*****************************
	 * Arrays of Related Objects *
	 *****************************/
	//Song Records matching search criteria
	var $aThurdySongDataRecords = array();

	//Constructor
   function __construct() 
   {
		$this->DB = new Database();
   }
  

	/*
	 ********************************************************************************
	 * getThurdySongData()
	 * 
	 * This function retrieves Song data based on data in the properites and
	 * returns an array of ThrdySongData Objects
	 ********************************************************************************
	*/
	function getThurdySongData()
	{

	
		$this->aThurdySongDataRecords = array();
		
		//Begin the SQL SELECT STATEMENT 
		$sql =	"SELECT thurdy_song_data.*, SONG.SONG_NAME ";
		$sql .= "  FROM thurdy_song_data, SONG ";
		$sql .=	" WHERE thurdy_song_data.SONG_ID = SONG.SONG_ID ";

		$this->buildWhereClause($sql);   

//DEBUG
//echo "SQL=". $sql . "<BR>";

		if(!$this->executeSQL($sql,$result))
		{
			return -1;
		}
		else
		{
			$iSongDataCount = 0;

			while($row = mysqli_fetch_array($result))
			{

				$oNextRecord = new ThurdySongData();
				
				//Load each product into a Song Object
				$this->loadSongObject($oNextRecord, $row);
				
				//Then add the object to the array of found products
				$this->aThurdySongDataRecords[$iSongDataCount] = $oNextRecord;
				
				$iSongDataCount++;

			}
		
			$this->DB->closeDB();
			return TRUE;

		}
		   
	}
	
	/*
	 ********************************************************************************
	 * buildWhereClause()
	 * 
	 * This function builds a where clause for retrieving Song records  
	 ********************************************************************************
	*/
	function buildWhereClause(&$sql)
	{

		//If a specific ID is being searched, match on the ID
		if ($this->nThurdySongDataId > 0)
		{
			$sql .= " AND thurdy_song_data.THURDY_SONG_DATA_ID = " . $this->nThurdySongDataId;
		}
		else
		{
		//Otherwise build an SQL statement based on the values in the properties
                    
            // *****************
			// * SONG_ID *
			// *****************
			if ($this->nSongId > 0)
			{
				$sql .= " AND thurdy_song_data.SONG_ID = " . $this->nSongId;
            }	
            
			// **************
			// * VIDEO_LINK *
			// **************
			if (!empty($this->sVideoLink))
			{
				$sql .= " AND thurdy_song_data.VIDEO_LINK = " . $this->sVideoLink;
			}	
							
			// **********************
			// * VIDEO_SUBMITTED_BY *
			// **********************
			if (!empty($this->sVideoSubmittedBy))
			{
				$sql .= " AND thurdy_song_data.VIDEO_SUBMITTED_BY = " . $this->sVideoSubmittedBy;
			}	

			// ****************
			// * LIVE_VERSION *
			// ****************
			if (!empty($this->sLiveVersion))
			{
				$sql .= " AND thurdy_song_data.LIVE_VERSION = " . $this->sLiveVersion;
			}	
							
			// *****************
			// * DROP_ID *
			// *****************
			if ($this->nDropId > 0)
			{
				$sql .= " AND thurdy_song_data.DROP_ID = " . $this->nDropId;
			}	

			// *************
			// * ART_IMAGE *
			// ************
			if (!empty($this->sArtImage))
			{
				$sql .= " AND thurdy_song_data.ART_IMAGE = " . $this->sArtImage;
			}	
							
			// ************
			// * ORDER BY *
			// ************
			if (!empty($this->sOrderByField))
			{
				$sql .= " ORDER BY " . $this->sOrderByField;
			}	
			else
			{
				$sql .= " ORDER BY thurdy_song_data.SONG_ID";	
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
	function executeSQL(&$sql, &$result)
	{
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "THURDYSONGDATA011 - FAILED TO OPEN DB: " . $this->DB->dbError;
			return false;
		}

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);

		if (!$result) 
		{

			 $this->sErrorMessage = "THURDYSONGDATA012 - " . mysqli_error($this->DB->dbConnection);
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
	 * insertThurdySongData
	 * 
	 * This function inserts a THURDY_SONG record 
	 ********************************************************************************
	*/
	function insertThurdySongData()
	{
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "THURDYSONGDATA003 - FAILED TO OPEN DB: ";
			return FALSE;
		}

		$this->escapeSpecialChars();

		$sql =	" INSERT INTO thurdy_song_data";
		$sql .= " (SONG_ID,";				
		$sql .= "  VIDEO_LINK,";		
		$sql .= "  VIDEO_SUBMITTED_BY,";				
		$sql .= "  LIVE_VERSION,";			
		$sql .= "  DROP_ID,";	
		$sql .= "  ART_IMAGE,";				
		$sql .= "  LAST_UPDATED)";		

		$sql .= " VALUES (";
		$sql .= $this->nSongId .",";	
		$sql .= "'" . $this->sVideoLink . "',";	
		$sql .= "'" . $this->sVideoSubmittedBy . "',";		
		$sql .= "'" . $this->sLiveVersion . "',";	
		$sql .= $this->nDropId .",";
		$sql .= "'" . $this->sArtImage . "',";		
		$sql .= "SYSDATE()";	
		$sql .= ")";	
		
//DEBUG
//echo "SQL=". $sql . "<BR>";

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);

	
		//SQL Error
		if (!$result) 
		{

			 $this->sErrorMessage = "THURDYSONGDATA004 - FAILED TO INSERT SONG RECORD - " . mysqli_error($this->DB->dbConnection);
			 return FALSE;
		}
		else 
		{
			$this->nSongNumber = mysqli_insert_id($this->DB->dbConnection);
			return TRUE;	
		}
	}	


	/*
	 ********************************************************************************
	 * updateSongData()
	 * 
	 * This Updates a SONG record
	 ********************************************************************************
	*/
	function updateSongData()
	{	
		if ($this->nThurdySongDataId > 0)
		{
			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "THURDYSONGDATA005 - FAILED TO OPEN DB: ";
				return FALSE;
			}
			$this->escapeSpecialChars();

			$sql =	" UPDATE thurdy_song_data ";
            $sql .= " SET SONG_ID = " . $this->nSongId;
            $sql .= ", VIDEO_LINK = '" . $this->sVideoLink . "'";
			$sql .= ", VIDEO_SUBMITTED_BY = '" . $this->sVideoSubmittedBy . "'";
			$sql .= ", LIVE_VERSION = '" . $this->sLiveVersion . "'";
			$sql .= ", DROP_ID = " . $this->nDropId;
			$sql .= ", ART_IMAGE = '" . $this->sArtImage . "'";
			$sql .= ", LAST_UPDATED = SYSDATE()";
			$sql .= " WHERE THURDY_SONG_DATA_ID  = " . $this->nThurdySongDataId;
		
//DEBUG
//echo "SQL=". $sql . "<BR>";

			
			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result) {	

				 $this->sErrorMessage = "THURDYSONGDATA006 - FAILED TO UPDATE SONG: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}
		
		}
		else
		{
			 $this->sErrorMessage = "THURDYSONGDATA010 - NO SONG_NUMBER SPECIFIED ";
			 return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deleteThurdySongData()
	 * 
	 * This function deletes a Song record
	 ********************************************************************************
	*/
	function deleteThurdySongData()
	{
		if ($this->nThurdySongDataId > 0)
		{
	
			$sql =	" DELETE FROM thurdy_song_data";
			$sql .= " WHERE THURDY_SONG_DATA_ID = " . $this->nThurdySongDataId;
		
//DEBUG
//echo "SQL=". $sql . "<BR>";

			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "THURDYSONGDATA007 - FAILED TO OPEN DB: ";
				return FALSE;
			}		

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result)
			{	
				 $this->sErrorMessage = "THURDYSONGDATA008 - FAILED TO DELETE SONG: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}

		}
		else
		{
			$this->sErrorMessage = "THURDYSONGDATA009 - CAN NOT DELETE SONG BECAUSE NO SONG ID SUPPLIED. ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * loadSongObject()
	 * This function loads a given row from the thurdy_song_data table into an object.  
	 ********************************************************************************
	*/
	function loadSongObject(&$oThurdySongData, &$row)
	{

            $oThurdySongData->nThurdySongDataId = $row['THURDY_SONG_DATA_ID'];
			$oThurdySongData->nSongId = $row['SONG_ID'];	
			$oThurdySongData->sSongTitle = $row['SONG_NAME'];		
			$oThurdySongData->sLiveVersion = $row['LIVE_VERSION'];
			$oThurdySongData->sVideoLink = $row['VIDEO_LINK'];
			$oThurdySongData->sVideoSubmittedBy = $row['VIDEO_SUBMITTED_BY'];
			$oThurdySongData->nDropId = $row['DROP_ID'];
			$oThurdySongData->sArtImage = $row['ART_IMAGE'];
			$oThurdySongData->dtLastUpdate= $row['LAST_UPDATED'];			

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
		$this->sVideoLink = mysqli_real_escape_string($this->DB->dbConnection,$this->sVideoLink);	
		$this->sVideoSubmittedBy = mysqli_real_escape_string($this->DB->dbConnection,$this->sVideoSubmittedBy);	
		$this->sLiveVersion = mysqli_real_escape_string($this->DB->dbConnection,$this->sLiveVersion);	
		$this->sArtImage = mysqli_real_escape_string($this->DB->dbConnection,$this->sArtImage);	
	}
	
}
	
?>