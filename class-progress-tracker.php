<?php
class progressTracker
{
	var $version 		= '0.7';
	var $pluginFolder 	= '';
	var $opName 		= 'as_progress_tracker_ops';
	var $ops 			= false;
	var $dbTable_users 	= 'as_progress_tracker_users';
	var $dbug 			= '';
	
	
	//~~~~~
	function __construct ()
	{
		$this->pluginFolder = plugins_url('', __FILE__);
		$this->ops = $this->checkCompat();
		$this->addWPActions();
	}
	
/*	---------------------------
	PRIMARY HOOKS INTO WP 
	--------------------------- */	
	function addWPActions ()
	{
		//Admin Menu
		add_action( 'admin_menu', array( $this, 'createAdminMenu' ) );
		
		
		//Post edit / new screens
		add_action( 'admin_head-post.php', array( $this, 'adminPostEnqueues' ) );
		add_action( 'admin_footer-post.php', array( $this, 'adminPostInline' ), 100 );
		
		add_action( 'admin_head-post-new.php', array( $this, 'adminPostEnqueues' ) );
		add_action( 'admin_footer-post-new.php', array( $this, 'adminPostInline' ), 100 );
		
		//add_action( 'post_submitbox_misc_actions', array( $this, 'drawPageCustomBox' ) );
		add_action( 'save_post', array( $this, 'savePostData' ) );
		
		add_action( 'wp_ajax_ASPT_parentHasTracking', 'ASPT_parentHasTracking' );
		
		
		
		// Edit screen metaboxes needed for GB :(
		// Add the Metaboxes
		add_action( 'add_meta_boxes', array( $this, 'drawGBmetabox' ));		
		
		
		
		
		//Frontend
		add_action( 'the_content', array( $this, 'specialThingy' ), 100 );
		add_action( 'wp_head', array( $this, 'frontendEnqueues' ) );
		add_action( 'wp_footer', array( $this, 'frontendInlineScript' ), 100 ); //later than enqueues
		add_action( 'wp_ajax_ASPT_updateUserPage', 'ASPT_updateUserPage' );
		
		// ADd some shortcodes
		add_shortcode('ptracker', array( $this, 'showUserProgress'));
		add_shortcode('ptracker-toggle', array( $this, 'showCustomToggler'));		
		
		//widget
		add_action( 'widgets_init',  array( $this, 'registerWidgets' ) );
		
	}
	
	
	
	
	static function registerWidgets () {
		register_widget( 'ptracker_widget' );
	}
	
	
	
	
	function showCustomToggler()
	{
		$str = ASPTdraw::drawToggle();
		return $str;
	}
	
	
	// Shortcode
	static function showUserProgress($atts)
	{
		global $P_TRACKER;
	
	
		$atts = shortcode_atts( 
			array(
				'parent'   => '#',
				'type'   => 'radial'				
			), 
			$atts
		);
		
		$userID = get_current_user_id();
		if($userID<>0)
		{
			$parentID = (int) $atts['parent'];
			$type= $atts['type'];			
			
			$parentPageLink = get_page_link($parentID);
			$progressStr='';
			
			$topicTitle =  get_the_title($parentID);  // Get the name of the parent ID	
			
			// Generate an array of the subpages of the parent. i.e. all the pages in the learning object
			$mySubPages = get_pages( array( 'child_of' =>$parentID,'sort_column' => 'menu_order' ) );
			
			$subPageCount = count($mySubPages);
			
			
			$subPagesIDs = array();
			foreach ($mySubPages as $page)
			{
				$subPagesIDs[] += $page->ID;
			}		
			
			$myProgress=0;
			
			
			// Get the data fort his logged in user
			$userTicks = $P_TRACKER->getRows( $userID );
			
			if ( is_array( $userTicks ) ) {
				foreach ( $userTicks as $row )
				{
					$childID = $row->page_id;
					if(in_array($childID, $subPagesIDs))
					{
						$myProgress++;
					}
				}
			}	
			
			if($myProgress>=1 && $subPageCount>=1)
			{
				$myProgress = round($myProgress/$subPageCount*100);
			}
			else
			{
				$myProgress = 0;
			}
			
			$progressBarColour = '#e63c3c';
			if($myProgress>31){$progressBarColour = 'orange';}
			if($myProgress>74){$progressBarColour = '#62c808';}						
			
		}	
		
		
		if($type=="bar")
		{
			$progressBarColour = 'red';
			if($myProgress>31){$progressBarColour = 'orange';}
			if($myProgress>74){$progressBarColour = 'green';}						
	
			$progressStr.= '<div class="progress" style="width:600px">';
			$progressStr.= '<span class="'.$progressBarColour.'" style="width: '.$myProgress.'%;"><span>'.$myProgress.'%</span></span>';
			$progressStr.= '</div>';				
			
			
			//$progressStr .= '<div class="progress"><span class="red" style="width: '.$myProgress.'%;"><span>'.$myProgress.'%</span></span></div>';
		}
		else
		{
			$progressStr .= $P_TRACKER->makeProgressDial( $myProgress, $atts['parent'] );
			$progressStr .= '<style> .radial-progress#radialProgress_' .$atts['parent']. ' .circle .mask .fill { background-color:' .$progressBarColour. '; } </style>';
		}
		
		return $progressStr;
	
	}
	
	
	//~~~~~
	function frontendEnqueues ()
	{
		//Scripts
		wp_enqueue_script('jquery');
		wp_enqueue_script( 'progress-tracker', $this->pluginFolder . '/scripts/frontend.js' );
		
		global $post;
		$post_id = ( ! empty( $post->ID ) ) ? $post->ID : '';
		$user_id = get_current_user_id();
		
		wp_localize_script( 
			'progress-tracker',
			'ASPTajax',
			array(
				'WPajaxurl' => admin_url('admin-ajax.php'),
				'post_id'	=> $post_id,
				'user_id'	=> $user_id,
			)
		);
		
		//Styles
		wp_enqueue_style( 'progress-tracker', $this->pluginFolder . '/css/frontend.css' );
		wp_enqueue_style( 'page_tracker_progress', $this->pluginFolder . '/css/progress-bar.css' );
		
		
		wp_enqueue_style('pure-style','https://cdnjs.cloudflare.com/ajax/libs/pure/0.6.0/buttons.css');		
		
		
	}
	
	
	//~~~~~
	function frontendInlineScript ()
	{		
	
	
		//add_thickbox();
	?>
		<script type="text/javascript">
		jQuery( document ).ready( function () {
			AS_ptracker.unMarkedText = '<?php echo $this->ops['unMarkedText']; ?>';
			AS_ptracker.markedText = '<?php echo $this->ops['markedText']; ?>';
			AS_ptracker.pluginURL = '<?php echo $this->pluginFolder; ?>';
			AS_ptracker.init();
		});
		</script>
	
	<?php
	}
	
	
/*	---------------------------
	ADMIN-SIDE MENU / SCRIPTS 
	--------------------------- */
	function createAdminMenu ()
	{
		$parentSlug 	= "tools.php";
		$page_title 	= "Progress Tracker";
		$menu_title		= "Progress Tracker";
		$menu_slug		= "ptracker-settings";
		$drawFunction	= array( $this, 'drawSettingsPage' );
		$handle = add_management_page( 
			$page_title, 
			$menu_title, 
			'manage_options', 
			$menu_slug, 
			$drawFunction 
		);
		
		add_action( 'admin_head-'. $handle, array( $this, 'adminSettingsEnqueues' ) );
		add_action( 'admin_footer-'. $handle, array( $this, 'adminSettingsInline' ), 200 );
	}
	
	
	
