<?php
define('PROFILES_PER_PAGE', 10);

class BSC_Membership_Search_Init {

	function __construct() {
		add_action('wp_ajax_update_directory_search', array($this, 'update_directory_search'));
		add_action('display_search_init_form', array($this, 'display_form'));
	}

	function display_form() {
		global $current_user;
		$wp_nonce = wp_create_nonce( $current_user->user_email );
		?>
		<div class="container">
			<hr />
			<p><strong>Export Membership Profiles to Directory Search Service</strong></p>
			<form id="sync-directory-search" method="post">
				<div class="form-group">
					<input type="checkbox" class="form-control" id="replace" name="replace" checked="checked" /><label for="replace">Replace existing record (defaults to update)</label>
				</div>
				<br />
				<input type="hidden" name="wp_nonce" value="<?php echo $wp_nonce ?>" />
				<input type="hidden" name="p" value="1" />
				<input id="start-directory-sync" type="submit" value="Start Export" />
			</form>
			<div id="profiles-list"></div>
		</div>
		<script src="http://malsup.github.com/jquery.form.js"></script>
		<script type="text/javascript">
			jQuery(document).ready( function($) {
				var page = $("#sync-directory-search input[name=p]");
				var form = $("#sync-directory-search");
				var profilesList = $("#profiles-list");
				var total_count = 0;
				var options = {
					data: { action: 'update_directory_search' },
					url: "<?php echo admin_url( 'admin-ajax.php'); ?>?XDEBUG_SESSION_START=PHPSTORM",
					success: function( responseText, statusText, xhr, $form ) {
						if ( responseText.length > 0 ) {
							var response = jQuery.parseJSON( responseText );
							var list = "<ol start=" + (total_count + 1) + " >";
							for (var i in response.profiles) {
								list += "<li>" + JSON.stringify(response.profiles[i]) + "</li>";
								total_count++;
							}
							list += "</ol>";
							response.eof && (list += "<p>All done!</p>");
							profilesList.html(list);
							page.attr('value', 1 + parseInt(page.attr('value')));
							!response.eof && form.ajaxSubmit( options );
						}
					}
				};

				$('#start-directory-sync').click( function(e) {
					e.preventDefault();
					form.ajaxSubmit( options );
					$(this).hide();
				});

			})
		</script>
		<?php
	}

	function get_records($table, $page = null, $items_per_page = null) {
		global $wpdb;
		static $current_page = 1, $current_items_per_page = PROFILES_PER_PAGE;  // default values

		$page and $current_page = $page;
		$items_per_page and $current_items_per_page;

		// build query
		$offset = ($current_page - 1) * $current_items_per_page;
		$sql = "SELECT * FROM ${table} LIMIT %d , %d";
		return $wpdb->get_results($wpdb->prepare($sql, $offset, $current_items_per_page));
	}

	function update_directory_search() {
		global $current_user;

		if ( ! wp_verify_nonce( $_POST['wp_nonce'], $current_user->user_email )) exit;

		$page = 1;
		if(!empty($_POST['p'])) {
			$page = filter_input(INPUT_POST, 'p', FILTER_VALIDATE_INT);
			if(false === $page) {
				$page = 1;
			}
		}
		$next = $page + 1;
		$rs = $this->get_records( 'bsc_membership', $page );
		$result = array(
			'eof' => true,
			'count' => 0,
			'profiles' => array()
		);
		foreach ($rs as $r) {
			$profile = apply_filters('clean_up_data', $r);
			$result['profiles'][] = $profile;

			do_action('update_directory_search', $profile);
		}
		$result['count'] = count($result['profiles']);
		$result['eof'] = $result['count'] < PROFILES_PER_PAGE;  // items per page
		echo json_encode($result);
		exit;
	}
}
