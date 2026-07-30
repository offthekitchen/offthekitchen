<?php
/*
*******************************************************************
class_Payment.php
This PHP file defines Payment object class
NOTES
Date        Change
-------------------------------------------------------------
2015-04-26	Added Order by clause to SQL.
2015-05-12	Added Purchase Order property
2015-12-19	Refactored and removed unneeded includes
2020-09-30	Added Logic to get errors
2021-11-30  Added Error for paynment with no revenues
*******************************************************************
*/	

//include DB Class		
include_once (CLASS_DIR . "/class_DB.php");
include_once (CLASS_DIR . "/class_Error.php");

class Payment
{

	var $DB;

	//PAYMENT Table Values
	var $nPaymentID = 0;
	var $nPaymentAmount = 0.00;
	var $dtPaymentDate = NULL;
	var $sPaymentDescription = NULL;
	var $nVendorID = NULL;
	var $nCheckNumber = NULL;
	var $nInvoice = NULL;
	var $sPurchaseOrder = NULL;
	var $dtLastUpdate = NULL;

	//Variable used only for searching
	var $nPaymentYear = 0;
	var $dtStartDate = NULL;
	var $dtEndDate = NULL;
	
	//Booleans used in Searching
	var $bFuzzyNameSearch = FALSE;

	var $sErrorMessage = "";

	//Payment Type Object 
	var $thisPayment;

	//Revenues assigned to this Payment
	var $oPaymentRevenues = NULL;
	//Potential Revenues not assigned to this Payment
	var $oPotentialRevenues = NULL;

	
	/*****************************
	 * Arrays of Related Objects *
	 *****************************/
	//Payment Records matching search criteria
	var $aPaymentRecords = array();
	//Payment Errors
	var $aPaymentErrors = array();

	//Constructor
   function __construct() 
   {
		$this->DB = new Database();
   }
  

	/*
	 ********************************************************************************
	 * getPayment()
	 * 
	 * This function retrieves Payment data based on data in the properites and
	 * returns an array of Payment Objects
	 ********************************************************************************
	*/
	function getPayment()
	{
		$this->aPaymentRecords = array();
		
		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the product type)
		$sql =	"SELECT PAYMENT.* ";
		$sql .= "  FROM PAYMENT ";
		$sql .=	" WHERE 1=1 ";
		   
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

			$iPaymentCount = 0;

			while($row = mysqli_fetch_array($result))
			{

				$oNextPayment = new Payment();
				
				//Load each product into a Payment Object
				$this->loadPaymentObject($oNextPayment, $row);
				
				//Then add the object to the array of found products
				$this->aPaymentRecords[$iPaymentCount] = $oNextPayment;
				
				$iPaymentCount++;

			}
		