	//~~~~~
	function adminSettingsInline () 
	{
	?>		
		<script>
			jQuery(document).ready(function(){	
				if (jQuery('#userTable').length>0)
				{
					jQuery('#userTable').dataTable({
						"bAutoWidth": true,
						"bJQueryUI": true,
						"sPaginationType": "full_numbers",
						"iDisplayLength": 50, // How many numbers by default per page
					});
				}
				
			});
		</script>	
	<?php
	}
	
	
	
	//~~~~~
	function adminSettingsEnqueues ()
	{
		//WP includes
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-widget');
		wp_enqueue_script('jquery-ui-mouse');
		wp_enqueue_script('jquery-ui-sortable');
		//wp_enqueue_script('jquery-ui-tabs'); 
		wp_enqueue_script('jquery-touch-punch');	
		
		//Plugin folder js
		wp_enqueue_script( 'page_tracker_settings', $this->pluginFolder . '/scripts/settings.js' );
		
		//Plugin folder css
		wp_enqueue_style( 'page_tracker_settings', $this->pluginFolder . '/css/settings.css' );
		wp_enqueue_style( 'page_tracker_progressBars', $this->pluginFolder . '/css/progress-bar.css' );		
		
		
		//DataTables js
		wp_register_script( 'datatables', ( '//cdn.datatables.net/1.10.7/js/jquery.dataTables.min.js' ), false, null, true );
		wp_enqueue_script( 'datatables' );
		
		//DataTables css
		wp_enqueue_style('datatables-style','//cdn.datatables.net/1.10.7/css/jquery.dataTables.min.css');
		
		
		
		
		
		
		//Load the jquery ui theme
		global $wp_scripts;	
		$queryui = $wp_scripts->query('jquery-ui-core');
		$url = "https://ajax.googleapis.com/ajax/libs/jqueryui/".$queryui->ver."/themes/smoothness/jquery-ui.css";	
		wp_enqueue_style('jquery-ui-smoothness', $url, false, null);	
	}
	
	
	//~~~~~
	function adminPostEnqueues ()
	{
		wp_enqueue_script( 'page_tracker', $this->pluginFolder . '/scripts/post.js' );
		wp_localize_script( 
			'page_tracker',
			'ASPTajax',
			array(
				'WPajaxurl' => admin_url('admin-ajax.php')
			)
		);
		
		wp_enqueue_style( 'page_tracker_post', $this->pluginFolder . '/css/post.css' );
	}
	
	
	//~~~~~
	function adminPostInline ()
	{
		global $current_screen;
		if( 'page' != $current_screen->id ) {
			return;
		}
		?>
		
		<script type="text/javascript">
		jQuery( document ).ready( function() {  
			ASPTadmin.init();
		 });
		</script>
	<?php
	}
	
	
	//~~~~~
	function drawSettingsPage ()
	{
		if ( isset( $_GET['view_user'] ) ) { 
			
			// Display single user data
			// ---
			?>
			<div class="wrap">
				<h1>Progress Tracker</h1>
				<?php
				$userID = $_GET['view_user'];
				ASPT_drawUserPage( $userID );
				?>
			</div>
		
		<?php
		} else { 
			
			// Display overview and settings tabs
			// ---
			if ( isset( $_POST['progress_tracker_submit'] ) ) //update options
			{
				
				
				global $P_TRACKER;
				
				// Now get the variables for each settings
				$defaults = $P_TRACKER->defaults();
				foreach ($defaults as $key => $value)
				{
					if(isset($_POST[$key]))
					{
						$postedValue = 	$_POST[$key];
						$newOps[$key] = $postedValue;
					}
					else // Mark it as blank
					{
						$newOps[$key] = "";
					}
				}				
				
				
				/*
				$newOps['navButtonLocation']	= $this->stripScripts( $_POST['navButtonLocation'] );
				$newOps['startLinkText'] 		= $this->prepValue( $this->stripScripts( $_POST['startLinkText'] ) );
				$newOps['nextLinkText'] 		= $this->prepValue( $this->stripScripts( $_POST['nextLinkText'] ) );
				$newOps['backLinkText'] 		= $this->prepValue( $this->stripScripts( $_POST['backLinkText'] ) );
				$newOps['showQuickJumpList'] 	= $this->stripScripts( $_POST['showQuickJumpList'] );
				$newOps['showStudentProgress']	= $this->stripScripts( $_POST['showStudentProgress'] );				
				$newOps['buttonIconID']			= $this->stripScripts( $_POST['buttonIconID'] );
				$newOps['unMarkedText'] 		= $this->prepValue( $this->stripScripts( $_POST['unMarkedText'] ) );
				$newOps['markedText'] 			= $this->prepValue( $this->stripScripts( $_POST['markedText'] ) );
				$newOps['readButtonLocation'] 	= $this->prepValue( $this->stripScripts( $_POST['readButtonLocation'] ) );
				$newOps['version']				= $this->stripScripts( $_POST['version'] );
				$newOps['subpageListStyle']		= $this->stripScripts( $_POST['subpageListStyle'] );
				$newOps['subPageNumberStyle']	= $this->stripScripts( $_POST['subPageNumberStyle'] );
				*/	
				
							
				update_option( $this->opName, $newOps );
				$this->ops = $newOps;
				?>
				<div class="updated"><p><strong><?php echo "Settings saved."; ?></strong></p></div>
			<?php
			}
			?>
				
			<div class="wrap">
				<h1>Progress Tracker</h1>
				<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	
				<?php 
				ASPT_drawSettingsPage( $this );				
				?>
		
				</form>
			</div>
		
		<?php
		}
	}
	
		
	
/*	------------------------------
	ADMIN-SIDE PAGE EDIT SCREENS 
	------------------------------ */
	//~~~~~
	function drawPageCustomBox()
	{
		global $post;
		if ( 'page' != get_post_type( $post ) ) {
			return;
		}
		
		
		
		
		//$checked = get_post_meta( $post->ID, 'enableProgressTracking', true );
		
		ASPTdraw::pageCustomBox( $post->ID );
	}
	
	
	function drawGBmetabox()
	{

		$id 			= 'progress_tracking_metabox';
		$title 			= 'Enable Tracking?';
		$drawCallback 	= array( $this, 'drawPageCustomBox' );
		$screen 		= 'page';
		$context 		= 'side';
		$priority 		= 'default';
		$callbackArgs 	= array();
		
		add_meta_box( 
			$id, 
			$title, 
			$drawCallback, 
			$screen, 
			$context,
			$priority, 
			$callbackArgs 
		);

	}
	

	
	
