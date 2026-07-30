<?php

//This include defines the relative path to the root directory from this sub-directory
include_once ("root.inc.php");

//include DB Class		
include_once (CLASS_DIR . "/class_DB.php");
//include Error Class
include_once (CLASS_DIR . "/class_Error.php");

class ThurdyComment
{

	var $DB;

	//COMMENT Table Values
	var $nCommentID = 0;
	var $nNumberOfComments = 0;
	var $sComment = NULL;
	var $sAnswer = NULL;
	var $bApproved = FALSE;
	var $dtCommentDate = NULL;
	var $dtLastUpdate = NULL;
	
	//Booleans used in Searching
	var $bFuzzyNameSearch = FALSE;
	

	var $sErrorMessage = "";

	/*****************************
	 * Arrays of Related Objects *
	 *****************************/
	//Comment Records matching search criteria
	var $aCommentRecords = array();

	//Constructor
   function __construct() 
   {
		$this->DB = new Database();
   }
  

	/*
	 ********************************************************************************
	 * getThurdyComment()
	 * 
	 * This function retrieves Comment data based on data in the properites and
	 * returns an array of ThrdyComment Objects
	 ********************************************************************************
	*/
	function getThurdyComment()
	{
		$this->aCommentRecords = array();
		
		//Begin the SQL SELECT STATEMENT 
		$sql =	"SELECT * ";
		$sql .= "  FROM thurdy_comment ";
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
			$iCommentCount = 0;

			while($row = mysqli_fetch_array($result))
			{

				$oNextComment = new ThurdyComment();
				
				//Load each product into a Song Object
				$this->loadCommentObject($oNextComment, $row);
				
				//Then add the object to the array of found products
				$this->aCommentRecords[$iCommentCount] = $oNextComment;
				
				$iCommentCount++;

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
		if ($this->nCommentID > 0)
		{
			$sql .= " AND COMMENT_ID = " . $this->nCommentID;
		}
		else
		{
		//Otherwise build an SQL statement based on the values in the properties
			
			// ***********
			// * COMMENT *
			// ***********
			if (!empty($this->sComment))
			{
				//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
				if ($this->bFuzzyNameSearch)
				{
					$sql .= " AND COMMENT LIKE '%" . $this->sComment . "%'";
				}
				else
				{
					$sql .= " AND COMMENT = '" . $this->sComment . "'";
				}
			}

			// ***********
			// * ANSWER  *
			// ***********
			if (!empty($this->sAnswer))
			{
				//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
				if ($this->bFuzzyNameSearch)
				{
					$sql .= " AND ANSWER LIKE '%" . $this->sAnswer . "%'";
				}
				else
				{
					$sql .= " AND ANSWER = '" . $this->sAnswer . "'";
				}
			}
			
			
			// ******************
			// * COMMENT_DATE *
			// ******************
			if (!empty($this->dtCommentDate))
			{
				$sql .= " AND COMMENT_DATE = " . $this->dtCommentDate;
			}	
							
			// ************
			// * APPROVED *
			// ************
			if (!is_null($this->bApproved))
			{
				if($this->bApproved)
				{
					$sql .= " AND APPROVED = TRUE ";
				}
				else
				{
					$sql .= " AND APPROVED = FALSE ";
				}
			}	
	
			// ************
			// * ORDER BY *
			// ************
			//ALPHABETICAL BY NAME
			$sql .= " ORDER BY COMMENT_DATE DESC";	
			
			// ************
			// * LIMIT *
			// ************
			if ($this->nNumberOfComments > 0)
			{

				$sql .= " LIMIT {$this->nNumberOfComments}";

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
			$this->sErrorMessage = "COMMENT011 - FAILED TO OPEN DB: " . $this->DB->dbError;
			return false;
		}

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);

		if (!$result) 
		{

			 $this->sErrorMessage = "COMMENT012 - " . mysqli_error($this->DB->dbConnection);
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
	 * insertComment
	 * 
	 * This function inserts a THURDY_COMMENT record 
	 ********************************************************************************
	*/
	function insertComment()
	{

		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "THURDY003 - FAILED TO OPEN DB: ";
			return FALSE;
		}
			
		$this->escapeSpecialChars();

		$sql =	" INSERT INTO thurdy_comment";
		$sql .= " (COMMENT,";		
		$sql .= "  ANSWER,";		
		$sql .= "  COMMENT_DATE,";		
		$sql .= "  APPROVED,";		
		$sql .= "  LAST_UPDATE)";		

		$sql .= " VALUES (";
		$sql .= "'" . $this->sComment . "',";	
		if (!empty($this->sAnswer))
		{
			$sql .= "'" . $this->sAnswer . "',";	
		}
		else
		{
			$sql .= " NULL,";	
		}
		if (!empty($this->dtCommentDate))
		{
			$sql .= "'" . $this->dtCommentDate . "',";	
		}
		else
		{
			$sql .= " NULL,";	
		}
		$sql .= "'" . $this->bApproved . "',";	
		$sql .= "SYSDATE()";	
		$sql .= ")";	
		
//DEBUG
//echo "SQL=". $sql . "<BR>";

	
		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);
	
		//SQL Error
		if (!$result) 
		{

			 $this->sErrorMessage = "THURDY004 - FAILED TO INSERT COMMENT RECORD - " . mysqli_error($this->DB->dbConnection);
			 return FALSE;
		}
		else 
		{
			$this->nCommentID = mysqli_insert_id($this->DB->dbConnection);
			return TRUE;	
		}
	}	

	/*
	 ********************************************************************************
	 * updateComment()
	 * 
	 * This Updates a COMMENT record
	 ********************************************************************************
	*/
	function updateComment()
	{
	
		if ($this->nCommentID > 0)
		{
	
			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "THURDY005 - FAILED TO OPEN DB: ";
				return FALSE;
			}
			
			$this->escapeSpecialChars();

			$sql =	" UPDATE thurdy_comment ";
			$sql .= " SET COMMENT = '" . $this->sComment . "'";
			if (!empty($this->sAnswer))
			{
				$sql .= ", ANSWER = '" . $this->sAnswer . "'";
			}
			else
			{
				$sql .= ", ANSWER = NULL";
			}
			if (!empty($this->dtCommentDate))
			{
				$sql .= ", COMMENT_DATE = '" . $this->dtCommentDate . "'";
			}
			else
			{
				$sql .= ", COMMENT_DATE = NULL";
			}
			$sql .= ", APPROVED = '" . $this->bApproved . "'";
			$sql .= ", LAST_UPDATE = SYSDATE()";
			$sql .= " WHERE COMMENT_ID  = " . $this->nCommentID;
		
//DEBUG
//echo "SQL=". $sql . "<BR>";

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);
	
			//SQL Error
			if (!$result) 
			{	

				 $this->sErrorMessage = "THURDY006 - FAILED TO UPDATE COMMENT: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}
		
		}
		else
		{
			 $this->sErrorMessage = "THURDY010 - NO COMMENT_ID SPECIFIED ";
			 return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deleteComment()
	 * 
	 * This function deletes a Comment record
	 ********************************************************************************
	*/
	function deleteComment()
	{
		if ($this->nCommentID > 0)
		{
	
			$sql =	" DELETE FROM thurdy_comment";
			$sql .= " WHERE COMMENT_ID = " . $this->nCommentID;
		
//DEBUG
//echo "SQL=". $sql . "<BR>";

			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "THURDY007 - FAILED TO OPEN DB: ";
				return FALSE;
			}		

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection, $sql);
	
			//SQL Error
			if (!$result)
			{	
				 $this->sErrorMessage = "THURDY008 - FAILED TO DELETE COMMENT: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}

		}
		else
		{
			$this->sErrorMessage = "THURDY009 - CAN NOT DELETE COMMENT BECAUSE NO COMMENT ID SUPPLIED. ";
			return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * loadCommentObject()
	 * This function loads a given row from the COMMENT table into a Comment object.  
	 ********************************************************************************
	*/
	function loadCommentObject(&$oComment, &$row)
	{

			$oComment->nCommentID = $row['COMMENT_ID'];			
			$oComment->sComment = $row['COMMENT'];
			$oComment->sAnswer = $row['ANSWER'];
			$oComment->dtCommentDate = $row['COMMENT_DATE'];			
			$oComment->bApproved = $row['APPROVED'];
			$oComment->dtLastUpdate= $row['LAST_UPDATE'];			

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
		$this->sComment = mysqli_real_escape_string($this->DB->dbConnection,$this->sComment);	
		$this->sAnswer = mysqli_real_escape_string($this->DB->dbConnection,$this->sAnswer);	
	}
	
}
	
?>