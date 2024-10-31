<?php
function ASPT_drawSettingsPage ( $PT )
{
	global $P_TRACKER;
	$ops = $PT->ops;
	
	// Now get the variables for each settings
	$defaults = $P_TRACKER->defaults();
	foreach ($defaults as $key => $value)
	{
		$$key = $ops[$key];
	}
	
	?>
		
		
	<div class="as-tabbuttons-wrap unselectable">
		<div class="as-tabbutton first" id="as_tabbutton_0">User Summary</div>
		<div class="as-tabbutton" id="as_tabbutton_1">Page Summary</div>		
		<div class="as-tabbutton" id="as_tabbutton_2">Settings</div>
		<div class="as-tabbutton" id="as_tabbutton_3">Buttons</div>					
		<div class="as-tabbutton last" id="as_tabbutton_4">Help</div>
		<br class="clearB"/>
	</div>
	
	
	
	<div class="as-tabs-wrap">
	
	
		<!-- TAB 0.......................... -->
		<div class="as-tab" id="as_tab_0">
		
				<h3>User Summary</h3><hr/>
				
				
				<?php
				
				// Create an array of the pages and subpages with tracking. The key is the page ID of parent page
				$ptrackerPageArray = array();
				$args = array(
					'meta_key' => 'enableProgressTracking',
					'meta_value' => 'true',
					'post_status' => 'any',
					'posts_per_page' => -1
				);
				$posts = get_pages($args);	
				$mypages = get_pages( array( 'hierarchical' => 0, 'sort_column' => 'menu_order', 'meta_key' => 'enableProgressTracking', 'meta_value' => 'true', 'posts_per_page' => -1) );
				
				
				$subPageCount = 0;
				$pageReadStatusCheckArray = array();				
				
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
						$subPageCount++; // Up the total of tracked sub pages
						
						$pageReadStatusCheckArray[$subPageID]=""; // create empty array value for this
					}
				}
				
			//	echo '<pre>';
			//	print_r($ptrackerPageArray);
			//	echo '</pre>';
				
				
				//$pTrackerUserData = progressTracker::getRows();
				$pTrackerUserData = $P_TRACKER->getRows();
				
				$userReadStatusCheckArray = array();
				
				foreach( $pTrackerUserData as $userReadInfo )
				{
					// Put all data into an array
					$userID = $userReadInfo->user_id;
					$thisPageID = $userReadInfo->page_id;						
					$userReadStatusCheckArray[$userID][]=$thisPageID;
					
					if(!is_array($pageReadStatusCheckArray[$thisPageID]) )
					{
						$pageReadStatusCheckArray[$thisPageID] = array();
					}
										
					// Put data into another array for easy PAGE lookup
					$pageReadStatusCheckArray[$thisPageID][] = $userID;
				}	
				
				//echo '<pre>';
				//print_r($pageReadStatusCheckArray);
				//echo '</pre>';
				echo '<table id="userTable">';
				echo '<thead><tr><th>Name</th><th>Username</th><th>Role</th><th width="200px">Percent Complete</th></tr></thead>';
				
				$blogusers = get_users();
				
				// Array of WP_User objects.
				foreach ( $blogusers as $userInfo )
				{
					$userID = $userInfo->ID;
					
				
					$firstName= esc_html( $userInfo->first_name );
					$surname= esc_html( $userInfo->last_name );		
					$username = $userInfo->user_login;
					$roles = $userInfo->roles;
					if($roles)
					{
						$userlevel = $roles[0];
					}
					else
					{
						$userlevel = "";	
					}	
					
					$totalRead=0;
					$percentCompleteOverall=0;						
					
					echo '<tr>';
					echo '<td><strong><a href="' . $_SERVER["REQUEST_URI"] . '&view_user=' . $userID . '">'.$surname.', '.$firstName.'</a></strong></td>';
					echo '<td>'.$username.'</td>';
					echo '<td>'.$userlevel.'</td>';
					echo '<td>';
					
					$thisUserReadArray = array();
					
					if(isset($userReadStatusCheckArray[$userID]))
					{						
						$thisUserReadArray = $userReadStatusCheckArray[$userID];
					}
					
					
					foreach($ptrackerPageArray as $pageInfo)
					{
						//echo '<b>'.$pageInfo[0].'</b><br/>';
						
						if(isset($pageInfo[1]))
						{
							$subPageArray = $pageInfo[1];
							if($subPageArray)
							{
								foreach($subPageArray as $subPageID)
								{							
									//echo $subPageID;
									
									// Check if this is in the array
									if(in_array($subPageID, $thisUserReadArray)==true)
									{
										//echo 'READ';
										$totalRead++;
									}
									//echo '<br/>';
								}
							}
						}
					}
					
					
					//echo $totalRead.'/'.$subPageCount.' marked as read<hr/>';
					if($totalRead>=1)
					{
						$percentCompleteOverall=round(($totalRead/$subPageCount)*100, 0);
					}
					else
					{
						$percentCompleteOverall=0;
					}
					
					
					$progressBarColour = 'red';
					if($percentCompleteOverall>31){$progressBarColour = 'orange';}
					if($percentCompleteOverall>74){$progressBarColour = 'green';}						
					
					echo '<a href="' . $_SERVER["REQUEST_URI"] . '&view_user=' . $userID . '">';
					echo '<div class="progress">';
					echo '<span class="'.$progressBarColour.'" style="width: '.$percentCompleteOverall.'%;"><span>'.$percentCompleteOverall.'%</span></span>';
					echo '</div>';
					echo '</a>';
					
					
					echo '</td>';
					echo '</tr>';
				}						
				
				echo '</table>';
				?>
		</div>
		
		<!-- TAB 1.......................... -->					
		<div class="as-tab" id="as_tab_1" style="display:none;">
		
				<h2>Page Summary</h2><hr/>
				The following page shows how many of your users have completed each of your tracked pages<br/><br/>
				
				
				
				<?php
				// get array of user IDs
				$blogusers = get_users( array( 'fields' => array( 'ID' ) ) );
				$userArray = array();
				// Array of WP_User objects.
				foreach ( $blogusers as $user )
				{
					$userArray[] = $user->ID;
				}	
				$totalUsers = count ($userArray);
				
				$mypages = progressTracker::getTrackedPages();
				
				$trackedParentCount = count($mypages);
				
								
				$subPageCount = 0;
				if($trackedParentCount)
				{	
					foreach( $mypages as $page )
					{
						if ( ! empty( $page->ID ) ) {
						
							$pageTitle = $page->post_title;
							$parentPageID = $page->ID;												
							
							echo '<div class="pageBreakdownDiv">';
							
							echo '<h3>'.$pageTitle.'</h3>';
							echo '<i>[ptracker parent='.$parentPageID.']</i><br/><br/>';
							// now get sub pages of this page
							$mySubPages = get_pages( array( 'child_of' =>$parentPageID,'sort_column' => 'menu_order' ) );
							
							foreach ( $mySubPages as $page ) 
							{
								
								
								if ( ! empty( $page->ID ) ) {
								
									$subPageID = $page->ID;
									$subPageTitle = $page->post_title;
									echo $subPageTitle.'<br/>';
									
									$thisCheckPageArray = $pageReadStatusCheckArray[$subPageID];
									
									// Check the Spage array of this Spage
									$usersDoneCount=0;
									foreach($userArray as $userID)
									{
										if(is_array($thisCheckPageArray))
										{
											if(in_array($userID, $thisCheckPageArray))
											{
												$usersDoneCount++;
											}
										}
										
									}
									$percentUsersDone = round(($usersDoneCount/$totalUsers *100));
									
									$progressBarColour = 'red';
									if($percentUsersDone>31){$progressBarColour = 'orange';}
									if($percentUsersDone>74){$progressBarColour = 'green';}						
							
									echo '<div class="progress" style="width:600px">';
									echo '<span class="'.$progressBarColour.'" style="width: '.$percentUsersDone.'%;"><span>'.$percentUsersDone.'%</span></span>';
									echo '</div>';							
									//echo '<h3>'.$percentUsersDone.'%.</h3>';
									
									echo '<hr/>';
								}
							}	
							echo '</div>';					
						}
					}
				}				
				
				?>
				
		</div>		
	
	
		
		
		<!-- TAB 2.......................... -->
		<div class="as-tab" id="as_tab_2" style="display:none;">
			
				<h3>Page Navigation</h3><hr>
				<table class="settingsTable">
					<tr>
						<td style="width:160px;"><label>Button location:</label></td>
						<td><select name="navButtonLocation" id="navButtonLocation">
								<option value="top"<?php if ( 'top' == $navButtonLocation ) { echo ' SELECTED'; } ?>>Top</option>
								<option value="bottom"<?php if ( 'bottom' == $navButtonLocation ) { echo ' SELECTED'; } ?>>Bottom</option>
								<option value="both"<?php if ( 'both' == $navButtonLocation ) { echo ' SELECTED'; } ?>>Top and bottom</option>
							</select></td>
					</tr>
                    
					
					<tr>
						<td><label for="subpageListStyle">Subpage list style:</label></td>
						<td><select name="subpageListStyle" id="subpageListStyle">
							<option value="oneCol"<?php if ( 'oneCol' == $subpageListStyle ) { echo ' SELECTED'; } ?>>Single List</option>						
							<option value="twoCol"<?php if ( 'twoCol' == $subpageListStyle ) { echo ' SELECTED'; } ?>>Two Column List</option>
							<option value="excerpt"<?php if ( 'excerpt' == $subpageListStyle ) { echo ' SELECTED'; } ?>>List with Excerpt</option>
							</select>
						</td>
					</tr>					

					<tr>
						<td style="width:160px;"><label>Numbering:</label></td>
						<td><select name="subPageNumberStyle" id="subPageNumberStyle">
								<option value="numeric"<?php if ( $subPageNumberStyle == 'numeric'  ) { echo ' SELECTED'; } ?>>Numbers</option>
								<option value="roman"<?php if ( $subPageNumberStyle == 'roman'  ) { echo ' SELECTED'; } ?>>Roman Numerals</option>
								<option value="none"<?php if ( $subPageNumberStyle == 'none'  ) { echo ' SELECTED'; } ?>>None</option>                                                                
							</select></td>
					</tr>    					
                    
					
					<tr>
						<td colspan="2"><br/></td>
					</tr>                    
					<tr>
						<td colspan="2"><h3>Completed Toggler</h3><hr></td>
					</tr>
                    
                    <tr>
                    <td colspan="2">
                    <input type="checkbox" id="autoMarkProgress" name="autoMarkProgress" <?php if($autoMarkProgress=="on"){echo 'checked';}?> /><label for="autoMarkProgress">Automatically record progression (mark as complete button will be removed)</label>
                    </tr>
                    
                    
					<tr>
						<td><label>Un-marked text:</label></td>
						<td><input type="text" value="<?php echo $unMarkedText; ?>" id="unMarkedText" name="unMarkedText" /></td>
					</tr>
					
					<tr>
						<td><label>Marked text:</label></td>
						<td><input type="text" value="<?php echo $markedText; ?>" id="markedText" name="markedText" /></td>
					</tr>
					<tr>
						<td><label for="readButtonLocation">Toggle Button Location</label></td>
						<td><select name="readButtonLocation" id="readButtonLocation">
							<option value="top"<?php if ( 'top' == $readButtonLocation ) { echo ' SELECTED'; } ?>>Top of Page</option>
							<option value="bottom"<?php if ( 'bottom' == $readButtonLocation ) { echo ' SELECTED'; } ?>>Bottom of Page</option>
							<option value="custom"<?php if ( 'custom' == $readButtonLocation ) { echo ' SELECTED'; } ?>>Custom (use shortcode)</option>
							</select>
						</td>
					</tr>
                    
                    <tr>
                    <td colspan="2">
                    <script>
					jQuery( "#readButtonLocation" ).change(function() {

						  var newValue =  jQuery('select[name=readButtonLocation]').val();
						  if(newValue=="custom")
						  {
							jQuery("#customShortcodeInfo").show("fast");
						  }
						  else
						  {
							jQuery("#customShortcodeInfo").hide("fast");
						 }
					});
					</script>
                    <?php
					$cssStr="display:none";
					if($readButtonLocation=="custom")
					{
						$cssStr='display:block';
					}
					
					echo '<div id="customShortcodeInfo" style="'.$cssStr.'; border:1px solid #ccc; padding:5px; background:#fff;">';
					echo 'Use the shortcode [ptracker-toggle] to display the toggler on your page.';
					echo '</div>';
					
					?>
                    </td>
                    </tr>

                    
                    <tr>
                    <td colspan="2">
                    <input type="checkbox" id="allowUserReset" name="allowUserReset" <?php if($allowUserReset=="on"){echo 'checked';}?> /><label for="allowUserReset">Allow users to reset their progress</label>
                    </tr>
					
					
					<tr>
						<td colspan="2"><br/></td>
					</tr>
					
					<tr>
						<td colspan="2"><h3>Quick Jump Menu</h3><hr></td>
					</tr>
					
					<tr>
						<td><label for="showQuickJumpList">Show on subpages:</label></td>
						<td><select name="showQuickJumpList" id="showQuickJumpList">
							<option value="top"<?php if ( 'top' == $showQuickJumpList ) { echo ' SELECTED'; } ?>>Menu at top</option>
							<option value="bottom"<?php if ( 'bottom' == $showQuickJumpList  || $showQuickJumpList=="true" ) { echo ' SELECTED'; } ?>>Menu at bottom</option>
							<option value="both"<?php if ( 'both' == $showQuickJumpList ) { echo ' SELECTED'; } ?>>Menu at both</option>
							<option value="none"<?php if ( 'none' == $showQuickJumpList ) { echo ' SELECTED'; } ?>>No menu</option>
							</select>
						</td>
					</tr>
                    
					<tr>
						<td colspan="2"><h3>Student Progress Display</h3><hr></td>
					</tr>
                    <tr>
						<td><label for="showStudentProgress">Show progress on topic menu page:</label></td>
						<td><select name="showStudentProgress" id="showStudentProgress">
							<option value="radial"<?php if ( 'radial' == $showStudentProgress ) { echo ' SELECTED'; } ?>>Show as radial progress</option>
							<option value="bar"<?php if ( 'bar' == $showStudentProgress ) { echo ' SELECTED'; } ?>>Show as bar</option>
							<option value=""<?php if ( '' == $showStudentProgress) { echo ' SELECTED'; } ?>>Do not show progress</option>                            
							</select>
						</td>
					</tr>                   
					
				</table>
				
				<input type="submit" name="progress_tracker_submit" value="Update Options" class="button-primary"/>
					
		</div>
		
		
		<!-- TAB 3.......................... -->
		<div class="as-tab" id="as_tab_3" style="display:none;">
				<table class="settingsTable">
					<tr>
						<td width="150px"><label>Start button text:</label></td>
						<td><input type="text" value="<?php echo $startLinkText ?>" id="startLinkText" name="startLinkText" /></td>
					</tr>
				
				
				
					<tr>
						<td width="150px"><label>Next button text:</label></td>
						<td><input type="text" value="<?php echo $nextLinkText; ?>" id="nextLinkText" name="nextLinkText" /></td>
					</tr>
					
					<tr>
						<td><label>Previous button text:</label></td>
						<td><input type="text" value="<?php echo $backLinkText; ?>" id="backLinkText" name="backLinkText" /></td>
					</tr>
					
					<tr>
						<td colspan="2"><br/></td>
					</tr>
                   </table>
			
				<h3>Button Set</h3><hr>
				<?php
				// ICON OPTION
				//---
				// Get the contents of the image dir
				$ptrackerButtonDir = PTRACKER_PATH . '/images/buttons/';
				$myIcons = scandir( $ptrackerButtonDir );
				
				//make array for the icon options
				$buttonArray = array();
				
				//add the 'No Icon' option
				$buttonArray[] = '';
				
				//add the other images
				foreach( $myIcons as $imageRef ) {
					if( $imageRef != "." && $imageRef != ".." ) {
						$buttonArray[] = $imageRef;
					}
				}
										
				//work out a nice number of columns
				$maxCols = 4;
				$buttonPairs = 0.5 * ( count( $buttonArray ) + 1 );
				$Cols = ( $buttonPairs < 2 * $maxCols ) ? ceil( 0.5 * $buttonPairs ) : $maxCols;
				
				//draw table
				$column = 1;
				$buttonIconDir = PTRACKER_PLUGIN_URL . '/images/buttons/';
				echo '<table class="iconTable">';
				
				
				$i = 0;
				while ( $i < $buttonPairs )
				{
					echo ( $column == 1 ? '<tr>' : '' );
					
					$radioButton = '<input type="radio" value="' . $i. '" name="buttonIconID" id="buttonSet' . $i. '"' . ( $i == $buttonIconID ? ' CHECKED' : '' ) . '>';
					echo '<td>';
					if ( $i == 0 ) //draw the 'no icon' option
					{
						echo $radioButton . '<label for="buttonSet' . $i. '">None</label>';
					} 
					else
					{
						echo '<label for="buttonSet' . $i . '">';
						echo '<img src="' . $buttonIconDir . 'button' . $i . '_back.png" width="60">';
						echo '<img src="' . $buttonIconDir . 'button' . $i . '_next.png" width="60"><br/>';
						echo $radioButton;
						echo 'Set ' . $i . '</label>';
					}
					echo '</td>';
					
					echo ( $column == $Cols || $i == $buttonPairs - 1 ? '</tr>' : '' );
					
					$column = ( $column == $Cols ) ? 1 : $column + 1; 
					$i++;
				}
				
				echo '</table>';
				?>
				
				<input type="submit" name="progress_tracker_submit" value="Update Options" class="button-primary"/>
				
		
		</div>
		
	
		<!-- TAB 4.......................... -->					
		<div class="as-tab" id="as_tab_4" style="display:none;">
		
				<h3>How to use this plugin</h3><hr/>
				<p>
				<i>This plugin only works for logged in users</i><br/>
				When creating or editing a page look for the 'Progress Tracking' tickbox in the Page Atributes settings. If this is ticked, all subpages of this parent page will have
				the 'Mark as read' option which allows your students and users to incidate when they are finished with a page.<br/>
				<br/>Next time they login they will be shown which pages they have yet to read.<br/>
				It also adds  handy 'next' and 'back' buttons to your sub pages so they quickly navigate between pages in a topic.
				</p>
				
		</div>
	
	
	
	</div><!-- close as-tabs-wrap -->
	
	<input type="hidden" name="version" value="<?php echo $PT->version; ?>" />
	
	
	<?php
	if ( isset( $_POST['currentTab'] ) ) {
		$openTab = $_POST['currentTab'];
	} elseif ( isset( $_GET['tab'] ) ) {
		$openTab = $_GET['tab'];
	} else {
		$openTab = '0';
	}
	?>
	
	<input id="currentTab" type="hidden" name="currentTab" value="<?php echo $openTab; ?>" />
	<script>
	jQuery( document ).ready( function () {	
		ASadmin.openTab = <?php echo $openTab; ?>;
	});
	</script>	
	
	<?php
	/*
	echo 'DBUG: ';
	echo $PT->dbug;
	echo '<pre>';
	print_r( $PT->ops );
	echo '</pre>';
	//*/
}
	
?>