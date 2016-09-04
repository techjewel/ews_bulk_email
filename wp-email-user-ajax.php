<?php
$temp_name= isset($_POST['temp_key'])?$_POST['temp_key']:''; // key for template
$csv_name = isset($_POST['key'])?$_POST['key']:''; // key for csv
if($csv_name =='edit' || $csv_name =='delete' || $csv_name =='update'){ 
add_action( 'wp_ajax_weu_my_csv_action', 'weu_csv_action_callback' );
function weu_csv_action_callback(){
	global $wpdb;
    $table_name = $wpdb->prefix.'email_user';
    $csvlist = $_POST['csv_file_title'];
    $del_key = $_POST['del_csv'];
    if($del_key =='del_csv'){  // for Delete
    $myrows = $wpdb->query($wpdb->prepare("DELETE FROM `".$table_name."` where id = $csvlist"));
    $data = 'File Deleted';
    echo $data;
    } 
    $edit_key = $_POST['edit_csv'];
	if($edit_key == 'edit_csv'){ // for show
        $myrows = $wpdb->get_results( "SELECT id,template_value FROM `".$table_name."` where id = $csvlist");
        $data = unserialize($myrows[0]->template_value); 
        echo "<table border='4' id='show_table' style='width:93%; text-align: center;'><th>First Name</th><th>Last Name</th><th>Email</th>";
        
    foreach ($data as $line){ 
            list($name,$last,$email) = explode(',', $line);
            echo "<tr><td>".$name."</td>";
            echo "<td>".$last."</td>";
            echo "<td>".$email."</td></tr>";
      }echo"</table>";
    }
    $update_val = ($_POST['update_val']);
    $update_key = $_POST['update_csv'];
	if($update_key == 'update_csv'){ // for Update 
		print_r(serialize($_POST['update_val']));
	    $myrows = $wpdb->query($wpdb->prepare("UPDATE `".$table_name."` SET template_value = ".$update_val." where id = $csvlist"));	
	    $data=$myrows;
        echo $data;
	}
    //echo $data;
	wp_die();
   }
}
add_action( 'wp_ajax_weu_my_action', 'weu_tem_action_callback' );
function weu_tem_action_callback() {
	global $wpdb;
	$table_name = $wpdb->prefix.'email_user';
	$temp = $_POST['filetitle']; 
    $temp_del_key = $_POST['temp_del_key']; 
    if($temp_del_key == 'delete_temp'){
        $myrows = $wpdb->query($wpdb->prepare("DELETE FROM `".$table_name."` where id = $temp"));
        $data= 'Template Deleted';
    }
    $temp_sel_key=$_POST['temp_sel_key']; 
    if($temp_sel_key == 'select_temp'){ 
        $myrows = $wpdb->get_results( "SELECT template_value FROM `".$table_name."` where id = $temp");
        $data=$myrows[0]->template_value;
    }
    echo $data;
	wp_die();
  }
?>