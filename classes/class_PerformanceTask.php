<?php
/*
*******************************************************************
class_PerformanceTask.php
This PHP file defines the PerformanceTask object class
NOTES
Date        Change
-------------------------------------------------------------
2022-03-14	Created
*******************************************************************
*/	


//include DB Class		
include_once (CLASS_DIR . "/class_DB.php");


class PerformanceTask
{

	var $DB;

	//PerformanceTask Table Values
	var $nPerformanceTaskID = 0;
    var $nPerformanceID = 0;
    var $nTourID = 0;
	var $sDescription = NULL;
    var $sPerformanceName = NULL;
	var $sLocationCity = NULL;
    var $sTourName = NULL;
    var $bComplete = false;
	var $dtLastUpdate = NULL;
	
	//Booleans used in Searching
	var $bFuzzyDescriptionSearch = false; 

	var $sErrorMessage = "";

	/*****************************
	 * Arrays of Related Objects *
	 *****************************/
	//PerformanceTask Records matching search criteria
	var $aPerformanceTaskRecords = array();
	//PerformanceTask Errors
	var $aPerformanceTaskErrors = array();

	//Constructor
   function __construct() 
   {
		$this->DB = new Database();
   }
  

	/*
	 ********************************************************************************
	 * getPerformanceTask()
	 * 
	 * This function retrieves PerformanceTask data based on data in the properites and
	 * returns an array of PerformanceTask Objects
	 ********************************************************************************
	*/
	function getPerformanceTask()
	{

		$this->aPerformanceTaskRecords = array();
		
		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the PerformanceTask type)
		$sql =	"SELECT PT.*, P.PERFORMANCE_NAME, P.LOCATION_CITY, T.TOUR_NAME ";
		$sql .= "  FROM PERFORMANCE_TASK PT";
		$sql .= "  LEFT JOIN PERFORMANCE P ON PT.PERFORMANCE_ID = P.PERFORMANCE_ID ";
        $sql .= "  LEFT JOIN TOUR T ON PT.TOUR_ID = T.TOUR_ID ";
		$sql .=	" WHERE 1=1";
		   
		$this->buildWhereClause($sql);

//DEBUG
//echo "SQL=". $sql . "<BR>";
							
		$result = NULL;
		
		if(!$this->executeSQL($sql,$result))
		{
			return -1;
		}
		else
		{

			$iPerformanceTaskCount = 0;

			while($row = mysqli_fetch_array($result))
			{

				$oNextPerformanceTask = new PerformanceTask();
				
				//Load each product into a PerformanceTask Object
				$this->loadPerformanceTaskObject($oNextPerformanceTask, $row);

				//Then add the object to the array of found products
				$this->aPerformanceTaskRecords[$iPerformanceTaskCount] = $oNextPerformanceTask;
				
				$iPerformanceTaskCount++;

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
		if ($this->nPerformanceTaskID > 0)
		{
			$sql .= " AND PT.PERFORMANCE_TASK_ID = {$this->nPerformanceTaskID}";
		}
		else
		{
		//Otherwise build an SQL statement based on the values in the properties
			
			// ***************
			// * DESCRIPTION *
			// ***************
			if (!empty($this->sDescription))
			{
				//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
				if ($this->bFuzzyDescriptionSearch)
				{
					$sql .= " AND DESCRIPTION LIKE '%{$this->sDescription}%'";
				}
				else
				{
					$sql .= " AND DESCRIPTION = '{$this->sDescription}'";
				}
			}

            // ***********
			// * TOUR_ID *
			// ***********
			if (!empty($this->nTourID))
			{
				$sql .= " AND PT.TOUR_ID = {$this->nTourID}";
			}

            // ******************
			// * PERFORMANCE_ID *
			// ******************
			if (!empty($this->nPerformanceID))
			{
				$sql .= " AND PT.PERFORMANCE_ID = {$this->nPerformanceID}";
			}

			// ************
			// * COMPLETE *
			// ************
			if (!empty($this->bComplete))
			{
				$sql .= " AND PT.COMPLETE = {$this->bComplete}";
			}

			// ************
			// * ORDER BY *
			// ************
			//ALPHABETICAL BY NAME
			$sql .= " ORDER BY PT.LAST_UPDATE";	

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
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "PFT015 - FAILED TO OPEN DB: " . $this->DB->dbError;
			return false;
		}

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);

		if (!$result) 
		{

			 $this->sErrorMessage = "PFT016 - " . mysqli_error($this->DB->dbConnection);
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
	 * InsertPerformanceTask()
	 * 
	 * This function inserts a PerformanceTask record 
	 ********************************************************************************
	*/
	function insertPerformanceTask()
	{
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "PFT003 - FAILED TO OPEN DB: ";
			return FALSE;
		}
			
		$this->escapeSpecialChars();

		$sql =	" INSERT INTO PERFORMANCE_TASK";
		$sql .= " (DESCRIPTION,";
        $sql .= " PERFORMANCE_ID,";
		$sql .= " TOUR_ID,";
		$sql .= " COMPLETE,";
		$sql .= " LAST_UPDATE)";		

		$sql .= " VALUES (";
		$sql .= "'{$this->sDescription}',";	
        if(!empty($this->nPerformanceID)){
            $sql .= "{$this->nPerformanceID},";	
        }
        else{
            $sql .= "NULL,";	
        }
        if(!empty($this->nTourID)){
            $sql .= "{$this->nTourID},";	
        }
        else{
            $sql .= "NULL,";	
        }	
        if($this->bComplete)
		{
			$sql .= "TRUE,";	
		}
		else
		{
			$sql .= "FALSE,";	
		}
		$sql .= "SYSDATE()";	
		$sql .= ")";	
		
//DEBUG
//echo "SQL={$sql}<BR>";

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);
	
		//SQL Error
		if (!$result) 
		{

			 $this->sErrorMessage = "PFT004 - FAILED TO INSERT PerformanceTask RECORD - " . mysqli_error($this->DB->dbConnection);
			 return FALSE;
		}
		else 
		{
			$this->nPerformanceTaskID = mysqli_insert_id($this->DB->dbConnection);
			return TRUE;	
		}

	}	


