<?php

function ASPT_drawUserPage( $userID )
{
	echo '<a href="tools.php?page=ptracker-settings&tab=0">Back to Overview Page</a><hr/>';
	
	// Get the user data
	$userInfo = get_user_meta( $userID );
	//print_r($userInfo);
	
	
	$userFullname = $userInfo['first_name'][0].' '.$userInfo['last_name'][0];
	$username = $userInfo['nickname'][0];
	echo '<h1>';
	echo get_avatar( get_the_author_meta( $userID ), 64 );
	echo ' '.$userFullname.' ('.$username.')</h1>';	
	
	
	
	// Firstly get an array of all page IDs this user has marked as read
	global $P_TRACKER;
	$pTrackerUserData = $P_TRACKER->getRows( $userID );
	
	$userReadStatusCheckArray = array();
	foreach( $pTrackerUserData as $userReadInfo )
	{
		$thisPageID = $userReadInfo->page_id;
		$read_date= $userReadInfo->read_date;
		$userReadStatusCheckArray[$thisPageID] = $read_date;
	}
	
	$ptrackerPageArray = array();
	$args = array(
		'meta_key' => 'enableProgressTracking',
		'meta_value' => 'true',
		'post_status' => 'any',
		'posts_per_page' => -1
	);
	$posts = get_pages($args);	
	$mypages = get_pages( array( 'hierarchical' => 0, 'sort_column' => 'menu_order', 'meta_key' => 'enableProgressTracking', 'meta_value' => 'true', 'posts_per_page' => -1) );
	


	$totalSubPageCount = 0;
	foreach( $mypages as $page )
	{
		$pageTitle = $page->post_title;
		$parentPageID = $page->ID;						
		$ptrackerPageArray[$parentPageID][0] = $pageTitle;
		
		// now get sub pages of this page
		$mySubPages = get_pages( array( 'child_of' =>$parentPageID,'sort_column' => 'menu_order' ) );
		
		foreach ( $mySubPages as $page ) 
		{
			$subPageID = $page->ID;
			$subPageTitle = $page->post_title;						
			
			$ptrackerPageArray[$parentPageID][1][] = $subPageID;
			$ptrackerPageArray[$parentPageID][2][] = $subPageTitle;
			$totalSubPageCount++; // Up the total of tracked sub pages
		}
	}
	
	
	$userPageCountTotal = 0;
	
	$userReport = '<table style="padding:5px;">';
	foreach($ptrackerPageArray as $parentID => $trackedPageInfo)
	{
		
		$parentName = $trackedPageInfo[0];
		$userReport.= '<tr><td colspan="2" style="border-top:1px solid #E1E1E1; padding-top:20px;"><h3>'.$parentName.'</h3></td></tr>';
		$subPageArray = $trackedPageInfo[1];
		
		$i=0;
		if($subPageArray)
		{
			foreach($subPageArray as $subPageID)
			{
				$subPageName = $trackedPageInfo[2][$i];
				
				$myClass = 'ptrackerUnreadText';
				$thisReadDate='';
				
				// Check if a key of this value exists in the user read array
				if (array_key_exists($subPageID, $userReadStatusCheckArray))
				{
					$thisReadDate = $userReadStatusCheckArray[$subPageID];
					$myClass = 'ptrackerReadText';				
				}			
				$userReport.= '<tr><td><span class="'.$myClass.'">'.$subPageName.'</span></td>';
				$userReport.='<td>';
				if($thisReadDate)
				{
					$userPageCountTotal++; // up the total read page count by 1
					$thisReadDate = strtotime($thisReadDate);			
					//Formats the Date
					$thisReadDate = date('jS M Y g:i a', $thisReadDate);
					$userReport.='<span style="color:#989898">'.$thisReadDate.'</span>';
				}
				else
				{
					$userReport.='<span style="color:#989898">Not yet read</span>';
				}
				
				
				$userReport.= '</td>';
				$i++;
			}
		}
		//$userReport.='<hr/>';
	
	
	}
	

	//echo $totalRead.'/'.$subPageCount.' marked as read<hr/>';
	if($userPageCountTotal>=1)
	{
		$percentCompleteOverall=round(($userPageCountTotal/$totalSubPageCount)*100, 0);
	}
	else
	{
		$percentCompleteOverall=0;
	}
	
	$progressBarColour = 'red';
	if($percentCompleteOverall>31){$progressBarColour = 'orange';}
	if($percentCompleteOverall>74){$progressBarColour = 'green';}						
	
	echo '<div class="progress" style="width:600px">';
	echo '<span class="'.$progressBarColour.'" style="width: '.$percentCompleteOverall.'%;"><span>'.$percentCompleteOverall.'% complete</span></span>';
	echo '</div>';
	
	
	echo $userReport;
	

}

?>