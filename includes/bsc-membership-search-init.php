<?php
class BSC_Membership_Search_Init {

	function __construct() {
		$this->display_page();
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

	function display_page() {
		$page = 1;
		if(!empty($_GET['page'])) {
			$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
			if(false === $page) {
				$page = 1;
			}
		}
		echo '<ol class="container">';
		//do {
			$rs = $this->get_records( 'bsc_membership' );
			foreach ($rs as $r) {
				echo "<li>" . print_r($r,true) . "</li>";
			}
		//} while (count($rs));
		echo '</ol>';
	}
}
