<?php
/*
Plugin Name: Backup DB To Dropbox
Author: TuanVA
Author URI: http://tamhuyet.com/
Plugin URI: http://tamhuyet.com/plugin
Description: Free plugin from Tamhuyet.Com, Auto backup your database to Dropbox, Simple way to protect your database daily.
Version: 1.0 
*/
add_action('admin_menu', 'wp2dropbox_create_menu');
register_activation_hook( __FILE__,'tamhuyet_create_dropbox_db');


// Setup time to backup
add_filter( 'cron_schedules', 'myprefix_add_weekly_cron_schedule' );
function myprefix_add_weekly_cron_schedule( $schedules ) {
    $schedules['xdaily'] = array(
        'interval' => 86400, // 1 week in seconds
        'display'  => __( 'Once xdaily' ),
    );
    return $schedules;
}

// Add acction hool to scheduled
if (!wp_next_scheduled('myprefix_my_cron_action')) {
    wp_schedule_event( time(), 'xdaily', 'myprefix_my_cron_action' );
}

//wp_clear_scheduled_hook( 'myprefix_my_cron_action' );
//wp_clear_scheduled_hook( 'wp2dropbox_action_to_do' );

// Call function from hook
add_action( 'myprefix_my_cron_action', 'wp2dropbox_action_to_do' );
add_action( 'wp2dropbox_firsttime', 'wp2dropbox_action_to_do' );


function wp2dropbox_action_to_do() {
    require 'DropboxUploader.php';
    global $wpdb;
    $table_name = $wpdb->prefix."options";
    
    $email = $wpdb->get_row($wpdb->prepare('SELECT option_value FROM '.$table_name.' WHERE option_name = "tamhuyet_dropbox_email"'));
    $emailadd = $email->option_value;

    $password = $wpdb->get_row($wpdb->prepare('SELECT option_value FROM '.$table_name.' WHERE option_name = "tamhuyet_dropbox_pass"'));
    $passwordvalue = $password->option_value;
    
    if($emailadd != "your-email@gmail.com"){
    // Mysql account

    $url = site_url();
    include_once $url . '/wp-config.php';
    $dbname = DB_NAME;
    $dbhost ="localhost"; 
    $dbuser = DB_USER; 
    $dbpass = DB_PASSWORD;
    
    $folder = "db_backup"; // folder in Dropbox. Auto create

    $db_backup = plugin_dir_path( __FILE__ )."/".$dbname."_".date('Y-m-d-H-i').".sql.gz";
    mysql_connect($dbhost,$dbuser,$dbpass); 
    mysql_select_db($dbname); 

    $sql = "mysqldump -u ".$dbuser." -h ".$dbhost." -p".$dbpass." ".$dbname." | gzip -9 > ".$db_backup; 
    system($sql);
        try {
            $uploader = new DropboxUploader($emailadd,$passwordvalue);
            $uploader->upload($db_backup,$folder);
        } catch(Exception $e) {}
        unlink($db_backup);
    }
}




function wp2dropbox_create_menu() {
    //create new top-level menu
    add_menu_page('Auto Backup your database to Dropbox', 'Wp2dropbox', 'administrator', __FILE__, 'wp2dropbox_settings_page',plugins_url('/dropbox.png', __FILE__));
}


function wp2dropbox_settings_page() {
    global $wpdb;
    $table_name = $wpdb->prefix."options";

    if(isset($_POST) && isset($_POST['wp2dropbox_email']) && isset($_POST['wp2dropbox_pass'])){
        if($_POST['wp2dropbox_pass'] != "" && $_POST['wp2dropbox_email'] != ""){
            $wpdb->update($table_name, array( 'option_value' => $_POST['wp2dropbox_pass']), array('option_name' => 'tamhuyet_dropbox_pass'));
            $wpdb->update($table_name, array( 'option_value' => $_POST['wp2dropbox_email']), array('option_name' => 'tamhuyet_dropbox_email'));
            echo "<script type='text/javascript'>alert('Updated your account.. First backup in 2 min.. Please check dropbox!');</script>";
            wp_schedule_single_event(time() + 60,'wp2dropbox_firsttime');
        }

    }
    
    $email = $wpdb->get_row($wpdb->prepare('SELECT option_value FROM '.$table_name.' WHERE option_name = "tamhuyet_dropbox_email"'));
    $emailadd = $email->option_value;

    $password = $wpdb->get_row($wpdb->prepare('SELECT option_value FROM '.$table_name.' WHERE option_name = "tamhuyet_dropbox_pass"'));
    $passwordvalue = $password->option_value;
?>
<div class="wrap">
<h2>Auto Backup your database to Dropbox</h2>

<?php
if($emailadd == 'your-email@gmail.com'){
echo "You must provide your account at Dropbox. If not have, you can sign up <a href='https://db.tt/NxoxWfGK' target='_blank'>here</a><br>";
}
?>
<form method="post" action="">
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Your Dropbox Email</th>
        <td><input type="text" name="wp2dropbox_email" value="<?php echo $emailadd; ?>" /></td>
        </tr>
         
        <tr valign="top">
        <th scope="row">Password</th>
        <td><input type="password" name="wp2dropbox_pass" value="<?php echo $passwordvalue; ?>" /></td>
        </tr>
    </table>
    
    <?php submit_button(); ?>

</form>
<Br><br>Your database your automatic backup to dropbox daily
<bR><br>Support via skype: <b>ahuhyeu</b>, or Facebook: <b>fb.com/ahuhyeu</b>. Thanks! 
</div>
<?php }

function tamhuyet_create_dropbox_db()
{
    global $wpdb;
    $table_name = $wpdb->prefix."options";

        $wpdb->delete( $table_name, array('option_name' => 'tamhuyet_dropbox_email'));
        $wpdb->delete( $table_name, array('option_name' => 'tamhuyet_dropbox_pass'));

        $wpdb->insert(
                $table_name, 
                array( 
                    'option_name' => "tamhuyet_dropbox_email", 
                    'option_value' => "your-email@gmail.com" 
                ), 
                array( 
                    '%s', 
                    '%s'
                )
            );

                $wpdb->insert(
                $table_name, 
                array( 
                    'option_name' => "tamhuyet_dropbox_pass", 
                    'option_value' => "123456" 
                ), 
                array( 
                    '%s', 
                    '%s'
                )
            );
}

// Add settings link on plugin page
function wp2dropbox_plugin_settings_link($links) { 
  $settings_link = '<a href="admin.php?page=backup-db-to-dropbox/wp2dropbox.php">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
 
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'wp2dropbox_plugin_settings_link' );

register_deactivation_hook( __FILE__,'wp2dropbox_deactivation');
function wp2dropbox_deactivation(){
        global $wpdb;
        $table_name = $wpdb->prefix."options"; 
        $wpdb->delete( $table_name, array('option_name' => 'tamhuyet_dropbox_email'));
        $wpdb->delete( $table_name, array('option_name' => 'tamhuyet_dropbox_pass'));
        wp_clear_scheduled_hook( 'myprefix_my_cron_action' );
        wp_clear_scheduled_hook( 'wp2dropbox_action_to_do' );
}
?>
