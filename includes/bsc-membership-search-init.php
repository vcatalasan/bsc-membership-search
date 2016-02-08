<?php
class BSC_Membership_Search_Init {

	function __construct() {
		add_action( 'wp_ajax_update_directory_search', array($this, 'update_directory_search' ) );
	}

	function display_form() {
		global $current_user;
		$wp_nonce = wp_create_nonce( $current_user->user_email );
		?>
		<div class="container">
			<form id="sync-directory-search" method="post">
				<input type="hidden" name="wp_nonce" value="<?php echo $wp_nonce ?>" />
				<input type="hidden" name="p" value="1" />
				<input id="start-directory-sync" type="submit" value="Start Directory Sync" />
			</form>
			<div id="display-result"></div>
		</div>
		<script src="http://malsup.github.com/jquery.form.js"></script>
		<script type="text/javascript">
			jQuery(document).ready( function($) {

				var options = {
					data: { action: 'update_directory_search' },
					url: "<?php echo admin_url( 'admin-ajax.php'); ?>?XDEBUG_SESSION_START=PHPSTORM",
					success: function( responseText, statusText, xhr, $form ) {
						if ( responseText ) {
							var response = jQuery.parseJSON( responseText );
							$("#display-result").html( responseText );
						}
					}
				};


				$('#start-directory-sync').click( function(e) {
					e.preventDefault();
					$('#sync-directory-search').ajaxSubmit( options );
				});

			})
		</script>
		<?php
	}

	function get_records($table, $page = null, $items_per_page = null) {
		global $wpdb;
		static $current_page = 1, $current_items_per_page = 10;  // default values

		$page and $current_page = $page;
		$items_per_page and $current_items_per_page;

		// build query
		$offset = ($current_page - 1) * $current_items_per_page;
		$sql = "SELECT * FROM ${table} LIMIT %d , %d";
		return $wpdb->get_results($wpdb->prepare($sql, $offset, $current_items_per_page));
	}

	function update_directory_search() {
		global $current_user;

		if ( ! wp_verify_nonce( $_GET['wp_nonce'], $current_user->user_email )) exit;

		$page = 1;
		if(!empty($_GET['p'])) {
			$page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT);
			if(false === $page) {
				$page = 1;
			}
		}
		$next = $page + 1;
		echo '<ol class="container">';
		//do {
			$rs = $this->get_records( 'bsc_membership', $page );
			foreach ($rs as $r) {
				$profile = $this->cleanupData($r);
				$profile->user_id = intval($r->user_id);
				$profile->user_status = intval($r->user_status);
				echo "<li>" . print_r(json_encode($profile),true) . "</li>";

				//do_action('update_directory_search', $profile);
			}
		//} while (count($rs));
		echo '</ol>';
		echo '<a href="' . strtok("${_SERVER['REQUEST_URI']}",'?') . '?page=directory_search_settings&p=' . $next . '">Next Page</a>';
		exit;
	}

	function cleanupData($obj) {
		$objVars = get_object_vars($obj);

		if(count($objVars) > 0) {
			foreach($objVars as $propName => $propVal) {
				if(gettype($propVal) === "object") {
					$cObj = $this->cleanupData($propVal);
					if($cObj === null) {
						// stripped null value
						unset($obj->$propName);
					} else {
						$obj->$propName = $cObj;
					}
				} else {
					if(is_null($propVal)) {
						// stripped null value
						unset($obj->$propName);
					} elseif (is_serialized($propVal)) {
						// unserialize data
						$obj->$propName = unserialize($propVal);
					}
				}
			}
		} else {
			return null;
		}
		return $obj;
	}
}
