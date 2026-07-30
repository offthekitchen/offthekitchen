<?php
/*
*******************************************************************
class_Radio_Station.php
This PHP file defines the Radio Station object class
NOTES
Date        Change
-------------------------------------------------------------
2015-12-19	removed unneeded includes
*******************************************************************
*/	

//include DB Class		
include_once (CLASS_DIR . "/class_DB.php");

class RadioStation
{

	var $DB;

	//RadioStation Table Values
	var $nStationID = 0;
	var $sStationName = NULL;
	var $sFrequency = NULL;
	var $sCity = NULL;
	var $sState = NULL;
	var $sShowName = NULL;
	var $sURL = NULL;
	var $sHost = NULL;
	var $sTimeSlot = NULL;
	var $sRequestEmail = NULL;
	var $sRequestPhone = NULL;
	var $bActive = TRUE;
	var $dtLastUpdate = NULL;
	
	//Booleans used in Searching
	var $bFuzzyNameSearch = FALSE;
		
	var $sErrorMessage = "";

	/*****************************
	 * Arrays of Related Objects *
	 *****************************/
	//RadioStation Records matching search criteria
	var $aRadioStationRecords = array();
	//RadioStation Errors
	var $aRadioStationErrors = array();

	//Constructor
   function __construct() 
   {
		$this->DB = new Database();
   }
  

	/*
	 ********************************************************************************
	 * getRadioStation()
	 * 
	 * This function retrieves RadioStation data based on data in the properites and
	 * returns an array of RadioStation Objects
	 ********************************************************************************
	*/
	function getRadioStation()
	{

	
		$this->aRadioStationRecords = array();
		
		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the RadioStation)
		$sql =	"SELECT RADIO_STATION.* ";
		$sql .= "  FROM RADIO_STATION ";
		$sql .=	" WHERE 1=1 ";
		   

