<?php
include "bsc-membership-search-init.php";

class BSC_Membership_Search_Options {

	function __construct() {
		new BSC_Membership_Search_Init();

		add_action('wp_loaded', array($this, 'load_settings'));
		add_action('admin_menu', array($this, 'admin_menu'));
	}

	function admin_menu() {
		add_options_page(
			'Directory Search Settings',
			'Directory Search',
			'manage_options',
			'directory_search_settings',
			array(
				$this,
				'settings_page'
			)
		);
	}

	function load_settings() {
		$fields = apply_filters('bsc_membership_fields');
		$field_names = function(array $field) {
			return $field['name'];
		};

		BSC_Membership_Search::$settings['search'] = array(
			'url' => $this->get_option('directory_api_endpoint', 'http://localhost/api/profiles'),
			'api_enabled' => $this->get_option('directory_api_enabled', false),
			'fields' => $fields,
			'search_exports' => array_keys($this->get_option('search_exports', array_flip(array_map($field_names, $fields)))),
			'search_keywords' => array_keys($this->get_option('search_keywords', array()))
		);
	}

	function get_option($name, $default = null) {
		// get current value or set current to default value and save it
		$current = get_option($name) or ($default !== null and $current = $default and update_option($name, $current));
		// get new value and replace current value if changed
		if ($_POST['save_settings']) {
			$new = $_POST[$name] and !stristr($current, $new) and update_option($name, $new) and $current = $new;
			$new === null and delete_option($name) and $current = $new;
		}
		return $current;
	}

	function  settings_page() {
		extract(BSC_Membership_Search::$settings['search']);

		$checked = function($a, array $b) {
			if (in_array($a, $b)) return 'checked="checked"';
		}
		?>
		<div class="wrap">
			<h1>Directory Search Settings</h1>
			<div class="row">
				<div class="col-md-12">
					<form method="post">
						<div class="form-group">
							<input class="form-control" type="checkbox" name="directory_api_enabled" <?php echo $api_enabled ? 'checked=checked' : '' ?> />
							<label for="directory-api-endpoint">Directory Search API (url)</label>
							<input class="form-control" id="directory-api-endpoint" type="text" name="directory_api_endpoint" value="<?php echo $url ?>" />
						</div>
						<br />
						<?php // profile fields
						echo '<h2>Profile Fields</h2>
						<table class="profile-fields">
							<tr><th>Field ID</th><th>Name</th><th>Type</th><th>Export</th><th>Keyword</th></tr>
						';
						foreach ($fields as $id => $field) {
							echo '
						<tr>
							<td>' . $id . '</td>
							<td>' . $field['name'] . '</td>
							<td>' . $field['type'] . '</td>
							<td><input name="search_exports[' . $field['name'] . ']" type="checkbox" ' . $checked($field['name'], $search_exports) . ' /></td>
							<td><input name="search_keywords[' . $field['name'] . ']" type="checkbox" ' . $checked($field['name'], $search_keywords) . '/></td>
						</tr>';
						}
						echo '</table><br />';
						?>

						<input class="form-control btn btn-primary" type="submit" name="save_settings" value="Save Settings" />
					</form>
				</div>
			</div>
			<div class="row">
				<?php  do_action('display_search_init_form'); ?>
			</div>
		</div>
		<?php
	}
}