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
class ErrorLog
{

    //Properties
    var $sModule = '';
    var $sFunction = '';
    var $sErrorMessage = '';
    var $sRecordType = '';
    var $nRecordId = 0;

    //Constructor
    function __construct($sThisModule, $sThisFunction, $sThisErrorMessage, $nThisRecordId, $sThisRecordType)
    {
        $this->sModule = $sThisModule;
        $this->sFunction = $sThisFunction;
        $this->sErrorMessage = $sThisErrorMessage;
        $this->nRecordId = $nThisRecordId;
        $this->sRecordType = $sThisRecordType;
    }

    /*
     ********************************************************************************
     * writeErrorLog()
     * 
     * This function writes an error to the PHP error log file.
     ********************************************************************************
     */
    function writeErrorLog()
    {
        $sError = "\nERROR:";
        $sError .= "\n  Module: {$this->sModule}";
        $sError .= "\n  Function: {$this->sFunction}";
        $sError .= "\n  Record ID: {$this->nRecordId}";
        $sError .= "\n  Record Type: {$this->sRecordType}";
        $sError .= "\n  Error Message: {$this->sErrorMessage}";
        error_log($sError);
    }

}

?>