		//If a specific RadioStation ID is being searched, match on the ID
		if ($this->nStationID > 0)
		{
			$sql .= " AND STATION_ID = {$this->nStationID}";
		}
		else
		{
		//Otherwise build an SQL statement based on the values in the properties
			
			// **********************
			// * RADIO STATION NAME *
			// **********************
			if (!empty($this->sStationName))
			{
				//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
				if ($this->bFuzzyNameSearch)
				{
					$sql .= " AND STATION_NAME LIKE '%{$this->sStationName}%'";
				}
				else
				{
					$sql .= " AND STATION_NAME = '{$this->sStationName}'";
				}
			}
			
			// *************
			// * FREQUENCY *
			// *************
			if (!empty($this->sFrequency))
			{
				$sql .= " AND FREQUENCY = '{$this->sFrequency}'";
			}

			// ********
			// * CITY *
			// ********
			if (!empty($this->sCity))
			{
				$sql .= " AND CITY = '{$this->sCity}'";
			}

			// *********
			// * STATE *
			// *********
			if (!empty($this->sState))
			{
				$sql .= " AND STATE = '{$this->sState}'";
			}

			// *************
			// * SHOW NAME *
			// *************
			if (!empty($this->sShowName))
			{
				$sql .= " AND SHOW_NAME = '{$this->sShowName}'";
			}

			// *******
			// * URL *
			// *******
			if (!empty($this->sURL))
			{
				$sql .= " AND URL = '{$this->sURL}'";
			}

			// *************
			// * SHOW HOST *
			// *************
			if (!empty($this->sShowHost))
			{
				$sql .= " AND SHOW_HOST = '{$this->sShowHost}'";
			}

			// *************
			// * TIME SLOT *
			// *************
			if (!empty($this->sTimeSlot))
			{
				$sql .= " AND TIME_SLOT = '{$this->sTimeSlot}'";
			}

			// *****************
			// * REQUEST EMAIL *
			// *****************
			if (!empty($this->sRequestEmail))
			{
				$sql .= " AND REQUEST_EMAIL = '{$this->sRequestEmail}'";
			}


			// *****************
			// * REQUEST PHONE *
			// *****************
			if (!empty($this->sRequestPhone))
			{
				$sql .= " AND REQUEST_PHONE = '{$this->sRequestPhone}'";
			}

			// **********
			// * ACTIVE *
			// **********
			if (!is_null($this->bActive))
			{
				if($this->bActive)
				{
					$sql .= " AND ACTIVE = TRUE ";
				}
				else
				{
					$sql .= " AND ACTIVE = FALSE ";
				}
			}	
			
			// ************
			// * ORDER BY *
			// ************
			$sql .= " ORDER BY STATION_ID";

		}
//DEBUG
//echo "SQL={$sql}<BR>";
							
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "RST001 - FAILED TO OPEN DB: {$this->DB->dbError}";
			return FALSE;
		}

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);


		//SQL Error
		if (!$result) {

			 $this->sErrorMessage = "RST002 - " . mysqli_error($this->DB->dbConnection);
			 $this->DB->closeDB();
			 return FALSE;
		}
		else 
		{

			$iRadioStationCount = 0;

			while($row = mysqli_fetch_array($result))
			{

				$oNextRadioStation = new RadioStation();
				
				//Load each product into a RadioStation Object
				$this->loadRadioStationObject($oNextRadioStation, $row);

				//Then add the object to the array of found products
				$this->aRadioStationRecords[$iRadioStationCount] = $oNextRadioStation;
				
				$iRadioStationCount++;

			}
		
			$this->DB->closeDB();
			return TRUE;

		}

	}

	/*
	 ********************************************************************************
	 * Insert RadioStation()
	 * 
	 * This function inserts a RadioStation record 
	 ********************************************************************************
	*/
	function insertRadioStation()
	{
		

	}	


	/*
	 ********************************************************************************
	 * updateRadioStation()
	 * 
	 * This Updates a RadioStation record
	 ********************************************************************************
	*/
	function updateRadioStation()
	{

	}

	/*
	 ********************************************************************************
	 * deleteRadioStation()
	 * 
	 * This function deletes a RadioStation record
	 ********************************************************************************
	*/
	function deleteRadioStation()
	{
		if ($this->nStationID > 0)
		{
	
			$sql =	" DELETE FROM RADIO_STATION";
			$sql .= " WHERE STATION_ID = {$this->nStationID}";
		
//DEBUG
//echo "SQL={$sql}<BR>";

			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "RST007 - FAILED TO OPEN DB: ";
				return FALSE;
			}		

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result)
			{	
				 $this->sErrorMessage = "RST008 - FAILED TO DELETE RADIO STATION: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}

		}
		else
		{
			$this->sErrorMessage = "RST009 - CAN NOT DELETE RADIO STATION BECAUSE NO STATION_ID SUPPLIED. ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * loadRadioStationObject()
	 * This function loads a given row from the RadioStation table into a RadioStation object.  
	 ********************************************************************************
	*/
	function loadRadioStationObject(&$oRadioStation, &$row)
	{

			$oRadioStation->nStationID = $row['STATION_ID'];			
			$oRadioStation->sStationName = $row['STATION_NAME'];
			$oRadioStation->sFrequency = $row['FREQUENCY'];
			$oRadioStation->sCity = $row['CITY'];
			$oRadioStation->sState = $row['STATE'];
			$oRadioStation->sShowName = $row['SHOW_NAME'];
			$oRadioStation->sURL = $row['URL'];
			$oRadioStation->sShowHost = $row['SHOW_HOST'];
			$oRadioStation->sTimeSlot = $row['TIME_SLOT'];
			$oRadioStation->sRequestEmail = $row['REQUEST_EMAIL'];
			$oRadioStation->sRequestPhone = $row['REQUEST_PHONE'];
			$oRadioStation->bActive = $row['ACTIVE'];
			$oRadioStation->dtLastUpdate= $row['LAST_UPDATE'];			

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
		$this->sStationName = mysqli_real_escape_string($this->DB->dbConnection,$this->sStationName);	

	}

}
	
?>