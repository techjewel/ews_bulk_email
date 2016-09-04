<?php
/**
 * Plugin Name: EWS Bulk Mail
 * Plugin URI:  http://www.authlab.io
 * Description: EWS Bulk Mail send mail to individual user or group of users.
 * Version: 10.0.0
 * Author: authLab
 * Author URI: http://www.authlab.io
 */

/**
 * Make sure we don't expose any info if called directly
 */
if (!function_exists('add_action')) {
    echo 'Hi there!  I am just a plugin, not much I can do when called directly.';
    exit;
}
define('WP_EMAIL_USERS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_EMAIL_USERS_PLUGIN_DIR', plugin_dir_path(__FILE__));
require_once('wp-email-user-ajax.php');
require_once('wp-email-user-template.php');
if (!function_exists('ts_weu_load_enqueue_scripts')) {
    function ts_weu_load_enqueue_scripts()
    {
        wp_enqueue_script('jquery');
    }
}
add_action('init', 'ts_weu_load_enqueue_scripts');
if (!function_exists('ts_weu_enqueue_script')) {
    function ts_weu_enqueue_script()
    {
        wp_enqueue_script('wp-email-user-datatable-script', plugins_url('js/jquery.dataTables.min.js', __FILE__), array(), '1.0.0', false);
        wp_enqueue_script('wp-email-user-script', plugins_url('js/email-admin.js', __FILE__), array(), '1.0.0', false);
        wp_enqueue_style('wp-email-user-style', plugins_url('css/style.css', __FILE__));
        wp_enqueue_style('wp-email-user-datatable-style', plugins_url('css/jquery.dataTables.min.css', __FILE__));
    }
}
add_action('admin_enqueue_scripts', 'ts_weu_enqueue_script');
if (!function_exists('weu_admin_page')) {
    function weu_admin_page()
    {
        global $current_user, $wpdb, $wp_roles;
        $user_roles = $current_user->roles;
        $roles = $wp_roles->get_names();
        if ($user_roles[0] == 'administrator') {
            wp_enqueue_script('wp-email-user-datatable-script', plugins_url('js/jquery.dataTables.min.js', __FILE__), array(), '1.0.0', false);
            wp_enqueue_script('wp-email-user-script', plugins_url('js/email-admin.js', __FILE__), array(), '1.0.0', false);
            wp_enqueue_style('wp-email-user-style', plugins_url('css/style.css', __FILE__));
            wp_enqueue_style('wp-email-user-datatable-style', plugins_url('css/jquery.dataTables.min.css', __FILE__));

            if (isset($_POST['rbtn']) && $_POST['rbtn'] == 'csv') {
                for ($j = 0; $j < count($_POST['csv_file_id']); $j++) {
                    $table_name = $wpdb->prefix . 'email_user';
                    $myrows = $wpdb->get_results("SELECT * FROM $table_name WHERE status = 'csv' and id=" . $_POST['csv_file_id'][$j]);
                    $mixed = unserialize($myrows[0]->template_value);
                    $csv = $_POST['csv_file_id'][$j];
                    $csv_to = array();
                    foreach ($mixed as $line) {
                        list($name, $last, $email) = explode(',', $line);
                        array_push($csv_to, $email);
                    }
                    $mail_body = $_POST['wau_mailcontent'];
                    $subject = $_POST['wau_sub'];
                    $body = stripslashes($mail_body);
                    $from_email = sanitize_email($_POST['wau_from']);
                    $from_name = sanitize_text_field($_POST['wau_from_name']);
                    sanitize_text_field($body);
                    $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
                    $headers[] = 'Content-Type: text/html; charset="UTF-8"';
                    $wau_status = wp_mail($csv_to, $subject, $body, $headers);
                }
            }
            $wau_to = array();
            if (isset($_POST['rbtn']) && $_POST['rbtn'] == 'user') {
                for ($j = 0; $j < count($_POST['ea_user_name']); $j++) {
                    $user = $_POST['ea_user_name'][$j];
                    array_push($wau_to, $_POST[$user]);
                }
            } elseif (isset($_POST['rbtn']) && $_POST['rbtn'] == 'role') {
                for ($k = 0; $k < count($_POST['user_role']); $k++) {
                    $args = array(
                        'role' => $_POST['user_role'][$k]
                    );
                    $str_brk = explode(' ', $args[role]);
                    $str_join = join('_', $str_brk);
                    $str_join = strtolower($str_join);
                    $args = array(
                        'role' => $str_join
                    );
                    $wau_grp_users = get_users($args); //get all users
                    for ($m = 0; $m < count($wau_grp_users); $m++) {
                        array_push($wau_to, $wau_grp_users[$m]->data->user_email);
                    }
                }
            }
            /* Send Mail to user using wp_mail */
            global $wpdb;
            $wau_status = 2;
            $wau_too = array();
            if (isset($_POST['rbtn']) && $_POST['rbtn'] == 'user' || isset($_POST['rbtn']) && $_POST['rbtn'] == 'role') {
                $temp_key = isset($_POST['wau_sub']) ? $_POST['wau_sub'] : '';
                $chk_val = isset($_POST['temp']) ? $_POST['temp'] : '';
                $table_name = $wpdb->prefix . 'email_user';
                if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
                    $sql = "CREATE TABLE $table_name(
                        id int(11) NOT NULL AUTO_INCREMENT,
                        template_key varchar(20) NOT NULL,
                        template_value longtext NOT NULL,
                        status varchar(20) NOT NULL,
                        UNIQUE KEY id(id)   
                     );";
                    $rs = $wpdb->query($sql);
                }
                if ($chk_val == 1)
                    $wpdb->query($wpdb->prepare("INSERT INTO `" . $table_name . "`(`template_key`, `template_value`, `status`) VALUES (%s,%s,%s)
				",
                        $temp_key, stripcslashes($_POST['wau_mailcontent']), 'template'));
                for ($j = 0; $j < count($wau_to); $j++) {
                    $curr_email_data = get_user_by('email', $wau_to[$j]);
                    $user_id = $curr_email_data->ID;
                    $user_info = get_userdata($user_id);
                    $user_val = get_user_meta($user_id);
                    array_push($wau_too, $user_info->display_name);
                    $replace = array(
                        $user_val['nickname'][0],
                        $user_val['first_name'][0],
                        $user_val['last_name'][0],
                        get_option('blogname'),
                        $wau_too[$j],
                        $wau_to[$j]
                    );
                    $find = array(
                        '[[user-nickname]]',
                        '[[first-name]]',
                        '[[last-name]]',
                        '[[site-title]]',
                        '[[display-name]]',
                        '[[user-email]]'
                    );
                    $mail_body = str_replace($find, $replace, $_POST['wau_mailcontent']);
                    $subject = $_POST['wau_sub'];
                    $body = stripslashes($mail_body);
                    $from_email = sanitize_email($_POST['wau_from']);
                    $from_name = sanitize_text_field($_POST['wau_from_name']);
                    sanitize_text_field($body);
                    $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
                    $headers[] = 'Content-Type: text/html; charset="UTF-8"';
                    $wau_status = wp_mail($wau_to[$j], $subject, $body, $headers);
                } // for ends
            }
            if ($wau_status == 1) {
                echo '<div id="message" class="updated notice is-dismissible"><p>Your message has been sent successfully.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
            } elseif ($wau_status == 0) {
                echo '<div id="message" class="updated notice is-dismissible error"><p> Sorry,your message has not sent.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
            }
            $wau_users = get_users(); //get all wp users
            echo "<div class='wrap'>";
            echo "<h2> EWS Bulk Mail - Send Email </h2>";
            echo "</div>";
            echo "<p>Send email to individual as well as group of users.</p>";
            echo '<form name="myform" class="wau_form" method="POST" action="#" onsubmit="return validation()" >';

            /* User role */
            echo '<table id="" class="form-table" >';
            echo '<tbody>';
            echo '<tr>';
            echo '<th>From Name</th> <td colspan="1"><input type="text" name="wau_from_name" value="' . $current_user->display_name . '" class="wau_boxlen"  id="wau_from_name" required></td>';
            echo '</tr>';
            echo '<tr>';
            echo '<th>From Email</th> <td colspan="2"><input type="text" name="wau_from" value="' . $current_user->user_email . '" class="wau_boxlen"  id="wau_from" required></td>';
            echo '</tr>';
            echo '<tr>';
            echo "<th><b>Send Email By &nbsp; </b></th>";
            echo '<td style="width: 224px"><input type="radio" name="rbtn" id="user_role" onclick="radioFunction()" value="user" checked > User &nbsp;</td>';
            echo '<td style="width: 224px"><input type="radio" name="rbtn" id="r_role" onclick="radioFunction()" value="role"> Role </td>';
            echo "</tr>";
            /**
             * Select Users
             **/
            echo '<tr class="wau_user_toggle"><th></th><td colspan="3">';
            echo '<table id="example" class="display alluser_datatable" cellspacing="0" width="100%">
			<div id="new_existing" style="margin-bottom:20px;">
			<input type="radio" class="new_existing" name="new_existing" value="New">New 
			<input type="radio" class="new_existing" name="new_existing" value="Existing">Existing
			</div>
	        <thead>
	            <tr style="text-align:left"> <th style="text-align:center" ><input name="select_all" value="1" id="example-select-all" class="select-all" type="checkbox"></th>
	                 <th>Display name</th>
	                 <th>Email</th>
	                 <th>Status</th>
	            </tr>
	        </thead>    
	        <tbody>';
            foreach ($wau_users as $user) {
                echo '<tr style="text-align:left">';
                echo '<td style="text-align:center"><input type="checkbox" name="ea_user_name[]" value="' . $user->ID . '" class="select-all"></td>';
                echo '<td><span id="getDetail">' . esc_html($user->display_name) . '</span></td>';
                echo '<td><span >' . esc_html($user->user_email) . '</span></td>';
                $eleigibility = get_user_meta($user->ID, 'rpr_current_customer', true) != 1 ? 'New' : 'Existing';
                echo '<td><span >' . $eleigibility . '</span></td>';
                echo '</tr>';
            }
            echo '</tbody></table>'; // end user Data table for user
            foreach ($wau_users as $user) {
                echo '<input type="hidden" name="' . esc_html($user->ID) . '" value="' . esc_html($user->user_email) . '">';
            }
            echo '<table id="example1" class="display allcsv_datatable" cellspacing="0" width="100%">
	        <thead>
	            <tr style="text-align:left"> <th style="text-align:center" ><input name="select_all_csv" value="1" id="example-csv-select-all" class="select-all" type="checkbox"></th>
	                 <th>CSV File Name</th>
	                
	            </tr>
	        </thead>    
	        <tbody>';
            $table_name = $wpdb->prefix . 'email_user';
            $myrows = $wpdb->get_results("SELECT id,template_key FROM $table_name WHERE status = 'csv'");
            foreach ($myrows as $csv_file) {

                echo '<tr style="text-align:left">';
                echo '<td style="text-align:center"><input type="checkbox" name="csv_file_id[]" value="' . $csv_file->id . '" class="select-all"></td>';
                echo '<td><span id="getDetail">' . esc_html($csv_file->template_key) . '</span></td>';

                echo '</tr>';
            }
            echo '</tbody></table></td></tr>'; //end csv table
            foreach ($myrows as $csv_file) {
                echo '<input type="hidden" name="' . esc_html($csv_file->id) . '" value="' . esc_html($csv_file->template_key) . '">';
            }
            /* select roles */
            $mail_content = "";
            echo '<tr id="wau_user_role" style="display:none">';
            echo '<th>Select Roles</th>';
            echo '<td colspan="3"><select name="user_role[]" multiple class="wau_boxlen" id="wau_role" >';
            echo '<option value="" selected disabled>-- Select Role --</option>';
            foreach ($roles as $value) {
                echo '<option> ' . $value . ' </option>';
            }
            echo '</select></td>';
            echo '</tr>';
            $table_name = $wpdb->prefix . 'email_user';
            $myrows = $wpdb->get_results("SELECT id, template_key, template_value FROM $table_name WHERE status = 'template'");
            $template_path_one = plugins_url('template1.html', __FILE__);
            $template_path_two = plugins_url('template2.html', __FILE__);
            echo '<tr>';
            echo '<th>Template </th><td colspan="3"><select autocomplete="off" id="wau_template" name="mail_template[]" class="wau-template-selector" style="width:100%">
        <option selected="selected">- Select -</option>
        <option value="' . $template_path_one . '" id="wau_template_t1"> Default Template - 1 </option>
        <option value="' . $template_path_two . '" id="wau_template_t2"> Default Template - 2 </option>';
            for ($i = 0; $i < count($myrows); $i++) {
                echo '<option value="' . $myrows[$i]->id . '" id="am" >' . $myrows[$i]->template_key . '</option>';
            }
            '</select></td>';
            echo '</tr>';
            echo '<tr>';
            echo '<th>Subject</th> <td colspan="3"><input type="text" name="wau_sub" class="wau_boxlen"  id="sub" placeholder="write your email subject here" required></td>';
            echo '</tr>';
            echo '<tr>';
            echo '<th scope="row" valign="top"><label for="wau_mailcontent">Message</label></th>';
            echo '<td colspan="3">';
            echo '<div id="msg" class="wau_boxlen" name="wau_mailcontent">';
            wp_editor($mail_content, "wau_mailcontent", array('wpautop' => false, 'media_buttons' => true));
            echo '</div>';
            echo "For CSV List option following shortcode placeholder will not work</br>";
            echo '<b> [[user-nickname]] : </b>use this placeholder to display user nickname </br>
          <b> [[first-name]] : </b>use this placeholder to display user first name  </br>
          <b> [[last-name]] :  </b>use this placeholder to display user last name </br>
          <b> [[site-title]] : </b>use this placeholder to display your site title</br>
          <b> [[display-name]] : </b>use this placeholder for display name</br>
          <b> [[user-email]] : </b>use this placeholder to display user email</br>
           ';
            echo '<input type="checkbox" value="1" name="temp">Save template</br>';
            echo '</td>';
            echo '</tr>';

            echo '<tr>';
            echo '<th></th>';
            echo '<td colspan="3">';
            echo '<div><input type="submit" value="Send" class="button button-hero button-primary" id="weu_send" ></div>';
            echo '</td>';
            echo '</tr>';
            echo '</tbody>';
            echo '</table>';
            echo '</form>';
        }
    }

}
/**
 * add admin menu to wp menu
 */
