<?php

class AIOWPSecurity_Installer
{
    static function run_installer()
    {	
        global $wpdb;
        if (function_exists('is_multisite') && is_multisite()) 
        {
            // check if it is a network activation - if so, run the activation function for each blog id
            if (isset($_GET['networkwide']) && ($_GET['networkwide'] == 1)) 
            {
                $old_blog = $wpdb->blogid;
                // Get all blog ids
                $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
                foreach ($blogids as $blog_id) {
                    switch_to_blog($blog_id);
                    AIOWPSecurity_Installer::create_db_tables();
                    AIOWPSecurity_Configure_Settings::add_option_values();
                }
                AIOWPSecurity_Installer::create_db_backup_dir(); //Create a backup dir in the WP uploads directory
                switch_to_blog($old_blog);
                return;
            }
        }
        AIOWPSecurity_Installer::create_db_tables();
        AIOWPSecurity_Configure_Settings::add_option_values();
        AIOWPSecurity_Installer::create_db_backup_dir(); //Create a backup dir in the WP uploads directory
    }
    
    static function create_db_tables()
    {
        //global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        //"User Login" related tables
	$lockdown_tbl_name = AIOWPSEC_TBL_LOGIN_LOCKDOWN;
        $failed_login_tbl_name = AIOWPSEC_TBL_FAILED_LOGINS;
        $user_login_activity_tbl_name = AIOWPSEC_TBL_USER_LOGIN_ACTIVITY;
        $aiowps_global_meta_tbl_name = AIOWPSEC_TBL_GLOBAL_META_DATA;

	$ld_tbl_sql = "CREATE TABLE " . $lockdown_tbl_name . " (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        user_login VARCHAR(150) NOT NULL,
        lockdown_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        release_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        failed_login_ip varchar(100) NOT NULL DEFAULT '',
        PRIMARY KEY  (id)
        )ENGINE=MyISAM DEFAULT CHARSET=utf8;";
	dbDelta($ld_tbl_sql);

	$fl_tbl_sql = "CREATE TABLE " . $failed_login_tbl_name . " (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        user_login VARCHAR(150) NOT NULL,
        failed_login_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        login_attempt_ip varchar(100) NOT NULL DEFAULT '',
        PRIMARY KEY  (id)
        )ENGINE=MyISAM DEFAULT CHARSET=utf8;";
	dbDelta($fl_tbl_sql);
        
        $ula_tbl_sql = "CREATE TABLE " . $user_login_activity_tbl_name . " (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        user_login VARCHAR(150) NOT NULL,
        login_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        logout_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        login_ip varchar(100) NOT NULL DEFAULT '',
        login_country varchar(150) NOT NULL DEFAULT '',
        browser_type varchar(150) NOT NULL DEFAULT '',
        PRIMARY KEY  (id)
        )ENGINE=MyISAM DEFAULT CHARSET=utf8;";
	dbDelta($ula_tbl_sql);

        $gm_tbl_sql = "CREATE TABLE " . $aiowps_global_meta_tbl_name . " (
        meta_id bigint(20) NOT NULL auto_increment,
        date_time datetime NOT NULL default '0000-00-00 00:00:00',
        meta_key1 varchar(255) NOT NULL,
        meta_key2 varchar(255) NOT NULL,
        meta_key3 varchar(255) NOT NULL,
        meta_key4 varchar(255) NOT NULL,
        meta_key5 varchar(255) NOT NULL,
        meta_value1 varchar(255) NOT NULL,
        meta_value2 text NOT NULL,
        meta_value3 text NOT NULL,
        meta_value4 longtext NOT NULL,
        meta_value5 longtext NOT NULL,
        PRIMARY KEY  (meta_id)
        )ENGINE=MyISAM DEFAULT CHARSET=utf8;";
        dbDelta($gm_tbl_sql);
                
	update_option("aiowpsec_db_version", AIO_WP_SECURITY_DB_VERSION);
    }
    
    static function create_db_backup_dir()
    {
        //Create our folder in the "uploads" directory
	$upload_dir = wp_upload_dir();
	$aiowps_dir = $upload_dir['basedir'] . '/'.AIO_WP_SECURITY_BACKUPS_DIR_NAME;           
        if(!is_dir($aiowps_dir)) {
            mkdir($aiowps_dir , 0755, true);
            //Let's also create an empty index.html file in this folder
            $index_file = $aiowps_dir.'/index.html';
            $handle = fopen($index_file, 'w'); //or die('Cannot open file:  '.$index_file);
            fclose($handle);
            
            //Create an .htacces file
            //Write some rules which will only allow people originating from wp admin page to download the DB backup
            $rules = '';
            $rules .= '<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{HTTP_REFERER} !(wp-admin/admin.php) [NC]
RewriteRule .* http://127.0.0.1 [L]
</IfModule>' . PHP_EOL;
            $file = $aiowps_dir.'/.htaccess';
            file_put_contents($file, $rules);
        }
    }

}
