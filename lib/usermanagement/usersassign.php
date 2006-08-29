<?php
/** 
* TestLink Open Source Project - http://testlink.sourceforge.net/ 
* This script is distributed under the GNU General Public License 2 or later. 
*
* Filename $RCSfile: usersassign.php,v $
*
* @version $Revision: 1.8 $
* @modified $Date: 2006/08/29 19:41:38 $
* 
* Allows assigning users roles to testplans or testprojects
*
* 20060224 - franciscom - changes in session product -> testproject
*/
require_once('../../config.inc.php');
require_once('users.inc.php');
testlinkInitPage($db);

$feature = isset($_GET['feature']) ? $_GET['feature'] : null;
$testprojectID = isset($_SESSION['testprojectID']) ? $_SESSION['testprojectID'] : 0;
$tpID = isset($_SESSION['testPlanId']) ? $_SESSION['testPlanId'] : 0;
$userID = $_SESSION['userID'];

$sqlResult = null;
$action = null;
$testPlans = null;
$bTestproject = false;
$bTestPlan = false;
$featureID = isset($_GET['featureID']) ? $_GET['featureID'] : 0;

if ($feature == "testproject")
{
	$bTestproject = true;
}
else if ($feature == "testplan")
{
	$bTestPlan = true;
}

//postback
$bUpdate = isset($_POST['do_update']) ? 1 : 0;
if ($bUpdate)
{
	$featureID = isset($_POST['featureID']) ? intval($_POST['featureID']) : 0;
	if ($featureID)
	{
		$feature = isset($_POST['feature']) ? $_POST['feature'] : null;
		if ($feature == "testproject")
			$bTestproject = true;
		else if ($feature == "testplan")
			$bTestPlan = true;
	}
}

if ($featureID && $bUpdate)
{
	//remove keys which are no longer used 
	unset($_POST['featureID']);
	unset($_POST['do_update']);
	unset($_POST['feature']);
	$users = $_POST;
	
	if ($bTestproject)
		deleteProductUserRoles($db,$featureID);			
	else if ($bTestPlan)
		deleteTestPlanUserRoles($db,$featureID);					
	
	$subStr = "userRole";
	$subLen = strlen($subStr);
	foreach($users as $userRole => $roleID)
	{
		if ($roleID && (substr($userRole,0,$subLen) == $subStr))
		{
			$userID = intval(substr($userRole,$subLen));
			if ($bTestproject)
				insertUserTestProjectRole($db,$userID,$featureID,$roleID);
			else if ($bTestPlan)
				insertUserTestPlanRole($db,$userID,$featureID,$roleID);
		}
		$sqlResult = 'ok';
		$action = "updated";
	}
}
$userData = getAllUsers($db);

$userFeatureRoles = null;
$features = null;
if ($bTestproject)
{
	$features = getAccessibleProducts($db);
	if (!$featureID)
	{
		if ($testprojectID)
			$featureID = $testprojectID;
		else if (sizeof($features))
		{
			$k = key($features);
			$featureID = $k;
		}
	}
	$userFeatureRoles = getProductUserRoles($db,$featureID);
}
else if($bTestPlan)
{
	$activeFeatures = getAllActiveTestPlans($db,$testprojectID,$_SESSION['filter_tp_by_product']);
	$features = array();
	if (has_rights($db,"mgt_users"))
		$features = $activeFeatures;
	else if (sizeof($activeFeatures))
	{
		for($i = 0;$i < sizeof($activeFeatures);$i++)
		{
			$f = $activeFeatures[$i];
			if (has_rights($db,"testplan_planning",null,$f['id']))
				$features[] = $f;
		}
	}
	//if nothing special was selected, use the one in the session or the first
	if (!$featureID)
	{
		if ($tpID)
			$featureID = $tpID;
		else if (sizeof($features))
			$featureID = $features[0]['id'];
	}
	$userFeatureRoles = getTestPlanUserRoles($db,$featureID);
}
$roleList = getAllRoles($db);

$smarty = new TLSmarty();
$smarty->assign('mgt_users',has_rights($db,"mgt_users"));
$smarty->assign('role_management',has_rights($db,"role_management"));
$smarty->assign('tp_user_role_assignment', has_rights($db,"mgt_users") ? "yes" : has_rights($db,"user_role_assignment"));
$smarty->assign('tproject_user_role_assignment', has_rights($db,"mgt_users") ? "yes" : has_rights($db,"user_role_assignment",null,-1));
$smarty->assign('optRights', $roleList);
$smarty->assign('userData', $userData);
$smarty->assign('userFeatureRoles',$userFeatureRoles);
$smarty->assign('featureID',$featureID);
$smarty->assign('feature',$feature);
$smarty->assign('result',$sqlResult);
$smarty->assign('action',$action);
$smarty->assign('features',$features);
$smarty->display('usersassign.tpl');
?>