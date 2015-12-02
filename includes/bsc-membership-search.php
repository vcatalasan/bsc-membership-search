<?php

class BSC_Membership_Search
{

    static $settings;

    function __construct()
    {
        self::register_shortcodes();
        self::register_callbacks();
        self::register_scripts_stylesheets();
    }

    //__________________________________________________________________________________________________________________

    function register_shortcodes()
    {
    }

    function register_callbacks()
    {
        add_action('xprofile_fields_saved_field', array($this, 'export_membership_table'));
        add_action('xprofile_fields_deleted_field', array($this, 'export_membership_table'));
    }

    function register_scripts_stylesheets()
    {
        add_action('admin_enqueue_scripts', array($this, 'custom_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'custom_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'custom_stylesheets'));
        add_action('wp_print_styles', array($this, 'custom_print_styles'), 100);
    }

    function custom_scripts()
    {
    }

    function custom_stylesheets()
    {
    }

    function custom_print_styles()
    {
        wp_deregister_style('wp-admin');
    }


    // Create BSC Membership Table from BuddyPress xprofile fields data

    function export_membership_table()
    {
        global $wpdb, $bp;

        $fields = $this->bp_get_profile_fields();

        if (empty($fields)) return 0;

        $table_name = 'bsc_membership';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            // DROP OLD VIEW
            $query = 'DROP VIEW bsc_membership';
            $wpdb->query($query);
        }

        // CREATE NEW VIEW
        $query = "CREATE VIEW $table_name AS SELECT a.user_id, b.user_status, b.user_login, b.user_email";
        foreach ($fields as $field_id => $field_name) {
            $query .= sprintf(', GROUP_CONCAT(if(field_id=%s,value,NULL)) as %s', $field_id, $field_name);
        }
        $query .= sprintf(' FROM %s AS a LEFT JOIN %s AS b ON (a.user_id = b.ID) GROUP BY user_id', $bp->profile->table_name_data, $wpdb->prefix . 'users');
        return $wpdb->query($query);
    }

    function bp_get_profile_fields()
    {
        global $wpdb, $bp;

        $fields = array();

        if ($bp) {
            $query = "SELECT id, name FROM {$bp->profile->table_name_fields} WHERE parent_id = 0";
            $records = $wpdb->get_results($query);
            foreach ($records as $record)
                $fields[$record->id] = strtolower(preg_replace("![^a-z0-9]+!i", "_", $record->name));
        }
        return $fields;
    }

}

?>