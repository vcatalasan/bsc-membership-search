<?php
/*
Plugin Name: BSC Membership Search
Plugin URI: http://www.bscmanage.com/my-plugin/
Description: A plugin that creates bsc_membership table view from BP Profile Fields for export and search
Version: 1.0.0
Requires at least: WordPress 2.9.1 / BuddyPress 1.2
Tested up to: WordPress 2.9.1 / BuddyPress 1.2
License: GNU/GPL 2
Author: Val Catalasan
Author URI: http://www.bscmanage.com/staff-profiles/
*/

/* release notes:
 * 1.0.0: (02/12/2016)
*/
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

$bsc_membership_search_plugin_file  = plugin_dir_path(__FILE__) . 'plugin.php';

require($bsc_membership_search_plugin_file);

add_action('bp_include', array('BSC_Membership_Search_Plugin', 'get_instance'));

function bsc_membership_search_plugin_activation()
{
    update_option('bsc_membership_search_plugin_activation', __FILE__);
}
register_activation_hook(__FILE__, 'bsc_membership_search_plugin_activation');


function bsc_membership_search_plugin_deactivation()
{
    delete_option('bsc_membership_search_plugin_activation');
}
register_deactivation_hook(__FILE__, 'bsc_membership_search_plugin_deactivation');
