<?php
/*
Plugin Name: Awesome Crop
Plugin URI: http://norex.ca
Description: Allows the admin to set required widths and heights for an image, which changes the Edit Image screen. Requires Advanced Custom Fields version 3.0 or greater.
Author: Ryan Nielson
Author URI: http://norex.ca
Version: 1.0
*/

$awesomecrop = new AwesomeCrop();

class AwesomeCrop
{ 
    function AwesomeCrop()
    {
        add_action('admin_menu', array($this,'admin_menu'));

        // create acf_fields table
        $sql = "CREATE TABLE acrop_fields (
                post_type varchar(100) NOT NULL,
                field_name varchar(100) NOT NULL,
                field_key varchar(100) NOT NULL,
                enabled bool NOT NULL,
                width bigint(20) NOT NULL,
                height bigint(20) NOT NULL,
                aspect_ratio_width bigint(20) NOT NULL,
                aspect_ratio_height bigint(20) NOT NULL,
                PRIMARY KEY (post_type, field_name, field_key)
        );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_action('admin_head-media-upload-popup', array($this, 'popup_head'));
        do_action('admin_head-media-upload-popup');

        wp_enqueue_script('jquery');
        wp_enqueue_script('awesome-crop', plugins_url('/js/awesome-crop.js', __FILE__), '1.0');  

        return true;
    }
        
    function admin_menu() {

        add_options_page('Awesome Crop', 'Awesome Crop', 'manage_options', 'awesomecrop-options', array($this, 'options'));
    }

    function options() {
        include('admin/options.php');
    }

    function popup_head() {
        if(isset($_GET["acf_type"]) && $_GET['acf_type'] == 'image')
        {
            // Deregisters old image-edit so that new image edit can be used.
            wp_deregister_script('imgareaselect');
            wp_deregister_script('image-edit');

            wp_enqueue_script('imgareaselect', plugins_url('/js/imgareaselect/jquery.imgareaselect.js', __FILE__), array('jquery'), '0.9.6');  
            wp_enqueue_script('image-edit', plugins_url('/js/image-edit.js', __FILE__), array('jquery', 'json2', 'imgareaselect'), '1.0' );
        }
    }
  
}

add_action('wp_ajax_awesomecrop_aspect_ratio', 'awesomecrop_aspect_ratio');
add_action('wp_ajax_nopriv_awesomecrop_aspect_ratio', 'awesomecrop_aspect_ratio');
function awesomecrop_aspect_ratio() {
    if($_POST['field_key']){
        global $wpdb;
        $rows = $wpdb->get_row( 'SELECT aspect_ratio_width, aspect_ratio_height, enabled, width, height 
                                FROM acrop_fields
                                WHERE post_type = "' . $_POST['post_type'] . '" AND field_key = "' . $_POST['field_key'] . '";');

        echo json_encode($rows);
    }
    die();
}
        
?>
