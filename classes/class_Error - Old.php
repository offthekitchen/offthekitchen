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
class Error
{

	//Properties
	var $sErrorDesc = NULL;
	var $nErrorID = 0;
	var $nErrorSeverity = 0;
	
	var $aErrorItems = array();


	//Constructor
   function __construct($nThisErrorID,$sThisErrorDesc,$nThisErrorSeverity) 
   {
		$this->nErrorID = $nThisErrorID;
		$this->sErrorDesc = $sThisErrorDesc;
		$this->nErrorSeverity = $nThisErrorSeverity;
   }
   
   /*
 ********************************************************************************
 * renderError
 * 
 * This function renders an error as HTML 
 ********************************************************************************
*/
  function renderErrors($sMaintenanceForm)
  {

	//Generate a unique DIV name for this error
	$sDivID = "DIV_{$this->nErrorID}";
	
	echo "<a name=\"BM{$sDivID}\" onClick=\"shoh('{$sDivID}');\"><img src=\"". ADMIN_IMG_DIR ."/plus.gif\" name=\"img{$sDivID}\" width=\"11\" height=\"11\" 	border=\"0\" ></a>&nbsp;";

	switch($this->nErrorSeverity) 
	{
		case ERROR_SEVERITY_INFO :
			echo "<IMG SRC=\"" . ADMIN_IMG_DIR . "/Info.png\">";
			break;
		case ERROR_SEVERITY_WARNING :
			echo "<IMG SRC=\"" . ADMIN_IMG_DIR . "/Warning.png\">";
			break;
		case ERROR_SEVERITY_ERROR :
			echo "<IMG SRC=\"" . ADMIN_IMG_DIR . "/Error.png\">";
			break;
		default:
			echo "<IMG SRC=\"" . ADMIN_IMG_DIR . "/Info.png\">";
			break;
	}
	
	echo "<B>{$this->sErrorDesc}</B>";

	//HTML Comment makes this program easier to debug by viewing source
	echo "\n<!-- BEGIN DIV for {$this->sErrorDesc} ERROR -->\n\n";
	//Render a collapsed DIV for this group of records
	echo "\n<div style=\"display:none; position:relative; border-width: 1px; border-style: dotted; border-color: green; left:30px\" id=\"{$sDivID}\" width=\"100%\";  >\n";

	if (sizeof($this->aErrorItems) > 0)
	{
				
		foreach ($this->aErrorItems as $aErrorItem)
		{		

			echo "<A HREF=\"{$sMaintenanceForm}?ID={$aErrorItem['ID']}\">{$aErrorItem['NAME']}</A><BR>"; 
		}

	}
	else
	{
		echo "<B>NONE</B>";
	}
	//HTML Comment makes this program easier to debug by viewing source
	echo "\n<!-- END DIV for {$this->sErrorDesc} ERROR -->\n\n";


  }
  
}
	
?>