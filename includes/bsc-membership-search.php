<?php
include "bsc-membership-search-options.php";

class BSC_Membership_Search
{

    static $settings;

    function __construct()
    {
        // prevent program from continuing if not ready
        if (!self::$settings['program']['ready']) return;

        self::register_shortcodes();
        self::register_callbacks();
        self::register_scripts_stylesheets();

        new BSC_Membership_Search_Options();
    }

    //__________________________________________________________________________________________________________________

    function register_shortcodes()
    {
    }

    function register_callbacks()
    {
        add_action('xprofile_fields_saved_field', array($this, 'export_membership_table'));
        add_action('xprofile_fields_deleted_field', array($this, 'export_membership_table'));
        add_action('xprofile_updated_profile', array($this, 'xprofile_updated_profile'), 10, 5);
        add_action('update_directory_search', array($this, 'update_directory_search'), 10, 1);

        add_filter('bps_request_data', array($this, 'search_form'), 10, 1);
        add_filter('bsc_membership_fields', array($this, 'bsc_membership_fields'));
        add_filter('clean_up_data', array($this, 'clean_up_data'), 10, 1);
    }

    function xprofile_updated_profile($user_id, $field_ids, $errors, $old_values, $new_values) {
        $fields = $this->bp_get_profile_fields();
        $update = array(
            'user_id' => $user_id
        );
        foreach ($field_ids as $field_id) {
            $update[ $fields[ $field_id ]['name'] ] = $new_values[ $field_id ]['value'];
        }

        do_action('update_directory_search', apply_filters('clean_up_data', (object)$update));
    }

    function update_directory_search($profile) {
        $api_enabled = get_option('directory_api_enabled');
        $api_endpoint = get_option('directory_api_endpoint');
        $api_enabled and $this->sendPost($api_endpoint, $profile);
    }

    function clean_up_data(stdClass $obj) {
        $objVars = get_object_vars($obj);

        if(count($objVars) > 0) {

            $search_exports = BSC_Membership_Search::$settings['search']['search_exports'];
            $search_keywords = BSC_Membership_Search::$settings['search']['search_keywords'];


            foreach($objVars as $propName => $propVal) {
                if(gettype($propVal) === "object") {
                    $cObj = $this->clean_up_data($propVal);
                    if($cObj === null) {
                        // strip out null value
                        unset($obj->$propName);
                    } else {
                        $obj->$propName = $cObj;
                    }
                } else {
                    if(is_null($propVal)) {
                        // strip out null value
                        unset($obj->$propName);
                    } elseif (is_serialized($propVal)) {
                        // unserialize data
                        $obj->$propName = unserialize($propVal);
                    }
                    // process only exported properties
                    if (in_array($propName, $search_exports)) {
                        in_array($propName, $search_keywords) and $obj->$propName = $this->array_flatten($this->keywords($obj->$propName));
                    } else {
                        unset($obj->$propName);
                    }
                }
            }
        } else {
            return null;
        }
        return $obj;
    }

    function keywords($obj) {
        $keywords = $obj;
        if (!is_array($keywords)) {
            $keywords = explode( ' ', trim(
                preg_replace( '/ +/', ' ', preg_replace( '/[^A-Za-z0-9 ]/', ' ', urldecode( html_entity_decode( strip_tags( $obj ) ) ) ) )
            ) );
        }
        return count($keywords) > 1 ?
            array_map(array($this, 'keywords'), $keywords) : ucwords(strtolower($obj));
    }

    function array_flatten($array) {
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->array_flatten($value));
            } else {
                $result[] = $value;
            }
        }
        return $result;
    }

    // use PHP streams API to send data
    function sendPost($server, $data) {
        $data = json_encode($data);

        $result = file_get_contents($server, null, stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/json' . "\r\n"
                            . 'Content-Length: ' . strlen($data) . "\r\n",
                'content' => $data,
            ),
        )));
        return $result;
    }

    // use curl to send data
    function sendPost2($server, $data) {
        $data = json_encode($data);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $server);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data))
        );

        $result = curl_exec ($ch);
        curl_close ($ch);
        return $result;
    }

    function search_form($content) {
        error_log(print_r($content,true));
        return $content;
    }

    function register_scripts_stylesheets()
    {
        add_action('admin_enqueue_scripts', array($this, 'custom_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'custom_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'custom_stylesheets'));
        add_action('wp_enqueue_scripts', array($this, 'custom_stylesheets'));
        add_action('wp_print_styles', array($this, 'custom_print_styles'), 100);
    }

    function custom_scripts()
    {
    }

    function custom_stylesheets()
    {
        wp_enqueue_style('directory-search-style', self::$settings['program']['dir_url'] . 'style.css');
    }

    function custom_print_styles()
    {
        wp_deregister_style('wp-admin');
    }

    function bsc_membership_fields() {
        $fields = $this->bp_get_profile_fields();
        $fields['[1]'] = array('name' =>'user_id', 'type' => 'integer');
        $fields['[2]'] = array('name' => 'user_status', 'type' => 'integer');
        $fields['[3]'] = array('name' => 'user_login', 'type' => 'text');
        $fields['[4]'] = array('name' => 'user_email', 'type' => 'text');
        return $fields;
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
        foreach ($fields as $id => $field) {
            $query .= sprintf(", GROUP_CONCAT(if(field_id=%d,value,NULL)) as '%.64s'", $id, $field['name']);
        }
        $query .= sprintf(' FROM %s AS a LEFT JOIN %s AS b ON (a.user_id = b.ID) GROUP BY user_id', $bp->profile->table_name_data, $wpdb->prefix . 'users');
        return $wpdb->query($query);
    }

    function bp_get_profile_fields()
    {
        global $wpdb, $bp;

        $fields = array();

        if ($bp) {
            $query = "SELECT id, name, type FROM {$bp->profile->table_name_fields} WHERE parent_id = 0";
            $records = $wpdb->get_results($query);
            foreach ($records as $record)
                $fields[$record->id] = array('name' => $this->camel_case($record->name), 'type' => $record->type);
        }
        return $fields;
    }

    function camel_case($str, array $noStrip = [])
    {
        // non-alpha and non-numeric characters are converted to spaces
        $str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', $str);
        $str = trim($str);
        // uppercase the first character of each word
        $str = ucwords($str);
        $str = str_replace(" ", "", $str);
        $str = lcfirst($str);

        return $str;
    }
}

?>