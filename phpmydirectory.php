<?php
/*
Plugin Name: phpMyDirectory
Plugin URI: http://www.phpmydirectory.com
Description: Allows wordpress users to automatically log into phpMyDirectory.  The sessions are shared and accounts are created automatically if they do not exist.
Version: 1.1
Author: Accomplish Technology, LLC
Author URI: http://www.phpmydirectory.com
License: LGPLv3
*/
?>
<?php
/*  Copyright 2013  Accomplish Technology, LLC  (email: support@phpmydirectory.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License (LGPL) version 3, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License (LGPL) version 3
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action('wp_login', 'phpmydirectory_login', 10, 2);
//add_action('wp_logout', 'phpmydirectory_logout');
add_action('admin_menu', 'phpmydirectory_menu');

function phpmydirectory_login($user_login, $user) {
    global $wpdb;

    $table_name = rtrim(get_option('phpmydirectory_table_prefix'),'_');
    $pmd_folder = get_option('phpmydirectory_folder');

    $pmd_user = $wpdb->get_row($wpdb->prepare('SELECT id, pass, cookie_salt FROM '.$table_name.'_users WHERE user_email=%s',$user->data->user_email), ARRAY_A);
    if(!$pmd_user) {
        $wpdb->query($wpdb->prepare("INSERT INTO ".$table_name."_users (login,pass,cookie_salt,user_email) VALUES (%s,%s,%s,%s)",$user->data->user_login,$user->data->user_pass,md5($user->data->user_pass),$user->data->user_email));
        $pmd_user = $wpdb->get_row($wpdb->prepare("SELECT id, pass, cookie_salt FROM ".$table_name."_users WHERE user_email=%s",$user->data->user_email),ARRAY_A);
        $wpdb->query($wpdb->prepare("INSERT INTO ".$table_name."_users_groups_lookup (user_id,group_id) VALUES (%d,%d)",$pmd_user['id'],4));
    }
    setcookie('pmd_'.md5($pmd_folder).'_user_login',$pmd_user['id'].':'.md5($pmd_user['pass'].$pmd_user['cookie_salt']), time()+60*60*24,$pmd_folder,null);
}

/*
function phpmydirectory_logout() {
    global $wpdb;
    $pmd_folder = get_option('phpmydirectory_folder');
    $table_name = rtrim(get_option('phpmydirectory_table_prefix'),'_');
    $wpdb->query($wpdb->prepare("DELETE FROM ".$table_name."_sessions WHERE id=%s",session_id()));
    setcookie('pmd_'.md5($pmd_folder).'_user_login','', time()-3600,$pmd_folder,null);
}
*/

function phpmydirectory_menu() {
    add_options_page('phpMyDirectory Settings', 'phpMyDirectory', 'manage_options', 'phpmydirectory-settings', 'phpmydirectory_settings_page');
}

function phpmydirectory_settings_page() {
    if(!current_user_can('manage_options')) {
        wp_die( __('You do not have sufficient permissions to access this page.') );
    }

    $opt_name = 'phpmydirectory_table_prefix';
    $hidden_field_name = 'phpmydirectory_submit_hidden';
    $data_field_name = 'phpmydirectory_table_prefix';

    $opt_name2 = 'phpmydirectory_folder';
    $data_field_name2 = 'phpmydirectory_folder';

    $opt_val = get_option($opt_name);
    $opt_val2 = get_option($opt_name2);

    if(isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y') {
        $opt_val = $_POST[$data_field_name];
        $opt_val2 = $_POST[$data_field_name2];
        update_option($opt_name,$opt_val);
        update_option($opt_name2,$opt_val2);

        // Put an settings updated message on the screen
        ?>
        <div class="updated"><p><strong>phpMyDirectory settings saved.</strong></p></div>
        <?php
    }
    ?>
    <div class="wrap">
    <h2>phpMyDirectory Settings</h2>
    <form name="form1" method="post" action="">
        <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
        <p>Database Table Prefix:
            <input type="text" class="regular-text" name="<?php echo $data_field_name; ?>" value="<?php echo $opt_val; ?>" size="20">
            <p class="description">Usually "pmd_" unless using a custom table prefix.</p>
        </p>
        <p>phpMyDirectory Installation Folder:
            <input type="text" class="regular-text" name="<?php echo $data_field_name2; ?>" value="<?php echo $opt_val2; ?>" size="20">
            <p class="description">If installed in the root use "/" otherwise use the folder name.  Example: "/directory/"</p>
        </p>

        <hr />
        <p class="submit">
            <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
        </p>
    </form>
    </div>
<?php
}
?>