function add_weu_custom_menu()
{
    global $current_user;
    $user_roles = $current_user->roles;
    if ($user_roles[0] == 'administrator') {
        add_menu_page('EWS Bulk Mail page', 'EWS Bulk Mail', 'manage_options', 'weu-admin-page', 'weu_admin_page', 'dashicons-email-alt');
        add_submenu_page('weu-admin-page', 'Send Email', 'Send Email', 'manage_options', 'weu_send_email', 'weu_admin_page');
        add_submenu_page('weu-admin-page', 'WP Template page', 'Template Editor', 'manage_options', 'weu-template', 'weu_template');
        remove_submenu_page('weu-admin-page', 'weu-admin-page');
    }
}


add_action('admin_menu', 'add_weu_custom_menu');

function weu_setup_activation_data()
{
    global $wpdb, $table_prefix;
    $table_name = $wpdb->prefix . 'email_user';
    if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name(
	                        id int(11) NOT NULL AUTO_INCREMENT,
	                        template_key varchar(20) NOT NULL,
	                        template_value longtext NOT NULL,
	                        status varchar(20) NOT NULL,
	                        UNIQUE KEY id(id)   
	                     );";
        $rs = $wpdb->query($sql);
    }
    $table_name_notifi = $wpdb->prefix . 'weu_user_notification';
    if ($wpdb->get_var("show tables like '$table_name_notifi'") != $table_name_notifi) {
        $sql = "CREATE TABLE $table_name_notifi(
                    id int(11) NOT NULL AUTO_INCREMENT,
                    template_id int(11) NOT NULL,
                    template_value longtext NOT NULL,
                   	email_for varchar(20) NOT NULL,
                   	email_by varchar(20) NOT NULL,
                   	email_value longtext NOT NULL,
                    UNIQUE KEY id(id)   
                 );";
        $rs2 = $wpdb->query($sql);
    }


    $table_email_user = $wpdb->prefix.'email_user';
    // create table if the table is not exists
    if($wpdb->get_var("show tables like '$table_email_user'") != $table_email_user){
        $sql = "CREATE TABLE $table_name(
                            id int(11) NOT NULL AUTO_INCREMENT,
                            template_key varchar(20) NOT NULL,
                            template_value longtext NOT NULL,
                            status varchar(20) NOT NULL,
                            UNIQUE KEY id(id)   
                            );";
        $rs3 = $wpdb->query($sql);
    }
}

register_activation_hook(__FILE__, 'weu_setup_activation_data');