	//~~~~~
	function savePostData( $post_id )
	{	
		if ( empty( $_POST['post_type'] ) || 'page' != $_POST['post_type'] ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['ASPT_tickBox'] ) || ! wp_verify_nonce( $_POST['ASPT_tickBox'], 'ASPT_enableProgressTracking' ) ) {
			return;
		}
		if( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		
		$state = ( isset( $_POST['enableProgressTracking'] ) ) ? 'true' : 'false';
		update_post_meta( 
			$post_id, 
			'enableProgressTracking', 
			$state
		);
	}
	
	
/*	--------------------------------------------
	PLUGIN COMPATIBILITY AND UPDATE FUNCTIONS 
	-------------------------------------------- */	
	//~~~~~
	function getCharsetCollate () 
	{
		global $wpdb;
		$charset_collate = '';
		if ( ! empty( $wpdb->charset ) )
		{
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) 
		{
			$charset_collate .= " COLLATE $wpdb->collate";
		}
		return $charset_collate;
	}
	
	//~~~~~
	function checkCompat ()
	{
	
		$this->dbug .= '#checkCompat.';
		$ops = get_option( $this->opName );
		
		if ( empty( $ops ) ) {
			$ops = $this->defaults();
			update_option( $this->opName, $ops );
			$this->deltaWPtables();
		}
		else {
			

			if ( $ops['version'] < $this->version ) { //Never downgrade!
				$ops = $this->update( $ops );
			}
			else // REMOVE THIS FROM LIVE
			{
				$ops = $this->update( $ops );
			}
		}
		return $ops;
	}
	
	
	//~~~~~
	function update ( $old )
	{
		$this->dbug .= '#update.';
		$ops = $this->defaults();
		
		foreach ( $ops as $k => $op ) {
			
			
		//	echo 'update '.$op.'<br/>';
			if ( array_key_exists( $k, $old ) ) {
				$ops[$k] = $old[$k];
			}
		}
		
		
		$ops['version'] = $this->version; //set last!
		update_option( $this->opName, $ops );
		$this->deltaWPtables();
		return $ops;
	}
	
	
	//~~~~~
	function defaults ()
	{
		$defaults = array(
			'version' 				=> $this->version,
			'navButtonLocation'		=> 'both',
			'nextLinkText'			=> 'Next',
			'backLinkText'			=> 'Previous',
			'buttonIconID'			=> '1',
			'showQuickJumpList'		=> 'true',
			'unMarkedText'			=> 'Mark as read',
			'markedText'			=> 'Completed',
			'showStudentProgress'	 => 'bar',
			'readButtonLocation'	 => 'top',
			'startLinkText'			=> 'Click here to start',
			'subpageListStyle'		=> 'twoCol',
			'subPageNumberStyle' 	=> 'numeric',
			'showStudentProgress'	=> 'bar',
			'allowUserReset'		=> '',
			'autoMarkProgress'		=> ''
		);
		return $defaults;
	}
	