	/*
	 ********************************************************************************
	 * updatePerformanceTask()
	 * 
	 * This Updates a PerformanceTask record
	 ********************************************************************************
	*/
	function updatePerformanceTask()
	{
	
		if ($this->nPerformanceTaskID > 0)
		{
			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "PFT005 - FAILED TO OPEN DB: ";
				return FALSE;
			}
			
			$this->escapeSpecialChars();
	
			$sql =	" UPDATE PERFORMANCE_TASK ";
			$sql .= " SET  DESCRIPTION  = '{$this->sDescription}'";
            if(!empty($this->nPerformanceID)){
                $sql .= "   ,  PERFORMANCE_ID = {$this->nPerformanceID}";
            }
            if(!empty($this->nTourID)){
			    $sql .= "   ,  TOUR_ID = {$this->nTourID}";	
            }			
			if($this->bComplete)
			{
				$sql .= ", COMPLETE = TRUE";
			}
			else
			{
				$sql .= ", COMPLETE = FALSE";
			}
			$sql .= ", LAST_UPDATE = SYSDATE()";
			$sql .= " WHERE PERFORMANCE_TASK_ID  = {$this->nPerformanceTaskID}";
		
//DEBUG
//echo "SQL={$sql}<BR>";

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result) {	

				 $this->sErrorMessage = "PFT006 - FAILED TO UPDATE PerformanceTask: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}
		
		}
		else
		{
			 $this->sErrorMessage = "PFT010 - NO PerformanceTask_ID SPECIFIED ";
			 return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deletePerformanceTask()
	 * 
	 * This function deletes a PerformanceTask record
	 ********************************************************************************
	*/
	function deletePerformanceTask()
	{
		if ($this->nPerformanceTaskID > 0)
		{
			$sql =	" DELETE FROM PERFORMANCE_TASK";
			$sql .= " WHERE PERFORMANCE_TASK_ID = {$this->nPerformanceTaskID}";
		
//DEBUG
//echo "SQL={$sql}<BR>";

			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "PFT007 - FAILED TO OPEN DB: ";
				return FALSE;
			}		

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);

			//SQL Error
			if (!$result)
			{	
				$this->sErrorMessage = "PFT008 - FAILED TO DELETE PerformanceTask: " . mysqli_error($this->DB->dbConnection);
				return FALSE;
			}
			else 
			{
				return TRUE;	
			}


		}
		else
		{
			$this->sErrorMessage = "PFT009 - CAN NOT DELETE PerformanceTask BECAUSE NO PerformanceTask ID SUPPLIED. ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * loadPerformanceTaskObject()
	 * This function loads a given row from the PERFORMANCE table into a Performance object.  
	 ********************************************************************************
	*/
	function loadPerformanceTaskObject(&$oPerformanceTask, &$row)
	{

			$oPerformanceTask->nPerformanceTaskID = $row['PERFORMANCE_TASK_ID'];			
			$oPerformanceTask->sDescription = $row['DESCRIPTION'];
            $oPerformanceTask->sPerformanceName = $row['PERFORMANCE_NAME'];
			$oPerformanceTask->sLocationCity = $row['LOCATION_CITY'];
            $oPerformanceTask->sTourName = $row['TOUR_NAME'];
			$oPerformanceTask->nPerformanceID = $row['PERFORMANCE_ID'];			
            $oPerformanceTask->nTourID = $row['TOUR_ID'];				
			$oPerformanceTask->bComplete = $row['COMPLETE'];						
			$oPerformanceTask->dtLastUpdate= $row['LAST_UPDATE'];			
	}

	/*
	 ********************************************************************************
	 * getPerformanceTaskErrors()
	 * 
	 * This function creates an array of PerformanceTasks that have errors 
	 ********************************************************************************
	*/
	function getPerformanceTaskErrors()
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
		$this->sDescription = isset($this->sDescription) ? mysqli_real_escape_string($this->DB->dbConnection,$this->sDescription) : '';
		$this->sPerformanceName = isset($this->sPerformanceName) ? mysqli_real_escape_string($this->DB->dbConnection,$this->sPerformanceName) : '';
  		$this->sLocationCity = isset($this->sLocationCity) ? mysqli_real_escape_string($this->DB->dbConnection,$this->sLocationCity) : '';
  		$this->sTourName = isset($this->sTourName) ? mysqli_real_escape_string($this->DB->dbConnection,$this->sTourName) : '';
	}

	
}
	
?>