<?php 
$radioButton=isset($_POST['rbtn'])?$_POST['rbtn']:'';
$wau_to = array(); 
if($radioButton=='user'){
	for($j=0;$j<count($_POST['ea_user_name']);$j++){
			$user= $_POST['ea_user_name'][$j];
			array_push($wau_to,$_POST[$user]);
		}
}
elseif($radioButton=='role'){ 
	 	for($k=0;$k<count($_POST['user_role']);$k++){
		     	$args = array(
					'role' => $_POST['user_role'][$k]
				);
			    	$wau_grp_users=get_users( $args ); //get all users
				   	for($m=0;$m<count($wau_grp_users);$m++){
				   	array_push($wau_to,$wau_grp_users[$m]->data->user_email);
					}
			}
	}
elseif ($radioButton == 'csv') {
	for($j=0;$j<count($_POST['csv_file_id']);$j++){
	    	$myrows = $wpdb->get_results( "SELECT * FROM wp_email_user WHERE status = 'csv' and id=".$_POST['csv_file_id'][$j]);
			$mixed= unserialize ( $myrows[0]->template_value);
			$csv= $_POST['csv_file_id'][$j];
            $csv_to = array();  
			foreach ($mixed as $line){ 
			list($name,$last,$email) = explode(',', $line);
			array_push($csv_to,$email);
		}		
	}
}	
 global $wpdb;
$schedule_mail=array();
$schedule_mail['name']=trim(isset($_POST['wau_from_name']))?$_POST['wau_from_name']:'';
$schedule_mail['from']=trim(isset($_POST['wau_from']))?$_POST['wau_from']:'';
$schedule_mail['subject']=trim(isset($_POST['wau_sub']))?$_POST['wau_sub']:'';
$schedule_mail['body' ]=trim(isset($_POST['wau_mailcontent']))?$_POST['wau_mailcontent']:'';
update_option( 'updated_option_table', $schedule_mail);
update_option( 'updated_option_table1', $wau_to);
update_option( 'updated_option_table2', $csv_to);


        global $current_user,$wpdb;
        $user_roles = $current_user->roles;
        if($user_roles[0]=='administrator'){ 
	if( isset($_POST['rbtn']) && $_POST['rbtn'] == 'csv'){ 
	for($j=0;$j<count($_POST['csv_file_id']);$j++){
            $myrows = $wpdb->get_results( "SELECT * FROM wp_email_user WHERE status = 'csv' and id=".$_POST['csv_file_id'][$j]);
			$mixed= unserialize ( $myrows[0]->template_value);
			$csv= $_POST['csv_file_id'][$j];
            $csv_to = array();  
			foreach ($mixed as $line){ 
			list($name,$last,$email) = explode(',', $line);
			array_push($csv_to,$email);
       }
		$mail_body = $_POST['wau_mailcontent'];
        $subject = $_POST['wau_sub'];
		$body = stripslashes($mail_body);
		$from_email=sanitize_email($_POST['wau_from']);
   	    $from_name=sanitize_text_field($_POST['wau_from_name']);
	    sanitize_text_field( $body );
        $headers[] = 'From: '.$from_name.' <'. $from_email.'>';
	    $headers[] = 'Content-Type: text/html; charset="UTF-8"';
		$wau_status = wp_mail($csv_to, $subject, $body, $headers);
  		}
	}

	$wau_to = array();  
	if( isset($_POST['rbtn']) && $_POST['rbtn'] == 'user'){ 
		for($j=0;$j<count($_POST['ea_user_name']);$j++){
			$user= $_POST['ea_user_name'][$j];
			array_push($wau_to,$_POST[$user]);
		}
	}
	elseif( isset($_POST['rbtn']) && $_POST['rbtn'] =='role'){ 
	 	for($k=0;$k<count($_POST['user_role']);$k++){
		     	$args = array(
					'role' => $_POST['user_role'][$k]
				);
			    	$wau_grp_users=get_users( $args ); //get all users
				   	for($m=0;$m<count($wau_grp_users);$m++){
				   	array_push($wau_to,$wau_grp_users[$m]->data->user_email);
					}
			}
	}
/* Send mail to user using wp_mail */  
    global $wpdb;
	$wau_status=2;
	$wau_too = array();
	if(isset($_POST['rbtn']) && $_POST['rbtn'] =='user' ||  isset($_POST['rbtn']) && $_POST['rbtn'] == 'role' ){
		$temp_key=$_POST['wau_sub']; // subject as a key
   		$chk_val=$_POST['temp'];     // save template checkbox val
		$table_name = $wpdb->prefix.'email_user';
 
		if($wpdb->get_var("show tables like '$table_name'") != $table_name){
		$sql = "CREATE TABLE $table_name(
                        id int(11) NOT NULL AUTO_INCREMENT,
                        template_key varchar(20) NOT NULL,
                        template_value longtext NOT NULL,
                        status varchar(20) NOT NULL,
                        UNIQUE KEY id(id)   
                         );";
            $rs = $wpdb->query($sql);    
}
       if($chk_val==1)
			$wpdb->query($wpdb->prepare( "INSERT INTO `".$table_name."`(`template_key`, `template_value`, `status`) VALUES (%s,%s,%s)
				",
				$temp_key,stripcslashes($_POST['wau_mailcontent']),'template'));

	for($j=0;$j<count($wau_to);$j++){
		$curr_email_data = get_user_by ( 'email', $wau_to[$j] );
		$user_id =  $curr_email_data->ID;
		$user_info = get_userdata($user_id);
        $user_val=get_user_meta($user_id);
        array_push($wau_too,$user_info->display_name);
		$replace= array( 
				$user_val['nickname'][0],
				$user_val['first_name'][0],
				$user_val['last_name'][0],
				get_option( 'blogname' ),
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
	    $mail_body = str_replace( $find, $replace, $_POST['wau_mailcontent'] );
        $subject = $_POST['wau_sub'];
		$body = stripslashes($mail_body);
		$from_email=sanitize_email($_POST['wau_from']);
   	    $from_name=sanitize_text_field($_POST['wau_from_name']);
	    sanitize_text_field( $body );
        $headers[] = 'From: '.$from_name.' <'. $from_email.'>';
	    $headers[] = 'Content-Type: text/html; charset="UTF-8"';
		$wau_status = wp_mail($wau_to[$j], $subject, $body, $headers);
			} // for ends
		}

		
	}
?>