	//~~~~~
	function deltaWPtables ()
	{
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		$WPversion = substr( get_bloginfo('version'), 0, 3);
		$charset_collate = ( $WPversion >= 3.5 ) ? $wpdb->get_charset_collate() : $this->getCharsetCollate();
		
		$table = $wpdb->prefix . $this->dbTable_users;
		//users table
		$sql = "CREATE TABLE $table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_id mediumint(9),
			page_id mediumint(9),
			read_status mediumint(9),
			read_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			UNIQUE KEY id (id),
			KEY user_id (user_id),
			KEY read_date (read_date)
			) $charset_collate;";
		dbDelta( $sql );
		$this->dbug .= '#delta tables.';
	}
	
/*	--------------------------------------------
	SPECIAL THINGY
	-------------------------------------------- */		
	//~~~~~
	function specialThingy( $theContent )
	{
		//return $theContent;
		
		
		
		if(is_admin())
		{
			return $theContent;
			
		}
		else
		{
			return ASPTdraw::specialThingy( $theContent );
			
		}
	}
	
/*	--------------------------------------------
	TABLE MANAGEMENT
	-------------------------------------------- */	
	//~~~~~
	function updateUserInfo ( $INFO )
	{
		$success = false;
		
		$user_id 		= ( ! empty( $_POST['user_id'] ) ) ? $this->stripScripts( $_POST['user_id'] ) : '';
		$post_id 		= ( ! empty( $_POST['post_id'] ) ) ? $this->stripScripts( $_POST['post_id'] ) : '';
		$read_status  	= ( ! empty( $INFO['read_status'] ) ) ? $INFO['read_status'] : '';		
		$date 			= current_time( 'mysql' );
		
		$ROWS = $this->getRows( $user_id, $post_id );		
		
		global $wpdb;
		
		if ( 'green' === $read_status )
		{
			if ( ! empty( $ROWS[0] ) ) {
				return true;
			}
			$success = $this->addRow( $user_id, $post_id );
		}
		elseif ( 'red' === $read_status )
		{
			if ( empty( $ROWS[0] ) ) {
				return true;
			}
			$success = $this->deleteRow( $user_id, $post_id );
		}
		
		return $success;
	}	
	
	
	//~~~~~
	function getRows ( $userID = '', $pageID = '' ) 
	{
		global $wpdb;
		
		$sql = "SELECT * FROM " . $wpdb->prefix . $this->dbTable_users;
		if ( $userID !== '' ) 
		{ 
			$sql .= " WHERE user_id=" . $userID;
		}
		if ( $pageID !== '' ) 
		{
			$sql .= " AND page_id=" . $pageID;
		}
		
		return $wpdb->get_results( $sql );
	}
	
	
	//~~~~~
	function addRow ( $userID, $pageID )
	{
		global $wpdb;
		global $P_TRACKER;		
		
		// Check if this entry already exists
		// if it does don't re-add it
		$userTicks = $P_TRACKER->getRows( $userID );
		if ( is_array( $userTicks ) ) {
			foreach ( $userTicks as $row ) {
				if ( $row->page_id == $pageID ){
					return;
				}
			}
		}		
		
		
		return $wpdb->insert( 
			$wpdb->prefix . $this->dbTable_users, 
			array( 
				'user_id' 		=> $userID,
				'page_id' 		=> $pageID,
				'read_status' 	=> 1,
				'read_date' 	=> current_time( 'mysql' ) 
			),
			array( '%d', '%d', '%d', '%s' )
		);
	}
	
	
	//~~~~~
	function deleteRow ( $userID = '', $pageID = '' )
	{
		global $wpdb;
		$result = $wpdb->query( 
			$wpdb->prepare( 
				"DELETE FROM " . $wpdb->prefix . $this->dbTable_users . " WHERE user_id = %d AND page_id = %d",
					$userID, 
					$pageID 
				)
		);
		
		return ( $result === false ) ? false : true;
	}
	
	
