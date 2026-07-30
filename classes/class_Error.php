<?php
/*
*******************************************************************
class_Error.php
This PHP file defines Error object class
NOTES
Date        Change
-------------------------------------------------------------
2015-12-18 	Removed unneeded rootpath and includes and refaactored
*******************************************************************
*/	
class ErrorObject
{

	//Properties
	var $nRecordId = 0;
	var $sErrorType = '';
	var $sErrorDesc = '';
	var $sRecordDesc = '';
	var $nErrorSeverity = 0;
	


	//Constructor
   function __construct($nThisRecordId,$sThisErrorType, $sThisErrorDesc, $sThisRecordDesc, $nThisErrorSeverity) 
   {
		$this->nRecordId = $nThisRecordId;
		$this->sErrorType = $sThisErrorType;
		$this->sErrorDesc = $sThisErrorDesc;
		$this->sRecordDesc = $sThisRecordDesc;
		$this->nErrorSeverity = $nThisErrorSeverity;
   }
  
}
	
?>