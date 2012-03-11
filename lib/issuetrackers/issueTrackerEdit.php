<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * @filesource	issueTrackerEdit.php
 *
 * @author	francisco.mancardi@gmail.com
 * @internal revisions
 * @since 1.9.4
 * 20120311 - franciscom - TICKET 4904: integrate with ITS on test project basis
**/
require_once("../../config.inc.php");
require_once("common.php");
testlinkInitPage($db,false,false,"checkRights");
$templateCfg = templateConfiguration();

list($args,$gui,$commandMgr) = initScript($db);


$pFn = $args->doAction;
$op = null;
if(method_exists($commandMgr,$pFn))
{
	$op = $commandMgr->$pFn($args,$_REQUEST);
}

// new dBug($op);
renderGui($db,$args,$gui,$op,$templateCfg);




/**
 */
function renderGui(&$dbHandler,&$argsObj,$guiObj,$opObj,$templateCfg)
{
    $smartyObj = new TLSmarty();
    $renderType = 'none';
    
    // key: gui action
    // value: next gui action (used to set value of action button on gui)
    $actionOperation = array('create' => 'doCreate', 'edit' => 'doUpdate',
                             'doDelete' => '', 'doCreate' => 'doCreate', 
                             'doUpdate' => 'doUpdate');

	// Get rendering type and set variable for template
    switch($argsObj->doAction)
    {
        case "edit":
        case "create":
        case "doDelete":
		case "doCreate":
      	case "doUpdate":
            $key2loop = get_object_vars($opObj);
            foreach($key2loop as $key => $value)
            {
                $guiObj->$key = $value;
            }
            $guiObj->operation = $actionOperation[$argsObj->doAction];

            $renderType = 'redirect';
            $tpl = is_null($opObj->template) ? $templateCfg->default_template : $opObj->template;
            $pos = strpos($tpl, '.php');
           	if($pos === false)
           	{
            	$tplDir = (!isset($opObj->template_dir)  || is_null($opObj->template_dir)) ? $templateCfg->template_dir : $opObj->template_dir;
                $tpl = $tplDir . $tpl;      
            	$renderType = 'template';
            }
        break;
    }

	// execute rendering
	// new dBug($tpl);
	// new dBug($guiObj);
	
    switch($renderType)
    {
        case 'template':
        	$smartyObj->assign('gui',$guiObj);
		    $smartyObj->display($tpl);
        	break;  
 
        case 'redirect':
		      header("Location: {$tpl}");
	  		  exit();
        break;

        default:
       	break;
    }
}

/**
 * 
 */
function initScript(&$dbHandler)
{
	$mgr = new issueTrackerCommands($dbHandler);
	$args = init_args(array('doAction' => $mgr->getGuiOpWhiteList()));
	$gui = initializeGui($dbHandler,$args,$commandMgr);
	return array($args,$gui,$mgr);
}

/**
 * @return object returns the arguments for the page
 */
function init_args($whiteLists)
{
	$_REQUEST = strings_stripSlashes($_REQUEST);
	$args = new stdClass();

	$iParams = array("id" => array(tlInputParameter::INT_N),
					 "doAction" => array(tlInputParameter::STRING_N,0,20),
					 "name" => array(tlInputParameter::STRING_N,0,100),
					 "cfg" => array(tlInputParameter::STRING_N,0,1000),
					 "type" => array(tlInputParameter::INT_N));
	
	//new dBug($_REQUEST);
		
	R_PARAMS($iParams,$args);

	// sanitize via whitelist
	foreach($whiteLists as $inputKey => $allowedValues)
	{
		if( property_exists($args,$inputKey) )
		{
			if( !isset($allowedValues[$args->$inputKey]) )
			{
				$msg = "Input parameter $inputKey - white list validation failure - " .
					   "Value:" . $args->$inputKey . " - " .
					   "File: " . basename(__FILE__) . " - Function: " . __FUNCTION__ ; 
				tLog($msg,'ERROR');
				throw new Exception($msg);
			}
		}
	}

	$args->currentUser = $_SESSION['currentUser'];

	return $args;
}


/**
 * 
 *
 */
function initializeGui(&$dbHandler,&$argsObj,&$commandMgr)
{
	$gui = new stdClass();
	$gui->main_descr = '';
	$gui->action_descr = '';
	$gui->user_feedback = array('type' => '', 'message' => '');
	$gui->mgt_view_events = $argsObj->currentUser->hasRight($dbHandler,'mgt_view_events');
	
	return $gui;
}


/**
 * @param $db resource the database connection handle
 * @param $user the current active user
 * 
 * @return boolean returns true if the page can be accessed
 */
function checkRights(&$db,&$user)
{
	return $user->hasRight($db,'issuetracker_management');
}
?>
