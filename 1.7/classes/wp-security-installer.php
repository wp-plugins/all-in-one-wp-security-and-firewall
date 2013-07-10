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
                switch_to_blog($old_blog);
                return;
            }
        }
        AIOWPSecurity_Installer::create_db_tables();
        AIOWPSecurity_Configure_Settings::add_option_values();
    }
    
    static function create_db_tables()
    {
        //global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        //"User Login" related tables
	$lockdown_tbl_name = AIOWPSEC_TBL_LOGIN_LOCKDOWN;
        $failed_login_tbl_name = AIOWPSEC_TBL_FAILED_LOGINS;
        $user_login_activity_tbl_name = AIOWPSEC_TBL_USER_LOGIN_ACTIVITY;

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

	update_option("aiowpsec_db_version", AIO_WP_SECURITY_DB_VERSION);
    }
}
