<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

require(plugin_dir_path(__FILE__) . 'includes/bsc-membership-search.php');

class BSC_Membership_Search_Plugin extends BSC_Membership_Search
{

    // plugin general initialization

    private static $instance = null;

	// required plugins to used in this application
	var $required_plugins = array(
		'BuddyPress' => 'buddypress/bp-loader.php'
	);

	/**
     * Return an instance of this class.
     *
     * @since     1.0.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance()
    {
        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    function __construct()
    {
	    // program basename and dir
        self::$settings['program'] = array(
            'activation' => get_option( 'bsc_membership_search_plugin_activation' ),
            'ready' => $this->required_plugins_active(),
            'basename' => plugin_basename(__FILE__),
            'dir_path' => plugin_dir_path(__FILE__),
            'dir_url' => plugin_dir_url(__FILE__)
        );

        add_action('admin_init', array($this, 'activation'));

        parent::__construct();
    }

	function required_plugins_active()
	{
		$status = true;
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        foreach ($this->required_plugins as $name => $plugin) {
			if (is_plugin_active($plugin)) continue;

            if (is_admin()) { ?>
                <div class="error">
                    <p>BSC Membership Search plugin requires <strong><?php echo $name ?></strong> plugin to be installed and activated</p>
                </div>
			<?php }
			$status = false;
		}
		return $status;
	}

	function activation()
    {
        if (self::$settings['program']['activation']) {
            self::$settings['program']['ready'] and $this->export_membership_table();
            delete_option( 'bsc_membership_search_plugin_activation' );
        }
    }
}

?>