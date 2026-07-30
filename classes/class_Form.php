<?php

/*
**********************************************************************
Form Class
 
This file contains defnition of the Form class which controls
common form values and functions
NOTES
Date        Change
-------------------------------------------------------------
2015-12-19	Refactored and removed unneeded includes
2017-03-06	Added class to form buttons
**********************************************************************
*/

class Form
{
	
	//Variables needed for form rendering
	var $sMessage;
	var $nMessageType;
	var $nFormMode;

	//Array to store values from the Form
	var $aFormFieldValues;

	//Array to store Categories
	var $aFormCategories;

	//Form Mode Constants
	const FORM_MODE_NEW = 0;  	//Form is not editing any record in particular (Add or Search)
	const FORM_MODE_EDIT = 1;	//Form is editting a single record (Update)
	const FORM_MODE_SELECT = 2; //Form is displaying a list of records to select from
	
	//Constructor
	function __construct() 
   	{
		$this->aFormFieldValues = array();
		$this->aFormCategories = array();
   	}

	/********************************************************************************
	 * renderFormMessage
	 * 
	 * This function renders the form message.
	 ********************************************************************************
	*/
	function renderFormMessage()
	{
		
		switch ($this->nMessageType) 
		{
			case MESSAGE_TYPE_INFO :
				echo "<IMG SRC=\"" . ADMIN_IMG_DIR . "/Info.png\">";
				break;
			case MESSAGE_TYPE_WARNING :
				echo "<IMG SRC=\"" . ADMIN_IMG_DIR . "/Warning.png\">";
				break;
			case MESSAGE_TYPE_ERROR :
				echo "<IMG SRC=\"" . ADMIN_IMG_DIR . "/Error.png\">";
				break;
		}
		
		echo "<FONT";
		if (isset($nMessageType) && $nMessageType == MESSAGE_TYPE_ERROR)
		{
			echo " COLOR = \"red\"";
		}
		echo ">";
		
		echo "&nbsp;&nbsp;" . $this->sMessage . "</FONT>";
	
	}
	
	/********************************************************************************
	 * renderButtons
	 * 
	 * This function renders the form buttons
	 ********************************************************************************
	*/
	function renderButtons()
	{
	
		if ($this->nFormMode == FORM_MODE_NEW)
		{
			echo "<input type=\"submit\" name=\"btnSearch\" class=\"formButton\" value=\"Search\" />";
			echo "<input type=\"submit\" name=\"btnAdd\" class=\"formButton\" onclick=\"javascript: return validate_form() \" value=\"Add\" />";
			echo "<input type=\"submit\" name=\"btnClear\" class=\"formButton\" value=\"Clear\" />";
		}
		else if ($this->nFormMode == FORM_MODE_EDIT)
		{
			echo "<input type=\"submit\" name=\"btnUpdate\" class=\"formButton\" onclick=\"javascript: return validate_form() \" value=\"Update\" />";
			echo "<input type=\"submit\" name=\"btnDelete\" class=\"formButton\" onclick=\"return show_confirm('Are you sure you want to delete?')\" value=\"Delete\" />";
			echo "<input type=\"submit\" name=\"btnClear\" class=\"formButton\" value=\"Clear\" />";
			echo "<input type=\"submit\" name=\"btnCopy\" class=\"formButton\" value=\"Copy\" />";
		}
		else if ($this->nFormMode == FORM_MODE_EDIT_NO_DELETE)
		{
			echo "<input type=\"submit\" name=\"btnUpdate\" class=\"formButton\" onclick=\"javascript: return validate_form() \" value=\"Update\" />";
			echo "<input type=\"submit\" name=\"btnClear\" class=\"formButton\" value=\"Clear\" />";
		}
		else if ($this->nFormMode == FORM_MODE_SEARCH)
		{
			echo "<input type=\"submit\" name=\"btnSearch\" class=\"formButton\" value=\"Search\" />";
			echo "<input type=\"submit\" name=\"btnClear\" class=\"formButton\" value=\"Clear\" />";
		}
		else if ($this->nFormMode == FORM_MODE_SELECT)
		{
			echo "<input type=\"submit\" name=\"btnCancel\" class=\"formButton\" value=\"Cancel\" />";
		}
	}

	/********************************************************************************
	 * renderImagePreview
	 * 
	 * This function renders an image with a preview upong hovering over it.
	 ********************************************************************************
	*/
	function renderImagePreview($sImagePath, $sAltText)
	{
		//Default Image if none is passed
		if (empty($sImagePath))
		{
			echo "<IMG SRC='" . ADMIN_IMG_DIR . "/" . NO_IMAGE . "' BORDER=0 Height=40 >";
		}
		else
		{
			//Check if Image file even exists
			if  (!file_exists($sImagePath))
			{
				echo "<IMG SRC='" . ADMIN_IMG_DIR . "/" . IMAGE_NOT_FOUND . "' BORDER=0 Height=40 >";
			
			}
			else
			{
				try
				{
					//Get the dimaensions of the image
					$aImageSize = getimagesize($sImagePath);
					echo "<IMG SRC='" . $sImagePath . "' alt='". $sAltText . "' BORDER=0 Height=40 onmouseover=\"showtrail('" . $sImagePath . "', '" . $sAltText . "'," . $aImageSize[0] . "," . $aImageSize[1] . ")\" onmouseout=\"hidetrail()\">";
				}
				catch (Exception $e)
				{
					echo "<IMG SRC='" . ADMIN_IMG_DIR . "/" . INVALID_IMAGE . "' BORDER=0 Height=40 >";
				}
			}
		}
	}	
	
	/********************************************************************************
	 * renderImage
	 * 
	 * This function renders an image with a preview upong hovering over it.
	 ********************************************************************************
	*/
	function renderImage($sImageName)
	{
	
		//Default Image if none is passed
		if (empty($sImageName))
		{
			echo "<IMG SRC='" . ADMIN_IMG_DIR . "/" . NO_IMAGE . "' BORDER=0 >";
		}
		else
		{
		
			$sImagePath = $sImageName;
		
			//Check if Image file even exists
			if  (!file_exists($sImagePath))
			{
				echo "<IMG SRC='" . ADMIN_IMG_DIR . "/" . IMAGE_NOT_FOUND . "' BORDER=0 >";
			}
			else
			{
				try
				{
					//Get the dimaensions of the image
					$aImageSize = getimagesize($sImagePath);
					echo "<IMG SRC='" . $sImagePath . "' BORDER=0 >";
				}
				catch (Exception $e)
				{
					echo "<IMG SRC='" . ADMIN_IMG_DIR . "/" . INVALID_IMAGE . "' BORDER=0 >";
				}
			}
		}
	}	

}


?>