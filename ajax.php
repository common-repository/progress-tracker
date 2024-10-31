<?php
/*	--------------------------------
 FRONTEND CALLS
-------------------------------- */
function ASPT_updateUserPage ()
{
	global $P_TRACKER;
	$INFO = $P_TRACKER->getPostInfo();
	$success = $P_TRACKER->updateUserInfo( $INFO );	
	
	echo ( $success ? 'true' : 'false' );
	die();
}
/*	--------------------------------
 POST EDIT CALLS
-------------------------------- */
function ASPT_parentHasTracking ()
{
	global $P_TRACKER;
	$INFO = $P_TRACKER->getPostInfo();
	$parentEnabled = $P_TRACKER->parentTrackingEnabled( $INFO['parentID'] );
	
	echo ( $parentEnabled ? 'true' : 'false' );
	die();
}
?>