			$this->DB->closeDB();
			return TRUE;

		}

	}

	/*
	 ********************************************************************************
	 * getNumberOfPayments()
	 * 
	 * This function retrieves the number of revenues matching values in the 
	 * properites.  It returns the number of matching expense records or a -1
	 * if there is a failure
	 ********************************************************************************
	*/
	function getNumberOfPayments()
	{

		$this->aPaymentRecords = array();
		
		//Begin the SQL SELECT STATEMENT (Go ahead and get some basic information about the product type)
		$sql =	"SELECT COUNT(*) AS PAYMENT_COUNT ";
		$sql .= "  FROM PAYMENT ";
		$sql .=	" WHERE 1=1 ";

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
			$row = mysqli_fetch_array($result);
			
			$nNumberOfPayments = $row['PAYMENT_COUNT'];
		
			$this->DB->closeDB();
			return $nNumberOfPayments;
	
		}

	}
	
		/*
	 ********************************************************************************
	 * getPaymentRevenues()
	 * 
	 * This function loads the array of Revenue objects associated with this Payment 
	 ********************************************************************************
	*/
	function getPaymentRevenues()
	{
		$this->oPaymentRevenues = new Revenue();
		$this->oPaymentRevenues->nPaymentID = $this->nPaymentID;
		if(!$this->oPaymentRevenues->getRevenue())
		{
			$this->sErrorMessage = "PMT011 - Failed to retrieve Revenues: " . $this->oPaymentRevenues->sErrorMessage;
			return false; 
		}
		else
		{
			return true;
		}
	}	

	/*
	 ********************************************************************************
	 * getPotentialRevenues()
	 * 
	 * This function loads the array of Revenue objects whose dates fall within
	 * the range of this tour 
	 ********************************************************************************
	*/
	function getPotentialRevenues()
	{
		$this->oPotentialRevenues = new Revenue();
		$this->oPotentialRevenues->dtPaidDate = $this->dtPaymentDate;
		$this->oPotentialRevenues->nExcludePaymentID = $this->nPaymentID;
		if(!$this->oPotentialRevenues->getRevenue())
		{
			$this->sErrorMessage = "PMT012 - Failed to retrieve Revenues: " . $this->oPotentialRevenues->sErrorMessage;
			return false; 
		}
		else
		{
			return true;
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

		//If a specific Payment ID is being searched, match on the ID
		if ($this->nPaymentID > 0)
		{
			$sql .= " AND PAYMENT_ID = " . $this->nPaymentID;
		}
			

		// ****************
		// * PAYMENT_DATE *
		// ****************
		if (!empty($this->dtPaymentDate))
		{
			$sql .= " AND PAYMENT_DATE LIKE '" . $this->dtPaymentDate . "%'";
		}

		// ********************
		// * PAYMENT YEAR *
		// ********************
		if (!empty($this->nPaymentYear))
		{
			$sql .= " AND YEAR(PAYMENT_DATE) = " . $this->nPaymentYear ;
		}

		// **************
		// * START DATE *
		// **************
		if (!empty($this->dtStartDate))
		{
			$sql .= " AND PAYMENT_DATE >= '" . $this->dtStartDate . "'";
		}

		// ************
		// * END DATE *
		// ************
		if (!empty($this->dtEndDate))
		{
			$sql .= " AND PAYMENT_DATE <= '" . $this->dtEndDate . "'";
		}

		// ***********************
		// * PAYMENT DESCRIPTION *
		// ***********************
		if (!empty($this->sPaymentDescription))
		{
			//If the Fuzzy Name Search flag is set, look for the pattern anywhere in the name
			if ($this->bFuzzyNameSearch)
			{
				$sql .= " AND PAYMENT_DESCRIPTION LIKE '%" . $this->sPaymentDescription . "%'";
			}
			else
			{
				$sql .= " AND PAYMENT_DESCRIPTION = '" . $this->sPaymentDescription . "'";
			}
		}
			
		// ******************
		// * PAYMENT_AMOUNT *
		// ******************
		if ($this->nPaymentAmount > 0)
		{
			$sql .= " AND PAYMENT_AMOUNT = " . $this->nPaymentAmount;
		}


		// ****************
		// * VENDOR_ID   *
		// ****************
		if ($this->nVendorID > 0)
		{
			$sql .= " AND VENDOR_ID = " . $this->nVendorID;
		}

		// ******************
		// * PURCHASE_ORDER *
		// ******************
		if (!empty($this->sPurchaseOrder))
		{
			$sql .= " AND PURCHASE_ORDER = '" . $this->sPurchaseOrder . "'";
		}
			

		// ******************
		// * CHECK_NUMBER   *
		// ******************
		if ($this->nCheckNumber > 0)
		{
			$sql .= " AND CHECK_NUMBER = " . $this->nCheckNumber;
		}

		// *************
		// * INVOICE   *
		// *************
		if ($this->nInvoice > 0)
		{
			$sql .= " AND INVOICE = " . $this->nInvoice;
		}

		// ************
		// * ORDER BY *
		// ************
		$sql .= " ORDER BY PAYMENT_DATE";		
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
			$this->sErrorMessage = "PMT001 - FAILED TO OPEN DB: " . $this->DB->dbError;
			return false;
		}

		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);

		if (!$result) 
		{

			 $this->sErrorMessage = "PMT002 - " . mysqli_error($this->DB->dbConnection);
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
	 * insertPayment()
	 * 
	 * This function inserts an PAYMENT record 
	 ********************************************************************************
	*/
	function insertPayment()
	{
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "PMT003 - FAILED TO OPEN DB: ";
			return FALSE;
		}
		
		$this->escapeSpecialChars();
			
		$sql =	" INSERT INTO PAYMENT";
		$sql .= " (PAYMENT_DATE,";		
		$sql .= "  PAYMENT_AMOUNT,";		
		$sql .= "  PAYMENT_DESCRIPTION,";		
		$sql .= "  VENDOR_ID,";		
		$sql .= "  CHECK_NUMBER,";		
		$sql .= "  INVOICE,";		
		$sql .= "  PURCHASE_ORDER,";		
		$sql .= "  LAST_UPDATE)";		

		$sql .= " VALUES (";
		$sql .= "'" . $this->dtPaymentDate . "',";	
		$sql .= 	  $this->nPaymentAmount . ",";	
		$sql .= "'" . $this->sPaymentDescription . "',";	
		
		if (is_numeric($this->nVendorID))
		{
			$sql .=  $this->nVendorID . ",";	
		}
		else
		{
			$sql .= "NULL,";	
		}

		if (is_numeric($this->nCheckNumber))
		{
			$sql .=  $this->nCheckNumber . ",";	
		}
		else
		{
			$sql .= "NULL,";	
		}

		if (is_numeric($this->nInvoice))
		{
			$sql .=  $this->nInvoice . ",";	
		}
		else
		{
			$sql .= "NULL,";	
		}

		$sql .= "'" . $this->sPurchaseOrder . "',";	
		$sql .= "SYSDATE()";	
		$sql .= ")";	
		
//DEBUG
//echo "SQL=". $sql . "<BR>";

	
		//Execute the SQL		
		$result=mysqli_query($this->DB->dbConnection,$sql);

//SQL Error
		if (!$result) 
		{

			 $this->sErrorMessage = "PMT004 - FAILED TO INSERT PAYMENT RECORD - " . mysqli_error($this->DB->dbConnection);
			 return FALSE;
		}
		else 
		{
			$this->nPaymentID = mysqli_insert_id($this->DB->dbConnection);
			return TRUE;	
		}
		

	}	

	/*
	 ********************************************************************************
	 * updatePayment()
	 * 
	 * This Updates a PAYMENT record
	 ********************************************************************************
	*/
	function updatePayment()
	{
	
		if ($this->nPaymentID > 0)
		{

			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "PMT005 - FAILED TO OPEN DB: ";
				return FALSE;
			}
			
			$this->escapeSpecialChars();

			$sql =	" UPDATE PAYMENT ";
			$sql .= " SET PAYMENT_DATE =  '" . $this->dtPaymentDate . "'";
			$sql .= ", PAYMENT_AMOUNT = " . $this->nPaymentAmount;
			$sql .= ", PAYMENT_DESCRIPTION = '" . $this->sPaymentDescription . "'";
			if(is_numeric($this->nVendorID))
			{
				$sql .= ", VENDOR_ID = " . $this->nVendorID;
			}
			else
			{
				$sql .= ", VENDOR_ID = NULL ";
			}
			if(is_numeric($this->nCheckNumber))
			{
				$sql .= ", CHECK_NUMBER = " . $this->nCheckNumber;
			}
			else
			{
				$sql .= ", CHECK_NUMBER = NULL ";
			}
			if(is_numeric($this->nInvoice))
			{
				$sql .= ", INVOICE = " . $this->nInvoice;
			}
			else
			{
				$sql .= ", INVOICE = NULL ";
			}
			$sql .= ", PURCHASE_ORDER = '" . $this->sPurchaseOrder . "'";
			$sql .= ", LAST_UPDATE = SYSDATE()";
			$sql .= " WHERE PAYMENT_ID  = " . $this->nPaymentID;
		
//DEBUG
//echo "<BR>SQL=". $sql . "<BR>";

			
			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);

