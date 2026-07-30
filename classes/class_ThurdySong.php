<?php

//This include defines the relative path to the root directory from this sub-directory
include_once ("root.inc.php");

//include DB Class		
include_once (CLASS_DIR . "/class_DB.php");


class ThurdySong
{

	var $DB;

	//SONG Table Values
	var $nSongNumber = 0;
	var $sSongTitle = NULL;
	var $sSongDesc = FALSE;
	var $sSongMP3 = NULL;
	var $sVideoLink = NULL;
	var $sVideoSubmittedBy = NULL;
	var $dtVideoDate = NULL;
	var $sLiveVersion = NULL;
	var $sArtImage = NULL;
	var $sDropImage = NULL;
	var $sDropLocation = NULL;
	var $dtLastUpdate = NULL;
	var $sOrderByField = NULL;
		
	//Booleans used in Searching
	var $bFuzzyNameSearch = FALSE;
	

	var $sErrorMessage = "";

	/*****************************
	 * Arrays of Related Objects *
	 *****************************/
	//Song Records matching search criteria
	var $aSongRecords = array();

	//Constructor
   function __construct() 
   {
		$this->DB = new Database();
   }
  

	/*
	 ********************************************************************************
	 * getThurdySong()
	 * 
	 * This function retrieves Song data based on data in the properites and
	 * returns an array of ThrdySong Objects
	 ********************************************************************************
	*/
	function getThurdySong()
	{

	
		$this->aSongRecords = array();
		
		//Begin the SQL SELECT STATEMENT 
		$sql =	"SELECT * ";
		$sql .= "  FROM thurdy_song ";
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
			$iSongCount = 0;

			while($row = mysqli_fetch_array($result))
			{

				$oNextSong = new ThurdySong();
				
				//Load each product into a Song Object
				$this->loadSongObject($oNextSong, $row);
				
				//Then add the object to the array of found products
				$this->aSongRecords[$iSongCount] = $oNextSong;
				
				$iSongCount++;

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
		if ($this->nSongNumber > 0)
		{
			$sql .= " AND SONG_NUMBER = " . $this->nSongNumber;
		}
		else
		{
		//Otherwise build an SQL statement based on the values in the properties
			
			// **************
			// * SONG TITLE *
			// **************
			if (!empty($this->sSongTitle))
			{
				//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
				if ($this->bFuzzyNameSearch)
				{
					$sql .= " AND SONG_TITLE LIKE '%" . $this->sSongTitle . "%'";
				}
				else
				{
					$sql .= " AND SONG_TITLE = '" . $this->sSongTitle . "'";
				}
			}
			
			
			// *************
			// * SONG_DESC *
			// *************
			if (!empty($this->sSongDesc))
			{
				$sql .= " AND SONG_DESC = " . $this->sSongDesc;
			}	
							
			// ************
			// * SONG_MP3 *
			// ************
			if (!empty($this->sSongMP3))
			{
				$sql .= " AND SONG_MP3 = " . $this->sSongMP3;
			}	
							
			// **************
			// * VIDEO_LINK *
			// **************
			if (!empty($this->sVideoLink))
			{
				$sql .= " AND VIDEO_LINK = " . $this->sVideoLink;
			}	
							
			// **********************
			// * VIDEO_SUBMITTED_BY *
			// **********************
			if (!empty($this->sVideoSubmittedBy))
			{
				$sql .= " AND VIDEO_SUBMITTED_BY = " . $this->sVideoSubmittedBy;
			}	

			// **************
			// * VIDEO_DATE *
			// **************
			if (!empty($this->dtVideoDate))
			{
				$sql .= " AND VIDEO_DATE = " . $this->dtVideoDate;
			}	
							
			// ****************
			// * LIVE_VERSION *
			// ****************
			if (!empty($this->sLiveVersion))
			{
				$sql .= " AND LIVE_VERSION = " . $this->sLiveVersion;
			}	
							
			// *************
			// * ART_IMAGE *
			// *************
			if (!empty($this->sArtImage))
			{
				$sql .= " AND ART_IMAGE = " . $this->sArtImage;
			}	
							
			// **************
			// * DROP_IMAGE *
			// **************
			if (!empty($this->sDropImage))
			{
				$sql .= " AND DROP_IMAGE = " . $this->sDropImage;
			}	
							
			// *****************
			// * DROP_LOCATION *
			// *****************
			if (!empty($this->sDropLocation))
			{
				$sql .= " AND DROP_LOCATION = " . $this->sDropLocation;
			}	
							
			// ************
			// * ORDER BY *
			// ************
			//ALPHABETICAL BY NAME
			if (!empty($this->sOrderByField))
			{
				$sql .= " ORDER BY " . $this->sOrderByField;
			}	
			else
			{
				$sql .= " ORDER BY SONG_TITLE";	
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
			$this->sErrorMessage = "THURDYSONG011 - FAILED TO OPEN DB: " . $this->DB->dbError;
			return false;
		}

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);

		if (!$result) 
		{

			 $this->sErrorMessage = "THURDYSONG012 - " . mysqli_error($this->DB->dbConnection);
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
	 * insertSong
	 * 
	 * This function inserts a THURDY_SONG record 
	 ********************************************************************************
	*/
	function insertSong()
	{
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "THURDYSONG003 - FAILED TO OPEN DB: ";
			return FALSE;
		}

		$this->escapeSpecialChars();

		$sql =	" INSERT INTO thurdy_song";
		$sql .= " (SONG_NUMBER,";		
		$sql .= "  SONG_TITLE,";		
		$sql .= "  SONG_DESC,";		
		$sql .= "  SONG_MP3,";		
		$sql .= "  VIDEO_LINK,";		
		$sql .= "  VIDEO_SUBMITTED_BY,";		
		$sql .= "  VIDEO_DATE,";		
		$sql .= "  LIVE_VERSION,";		
		$sql .= "  ART_IMAGE,";		
		$sql .= "  DROP_IMAGE,";		
		$sql .= "  DROP_LOCATION,";		
		$sql .= "  LAST_UPDATE)";		

		$sql .= " VALUES (";
		$sql .= $this->nSongNumber .",";	
		$sql .= "'" . $this->sSongTitle . "',";	
		$sql .= "'" . $this->sSongDesc . "',";	
		$sql .= "'" . $this->sSongMP3 . "',";	
		$sql .= "'" . $this->sVideoLink . "',";	
		$sql .= "'" . $this->sVideoSubmittedBy . "',";	
		$sql .= "'" . $this->dtVideoDate . "',";	
		$sql .= "'" . $this->sLiveVersion . "',";	
		$sql .= "'" . $this->sArtImage . "',";	
		$sql .= "'" . $this->sDropImage . "',";	
		$sql .= "'" . $this->sDropLocation . "',";	
		$sql .= "SYSDATE()";	
		$sql .= ")";	
		
//DEBUG
//echo "SQL=". $sql . "<BR>";

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);

	
		//SQL Error
		if (!$result) 
		{

			 $this->sErrorMessage = "THURDYSONG004 - FAILED TO INSERT SONG RECORD - " . mysqli_error($this->DB->dbConnection);
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
	 * updateSong()
	 * 
	 * This Updates a SONG record
	 ********************************************************************************
	*/
	function updateSong()
	{	
		if ($this->nSongNumber > 0)
		{
			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "THURDYSONG005 - FAILED TO OPEN DB: ";
				return FALSE;
			}
			$this->escapeSpecialChars();

			$sql =	" UPDATE thurdy_song ";
			$sql .= " SET SONG_TITLE = '" . $this->sSongTitle . "'";
			$sql .= ", SONG_DESC = '" . $this->sSongDesc . "'";
			$sql .= ", SONG_MP3 = '" . $this->sSongMP3 . "'";
			$sql .= ", VIDEO_LINK = '" . $this->sVideoLink . "'";
			$sql .= ", VIDEO_SUBMITTED_BY = '" . $this->sVideoSubmittedBy . "'";
			$sql .= ", VIDEO_DATE = '" . $this->dtVideoDate . "'";
			$sql .= ", LIVE_VERSION = '" . $this->sLiveVersion . "'";
			$sql .= ", ART_IMAGE = '" . $this->sArtImage . "'";
			$sql .= ", DROP_IMAGE = '" . $this->sDropImage . "'";
			$sql .= ", DROP_LOCATION = '" . $this->sDropLocation . "'";
			$sql .= ", LAST_UPDATE = SYSDATE()";
			$sql .= " WHERE SONG_NUMBER  = " . $this->nSongNumber;
		
//DEBUG
//echo "SQL=". $sql . "<BR>";

			
			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result) {	

				 $this->sErrorMessage = "THURDYSONG006 - FAILED TO UPDATE SONG: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}
		
		}
		else
		{
			 $this->sErrorMessage = "THURDYSONG010 - NO SONG_NUMBER SPECIFIED ";
			 return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deleteSong()
	 * 
	 * This function deletes a Song record
	 ********************************************************************************
	*/
	function deleteSong()
	{
		if ($this->nSongNumber > 0)
		{
	
			$sql =	" DELETE FROM thurdy_song";
			$sql .= " WHERE SONG_NUMBER = " . $this->nSongNumber;
		
//DEBUG
//echo "SQL=". $sql . "<BR>";

			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "THURDYSONG007 - FAILED TO OPEN DB: ";
				return FALSE;
			}		

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result)
			{	
				 $this->sErrorMessage = "THURDYSONG008 - FAILED TO DELETE SONG: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}

		}
		else
		{
			$this->sErrorMessage = "THURDYSONG009 - CAN NOT DELETE SONG BECAUSE NO SONG ID SUPPLIED. ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * loadSongObject()
	 * This function loads a given row from the SONG table into a Song object.  
	 ********************************************************************************
	*/
	function loadSongObject(&$oSong, &$row)
	{

			$oSong->nSongNumber = $row['SONG_NUMBER'];			
			$oSong->sSongTitle = $row['SONG_TITLE'];
			$oSong->sSongDesc = $row['SONG_DESC'];
			$oSong->sSongMP3 = $row['SONG_MP3'];
			$oSong->sLiveVersion = $row['LIVE_VERSION'];
			$oSong->sVideoLink = $row['VIDEO_LINK'];
			$oSong->sVideoSubmittedBy = $row['VIDEO_SUBMITTED_BY'];
			$oSong->dtVideoDate = $row['VIDEO_DATE'];
			$oSong->sArtImage = $row['ART_IMAGE'];
			$oSong->sDropImage = $row['DROP_IMAGE'];
			$oSong->sDropLocation = $row['DROP_LOCATION'];
			$oSong->dtLastUpdate= $row['LAST_UPDATE'];			

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
		$this->sSongTitle = mysqli_real_escape_string($this->DB->dbConnection,$this->sSongTitle);	
		$this->sSongDesc = mysqli_real_escape_string($this->DB->dbConnection,$this->sSongDesc);	
		$this->sVideoLink = mysqli_real_escape_string($this->DB->dbConnection,$this->sVideoLink);	
		$this->sVideoSubmittedBy = mysqli_real_escape_string($this->DB->dbConnection,$this->sVideoSubmittedBy);	
		$this->sLiveVersion = mysqli_real_escape_string($this->DB->dbConnection,$this->sLiveVersion);	
		$this->sArtImage = mysqli_real_escape_string($this->DB->dbConnection,$this->sArtImage);	
		$this->sDropImage = mysqli_real_escape_string($this->DB->dbConnection,$this->sDropImage);	
	}
	
}
	
?>