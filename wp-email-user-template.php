<?php
function weu_template(){
    wp_enqueue_script( 'wp-email-user-datatable-script', plugins_url('js/jquery.dataTables.min.js', __FILE__ ), array(), '1.0.0', false );
    wp_enqueue_script( 'wp-email-user-script', plugins_url('js/email-admin.js', __FILE__ ), array(), '1.0.0', false );
	wp_enqueue_style( 'wp-email-user-style', plugins_url('css/style.css', __FILE__ ) );
	wp_enqueue_style( 'wp-email-user-datatable-style', plugins_url('css/jquery.dataTables.min.css', __FILE__ ) );
	echo "<div class='wrap'>";
	echo "<h2> WP Email Template Editor</h2>";
	echo "</div>"; /*end header */
	echo '<form name="myform" class="wau_form" method="POST" action="#" onsubmit="return validation()">';
    echo '<table id="" class="form-table" >';
	echo '<tbody>';
	global $wpdb; 
	$table_name = $wpdb->prefix.'email_user'; 	
	$myrows = $wpdb->get_results( "SELECT id, template_key, template_value FROM $table_name WHERE status = 'template'" );

	echo '<tr>';
	echo '<th>Template</th><td colspan="1">
        <select autocomplete="off" id="wau_template_single" name="mail_template" class="wau-template-selector" style="width:100%; height: 50px ">
            <option value="0" selected="selected" >- Select Template-</option>';
            for ($i=0;$i<count($myrows);$i++) {
            echo '<option value="'.$myrows[$i]->id.'" id="am" >'.$myrows[$i]->template_key.'</option>';   }
       '</select></td>';
    $mail_content="";
	echo '</tr>';
	echo '<tr>';
	echo '<th>Template Name</th>';
	echo '<td><input type="text" name="wau_temp" class="wau_boxlen" id="weu_temp_name" placeholder="Your Template Name here" required=""></td>';
	echo '</tr>';
	echo '<th scope="row" valign="top"><label for="weu_show_area">Message</label></th>';
    echo '<td colspan="2">';
	echo '<div id="msg" class="wau_boxlen" name="weu_show_area">';	
		wp_editor($mail_content, "weu_show_area",array('wpautop'=>false,'media_buttons' => true));
	echo '</div></td>';
    echo '</tbody>';
	echo '</table>';
	echo '<input type="submit" value="Save" style="margin-left: 220px;" class="button button-hero button-primary" name="save_template">  ';
	echo '<input type="submit" value="Delete" id="weu_delete_template" class="button button-hero button-primary" name="delete_template" >';
	echo '</form>';
}
if(isset($_POST['save_template']) && $_POST['save_template'] == 'Save'){
        $temp_name = $_POST['wau_temp'];
        $table_name = $wpdb->prefix.'email_user';

        $template_id = $_POST['mail_template'];
        if($template_id == 0) {
            // create if new template
            $template_result = $wpdb->query($wpdb->prepare( "INSERT INTO `".$table_name."`(`template_key`, `template_value`, `status`) VALUES (%s,%s,%s)", $temp_name,stripcslashes($_POST['weu_show_area']),'template'));
        } else {
            // @todo: Update the exiting template where id = $template_id
        }

        if($template_result==1){
            echo '<div id="message1" class="updated notice is-dismissible"><p>Your Template has been Saved.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
        }
        elseif($template_result==0){
            echo '<div id="message2" class="updated notice is-dismissible error"><p> Sorry,your template has not saved.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
	    }
}