/*	--------------------------------------------
	HELPERS
	-------------------------------------------- */		
	//~~~~~
	
	// Check to see if a parent ID has the post meta enabled
	static function parentTrackingEnabled ( $parentID )
	{	
		$enabled = 'false';
		if ( ! empty( $parentID ) ) {
			$enabled = get_post_meta( $parentID, 'enableProgressTracking', true );
		}
		return $enabled === 'true' ? true : false;
	}
	
	static function checkIfParentIsTracked ($ID)
	{
	
		$isParentTracked = false;
		$parentID = wp_get_post_parent_id($ID);
		
		if($parentID)
		{
			$isParentTracked = progressTracker::parentTrackingEnabled( $parentID );		
		}
		
		if($isParentTracked==true)
		{
			return $parentID;
		}
		else
		{
			return false;
		}
	
	}
	
	
	//~~~~~
	function getPostInfo ()
	{
		$cleaned = array();
		if ( isset( $_POST['info'] ) && is_array( $_POST['info'] ) ) {
			foreach ( $_POST['info'] as $k => $val ) {
				$cleaned[ $k ] = $this->stripScripts( $val ); 
			}
		}
		return $cleaned;
	}
	
	
	//~~~~~
	function prepValue ( $field )
	{	
		$search = array( "'", '"', '\\' );
		$option = str_replace( $search, "", $field );
		$option = strip_tags( $option );
		return $option;
	}
	
	//~~~~~
	function stripScripts ( $field )
	{ 
		$search = array(
			'@<script[^>]*?>.*?</script>@si',  // Strip out javascript 
			'@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly 
			'@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA 
		);
		$text = preg_replace( $search, '', $field ); 
		return $text; 
	}
	
	function drawProgressDial ( $percentComplete ) 
	{
		?>
		<div class="radial-progress" data-progress="<?php echo $percentComplete; ?>">
			<div class="circle">
				<div class="mask full">
					<div class="fill"></div>
				</div>
				<div class="mask half">
					<div class="fill"></div>
					<div class="fill fix"></div>
				</div>
				<div class="shadow"></div>
			</div>
			<div class="inset">
				<div class="percentage">
					<div class="numbers"><span>-</span><span>0%</span><span>1%</span><span>2%</span><span>3%</span><span>4%</span><span>5%</span><span>6%</span><span>7%</span><span>8%</span><span>9%</span><span>10%</span><span>11%</span><span>12%</span><span>13%</span><span>14%</span><span>15%</span><span>16%</span><span>17%</span><span>18%</span><span>19%</span><span>20%</span><span>21%</span><span>22%</span><span>23%</span><span>24%</span><span>25%</span><span>26%</span><span>27%</span><span>28%</span><span>29%</span><span>30%</span><span>31%</span><span>32%</span><span>33%</span><span>34%</span><span>35%</span><span>36%</span><span>37%</span><span>38%</span><span>39%</span><span>40%</span><span>41%</span><span>42%</span><span>43%</span><span>44%</span><span>45%</span><span>46%</span><span>47%</span><span>48%</span><span>49%</span><span>50%</span><span>51%</span><span>52%</span><span>53%</span><span>54%</span><span>55%</span><span>56%</span><span>57%</span><span>58%</span><span>59%</span><span>60%</span><span>61%</span><span>62%</span><span>63%</span><span>64%</span><span>65%</span><span>66%</span><span>67%</span><span>68%</span><span>69%</span><span>70%</span><span>71%</span><span>72%</span><span>73%</span><span>74%</span><span>75%</span><span>76%</span><span>77%</span><span>78%</span><span>79%</span><span>80%</span><span>81%</span><span>82%</span><span>83%</span><span>84%</span><span>85%</span><span>86%</span><span>87%</span><span>88%</span><span>89%</span><span>90%</span><span>91%</span><span>92%</span><span>93%</span><span>94%</span><span>95%</span><span>96%</span><span>97%</span><span>98%</span><span>99%</span><span>100%</span></div>
				</div>
			</div>
		</div>
		
		<?php
	}
	
	function makeProgressDial ( $percentComplete, $parentID ) 
	{
		$html = '<div class="radial-progress" data-progress="' . $percentComplete . '" id="radialProgress_' .$parentID. '">';
		$html .= 	'<div class="circle">';
		$html .= 		'<div class="mask full">';
		$html .= 			'<div class="fill"></div>';
		$html .= 		'</div>';
		$html .= 		'<div class="mask half">';
		$html .= 			'<div class="fill"></div>';
		$html .= 			'<div class="fill fix"></div>';
		$html .= 		'</div>';
		$html .= 		'<div class="shadow"></div>';
		$html .= 	'</div>';
		$html .= 	'<div class="inset">';
		$html .= 		'<div class="percentage">';
		$html .= 			'<div class="numbers"><span>-</span><span>0%</span><span>1%</span><span>2%</span><span>3%</span><span>4%</span><span>5%</span><span>6%</span><span>7%</span><span>8%</span><span>9%</span><span>10%</span><span>11%</span><span>12%</span><span>13%</span><span>14%</span><span>15%</span><span>16%</span><span>17%</span><span>18%</span><span>19%</span><span>20%</span><span>21%</span><span>22%</span><span>23%</span><span>24%</span><span>25%</span><span>26%</span><span>27%</span><span>28%</span><span>29%</span><span>30%</span><span>31%</span><span>32%</span><span>33%</span><span>34%</span><span>35%</span><span>36%</span><span>37%</span><span>38%</span><span>39%</span><span>40%</span><span>41%</span><span>42%</span><span>43%</span><span>44%</span><span>45%</span><span>46%</span><span>47%</span><span>48%</span><span>49%</span><span>50%</span><span>51%</span><span>52%</span><span>53%</span><span>54%</span><span>55%</span><span>56%</span><span>57%</span><span>58%</span><span>59%</span><span>60%</span><span>61%</span><span>62%</span><span>63%</span><span>64%</span><span>65%</span><span>66%</span><span>67%</span><span>68%</span><span>69%</span><span>70%</span><span>71%</span><span>72%</span><span>73%</span><span>74%</span><span>75%</span><span>76%</span><span>77%</span><span>78%</span><span>79%</span><span>80%</span><span>81%</span><span>82%</span><span>83%</span><span>84%</span><span>85%</span><span>86%</span><span>87%</span><span>88%</span><span>89%</span><span>90%</span><span>91%</span><span>92%</span><span>93%</span><span>94%</span><span>95%</span><span>96%</span><span>97%</span><span>98%</span><span>99%</span><span>100%</span></div>';
		$html .= 		'</div>';
		$html .= 	'</div>';
		$html .= '</div>';
				
	
	return $html;
	}
	
	
	// The query for getting list of traacked pages
	static function getTrackedPages()
	{
		$myPages = get_pages( array(
		'hierarchical' => 0,
		'sort_column' => 'menu_order',
		'meta_key' => 'enableProgressTracking',
		'meta_value' => 'true',
		'posts_per_page' => -1)
		);	
		
		return $myPages;
	}
	
	//Returns an array of page titles and IDs of tracked pages
	static function getTrackedPagesArray()
	{
		$pagesArray= array(); // Create an array to return
		$mypages = progressTracker::getTrackedPages();				
		$trackedParentCount = count($mypages);
		$subPageCount = 0;
		if($trackedParentCount)
		{	
			foreach( $mypages as $page )
			{
				if ( ! empty( $page->ID ) ) {
				
					$pageTitle = $page->post_title;
					$pageID = $page->ID;					
					$pagesArray[$pageID] = $pageTitle;					
				}						
			}
		}
		
		return $pagesArray;
	
	}
	
	// Gets the exceprt outside the loop
	static function get_excerpt_by_id($post_id){
		$the_post = get_post($post_id); //Gets post ID
		$the_excerpt = $the_post->post_content; //Gets post_content to be used as a basis for the excerpt
		$excerpt_length = 35; //Sets excerpt length by word count
		$the_excerpt = strip_tags(strip_shortcodes($the_excerpt)); //Strips tags and images
		$words = explode(' ', $the_excerpt, $excerpt_length + 1);
		if(count($words) > $excerpt_length) :
			array_pop($words);
			array_push($words, '...');
			$the_excerpt = implode(' ', $words);
		endif;
		$the_excerpt = '<p>' . $the_excerpt . '...</p>';
		return $the_excerpt;
	}	
				
	
	
} //Close class
?>