//SQL Error
			if (!$result) {	

				 $this->sErrorMessage = "PMT006 - FAILED TO UPDATE PAYMENT: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;	
			}
		
		}
		else
		{
			 $this->sErrorMessage = "PMT010 - NO PAYMENT_ID SPECIFIED ";
			 return FALSE;
		}
	}

	/*
	 ********************************************************************************
	 * deletePayment()
	 * 
	 * This function deletes a Payment record
	 ********************************************************************************
	*/
	function deletePayment()
	{
		if ($this->nPaymentID > 0)
		{
		
			//Look for Associated Revenues
			if ($this->getPaymentRevenues())
			{
				if (sizeof($this->oPaymentRevenues->aRevenueRecords) > 0)
				{
					$this->sErrorMessage = "PMT013 - Can not delete Payment because it has ";
					$this->sErrorMessage .= "<A HREF='" . ADMIN_DIR . "/RevenueMaintenance.php?PAYMENT_ID=" . $this->nPaymentID;
					$this->sErrorMessage .= "'>" . sizeof($this->oPaymentRevenues->aRevenueRecords) . " revenues</A>.";
					return FALSE;
				}
	
			}
			else
			{
				$this->sErrorMessage = "PMT014 - Failed to retrieve Revenues for this Payment: " . $oPaymentRevenues->sErrorMessage;
				return FALSE;
			}
		
			//Open the DB
			if (!$this->DB->openDB()) 
			{
				$this->sErrorMessage = "PMT007 - FAILED TO OPEN DB: ";
				return FALSE;
			}		

			$sql =	" DELETE FROM PAYMENT";
			$sql .= " WHERE PAYMENT_ID = " . $this->nPaymentID;

//DEBUG
//echo "SQL=". $sql . "<BR>";

			//Execute the SQL		
			$result=mysqli_query($this->DB->dbConnection,$sql);

			//SQL Error
			if (!$result)
			{	
				 $this->sErrorMessage = "PMT008 - FAILED TO DELETE PAYMENT: " . mysqli_error($this->DB->dbConnection);
				 return FALSE;
			}
			else 
			{
				return TRUE;
			}
	
		}
		else
		{
			$this->sErrorMessage = "PMT009 - CAN NOT DELETE PAYMENT BECAUSE NO PAYMENT ID SUPPLIED. ";
			return FALSE;
		}
	}
	
	/*
	 ********************************************************************************
	 * getPaymentErrors()
	 * 
	 * This function creates an array of Payments that have errors 
	 ********************************************************************************
	*/
	function getPaymentErrors()
	{
		$iErrorCount = 0;
		//Open the DB
		if (!$this->DB->openDB()) 
		{
			$this->sErrorMessage = "PMT00X - FAILED TO OPEN DB: ";
			return FALSE;
		}
		
		$this->escapeSpecialChars();
		
		//Find all payments with amounts unequal to their revenues
		$sql =	" SELECT * FROM PAYMENT";
		$sql .= " WHERE PAYMENT.PAYMENT_AMOUNT <> (SELECT SUM(REVENUE.REVENUE_AMOUNT) FROM REVENUE WHERE REVENUE.PAYMENT_ID = PAYMENT.PAYMENT_ID)";		
		
		//DEBUG
		//echo "SQL=". $sql . "<BR>";
			
		$result = NULL;
				
		if(!$this->executeSQL($sql,$result))
		{
			return -1;
		}
		else
		{



			while($row = mysqli_fetch_array($result))
			{
				$description = "Payment: " . $row['PAYMENT_DESCRIPTION'] . ": $" . $row['PAYMENT_AMOUNT'];

				$oNextError = new ErrorObject($row['PAYMENT_ID'],'PAYMENT','Payment Amount does not match revenues',$description,3 );
				
				//Then add the object to the array of found products
				$this->aPaymentErrors[$iErrorCount] = $oNextError;
				
				$iErrorCount++;

			}


		}

		//Find all payments with no revenues
		$sql =	" SELECT * FROM PAYMENT";
		$sql .= " WHERE NOT EXISTS (SELECT * FROM REVENUE WHERE REVENUE.PAYMENT_ID = PAYMENT.PAYMENT_ID)";		
		
		//DEBUG
		//echo "SQL=". $sql . "<BR>";
			
		$result = NULL;
				
		if(!$this->executeSQL($sql,$result))
		{
			return -1;
		}
		else
		{



			while($row = mysqli_fetch_array($result))
			{
				$description = "Payment: " . $row['PAYMENT_DESCRIPTION'] . ": $" . $row['PAYMENT_AMOUNT'];

				$oNextError = new ErrorObject($row['PAYMENT_ID'],'PAYMENT','Payment has no revenues',$description,3 );
				
				//Then add the object to the array of found products
				$this->aPaymentErrors[$iErrorCount] = $oNextError;
				
				$iErrorCount++;

			}


		}

		//Find all Revenues with amounts unequal to their revenues
		$sql =	" SELECT * FROM PAYMENT";
		$sql .= " WHERE PAYMENT.PAYMENT_DATE = '0000-00-00'";		
		
		//DEBUG
		//echo "SQL=". $sql . "<BR>";
			
		$result = NULL;
		
		if(!$this->executeSQL($sql,$result))
		{
			return -1;
		}
		else
		{
			while($row = mysqli_fetch_array($result))
			{
				$description = "Payment: " . $row['PAYMENT_DESCRIPTION'] . ": $" . $row['PAYMENT_AMOUNT'];

				$oNextError = new ErrorObject($row['PAYMENT_ID'],'PAYMENT','Payment date is 0000-00-00',$description,3 );
				
				//Then add the object to the array of found products
				$this->aPaymentErrors[$iErrorCount] = $oNextError;
				
				$iErrorCount++;

			}
		}

		
		$this->DB->closeDB();
		return TRUE;
				
	}

	/*
	 ********************************************************************************
	 * loadPaymentObject()
	 * This function loads a given row from the PAYMENT table into a Payment object.  
	 ********************************************************************************
	*/
	function loadPaymentObject(&$oPayment, &$row)
	{

			$oPayment->nPaymentID = $row['PAYMENT_ID'];			
			$oPayment->nPaymentAmount = $row['PAYMENT_AMOUNT'];			
			$oPayment->sPaymentDescription = $row['PAYMENT_DESCRIPTION'];
			$oPayment->dtPaymentDate = $row['PAYMENT_DATE'];			
			$oPayment->nVendorID = $row['VENDOR_ID'];		
			$oPayment->nCheckNumber = $row['CHECK_NUMBER'];			
			$oPayment->nInvoice = $row['INVOICE'];			
			$oPayment->sPurchaseOrder = $row['PURCHASE_ORDER'];			
			$oPayment->dtLastUpdate= $row['LAST_UPDATE'];			
	}

	/*
	 ********************************************************************************
	 * loadErrorObject()
	 * This function loads a given row from an Error object.  
	 ********************************************************************************
	*/
	function loadErrorObject(&$oError, &$row)
	{

			$oError->nPaymentID = $row['PAYMENT_ID'];			
				
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
		$this->sPaymentDescription = mysqli_real_escape_string($this->DB->dbConnection,$this->sPaymentDescription);	
		$this->sPurchaseOrder = mysqli_real_escape_string($this->DB->dbConnection,$this->sPurchaseOrder);	
	}
	
	
}
	
?>