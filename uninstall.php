<?php 
delete_option('weu_smtp_data_options'); 
delete_option('weu_ar_config_options'); 
delete_option('weu_new_user_register'); 
delete_option('weu_new_post_publish'); 
delete_option('weu_password_reset'); 
delete_option('weu_new_comment_post'); 
delete_option('weu_user_role_changed'); 
global $wpdb, $table_prefix;
$slider_table = $table_prefix.'email_user';
$sql = "DROP TABLE $slider_table;";
$wpdb->query($sql);