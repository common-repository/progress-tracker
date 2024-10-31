<?php
if ( class_exists("WP_Widget") ) 
{
	class ptracker_widget extends WP_Widget
	{
		
		
		//--- Setup
		function __construct () 
		{ 
			$widget_ops = array( 
				'classname' => 'ptracker_widget', 
				'description' => 'Adds a progress tracking widget into your sidebar.' 
			);
			$control_ops = array( 
				'id_base' => 'ptracker_widget'
			);
			parent::__construct( 'ptracker_widget', 'Progress Tracker', $widget_ops, $control_ops );
		}
		
		//--- Build frontend widget output
		function widget ( $args, $settings )
		{	
			//buld the widget
			$html = ''; 
			
			$showProgressDial=false; // By default don't show the progress dial
			
			
			global $P_TRACKER;
			$ops = $P_TRACKER->ops;
			$subPageNumberStyle = $ops['subPageNumberStyle'];
			
			$whatToShow = $settings['whatToShow']; // Get the page to show from widget settings
			$thisID = get_the_ID();
			
			
			switch ($whatToShow)
			{	
				case "current":
		
					// See if this page is a parent one
					$isThisPageTracked = progressTracker::parentTrackingEnabled($thisID);
					
					
					if($isThisPageTracked==true)
					{
						$trackedID = $thisID;
						$showProgressDial = true;
					}
					else
					{				
						$isParentTracked = progressTracker::checkIfParentIsTracked($thisID); // Returns false OR the parent ID
						if($isParentTracked<>false)
						{
							$trackedID = $isParentTracked;
							$showProgressDial = true;
							
						}				
					}

					
					if($showProgressDial==true)
					{
						$progressArgs = array
						(
							//"parent"=>get_the_ID(),			
							"parent"=>$trackedID,
							"type" => 'radial'
						);
						$progressDial = progressTracker::showUserProgress(  $progressArgs );
							
							
						$html.=$progressDial;
					}
				break;
				
				
				case "miniMenu":
					$showMenu = false;
					
					
					$isThisPageTracked = progressTracker::parentTrackingEnabled($thisID);
					
				
					if($isThisPageTracked==true)
					{
						$trackedID = $thisID;
						$showMenu = true;
						
					}
					else
					{				
						$isParentTracked = progressTracker::checkIfParentIsTracked($thisID); // Returns false OR the parent ID
						
						if($isParentTracked<>false)
						{
							$trackedID = $isParentTracked;
							$showMenu = true;
							
						}				
					}	
					
					if($showMenu==true)
					{
						global $P_TRACKER;
						

						$userTicks = array();
						$userID = get_current_user_id();
						if($userID)
						{
							$userTicks = $P_TRACKER->getRows( $userID );
						}
						
							
						$mySubPages = get_pages( array( 'child_of' =>$trackedID,'sort_column' => 'menu_order' ) );
					
						$subMenuStr= '<div id="learningObjectSubPageMenu">';
						
						$currentPage=1;
						
						$totalPages = count($mySubPages);
						$halfWay = round($totalPages/2);
					
						foreach ( $mySubPages as $page ) 
						{
							$pageID =  $page->ID;
							$link = get_page_link( $pageID );
							
							if($currentPage==1){$firstPageLink=$link;}
							
							$pageStatusIcon = 'red';
							$linkStyle='subMenuLinkUnread';
							if ( is_array( $userTicks ) ) {
								foreach ( $userTicks as $row ) {
									if ( $row->page_id == $pageID ) {
										$pageStatusIcon = 'green';
										$linkStyle = 'subMenuLinkRead';
										break;
									}
								}
							}
							
			
							$subMenuStr.='<span class="'.$linkStyle.'">';
							$subMenuStr.= '<a href="'.$link.'">';
							
							switch ($subPageNumberStyle)
							{
								case "numeric":
								case "":
									$subMenuStr.= $currentPage.'. ';					
								
								break;
								
								
								case "roman":				
									$subMenuStr.= ASPTutils::roman_numerals($currentPage).'. ';					
								break;
								
							}							
							
							
							$subMenuStr.= $page->post_title;
							$subMenuStr.= '</a></span>';				
							
							$subMenuStr.='<br/>';
	
							
							$currentPage++;
						}	
						
						$html = $subMenuStr;
					}


		
				
				
					
				break;
			}
		
		
			//output the widget along with WP standardised theme markup 
			if ( '' !== $html )
			{
				extract( $args ); // supplied WP theme vars 
				echo $before_widget;
				if ( $settings['title'] ) 
				{ 
					echo $before_title . $settings['title'] . $after_title; 
				}
				echo $html;
				echo $after_widget;
			}
			
			return;		
		
		}
		
		
		
		//--- Save / update widget settings
		function update ( $newSettings, $oldSettings )
		{
			$settings = $oldSettings;
			
			$settings['title'] = strip_tags( $newSettings['title'] );
			$settings['whatToShow'] = strip_tags( $newSettings['whatToShow'] );			
			
			return $settings;
		}
		
		
		
		//--- draw the admin-side widget form innards
		function form( $instance )
		{
			$defaults = array(
				'title' => '',
				'whatToShow' => 'current'
			);
			
			$settings = wp_parse_args( (array) $instance, $defaults );
			$whatToShow =  $settings['whatToShow'];
			$title =  $settings['title'];
			
			?>
		
			<h3>Widget Heading:</h3>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $settings['title']; ?>" />
			
			
			<?php
			
			echo '<h3>What do you want to show?</h3>';
			echo '<input type="radio" name="'.$this->get_field_name( 'whatToShow' ).'" id="'.$this->get_field_id( 'current' ).'" value="current" ';
			if($whatToShow=="current"){echo ' checked ';}
			
			echo '/>';
			echo '<label for="'.$this->get_field_id( 'current' ).'">The current page progress</label><hr/>';
			
			
			echo '<input type="radio" name="'.$this->get_field_name( 'whatToShow' ).'" id="'.$this->get_field_id( 'miniMenu' ).'" value="miniMenu" ';
			if($whatToShow=="miniMenu"){echo ' checked ';}
			echo '/>';
			echo '<label for="'.$this->get_field_id( 'miniMenu' ).'">The current page mini menu</label>';			
			
			
			/*
			// Get a list of all tracked pages and add to a drop down
			$trackedPages = progressTracker::getTrackedPagesArray();				
			$pageCount = count($trackedPages);
			
			if($pageCount==0)
			{
				echo 'You currently have no tracked pages';
			}
			else
			{
				echo '<select name="'.$this->get_field_name( 'pageToShow' ).'" id="'.$this->get_field_id( 'pageToShow' ).'">';
				echo '<option value="current">- The current tracked page / subpages -</option>';				
				foreach ($trackedPages as $pageID => $pageName)
				{
					echo '<option value="'.$pageID.'"';
					if ($pageToShow==$pageID){echo ' selected';}
					echo '>'.$pageName.'</option>';
				}
				echo '</select>';				
			}
			*/
				
			
			
			?>
			
			
			<br><br><hr>
		<?php
		}
	
	}
}
?>