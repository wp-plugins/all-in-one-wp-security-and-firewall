<?php

class AIOWPSecurity_General_Init_Tasks
{
    function __construct(){
        global $aio_wp_security;
        
        if($aio_wp_security->configs->get_value('aiowps_remove_wp_generator_meta_info') == '1'){
            add_filter('the_generator', array(&$this,'remove_wp_generator_meta_info'));
        }
        
        //For the cookie based brute force prevention feature
        $bfcf_secret_word = $aio_wp_security->configs->get_value('aiowps_brute_force_secret_word');
        if(isset($_GET[$bfcf_secret_word])){
            //If URL contains secret word in query param then set cookie and then redirect to the login page
            AIOWPSecurity_Utility::set_cookie_value($bfcf_secret_word, "1");
            AIOWPSecurity_Utility::redirect_to_url(AIOWPSEC_WP_URL."/wp-admin");
        }
        
        //For site lockout feature
        if($aio_wp_security->configs->get_value('aiowps_site_lockout') == '1'){
            if (!is_user_logged_in() && !current_user_can('administrator') && !is_admin() && !in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ))) {
                $this->site_lockout_tasks();
            }
        }
        
        //Add more tasks that need to be executed at init time
    }
    
    function remove_wp_generator_meta_info()
    {
        return '';
    }
    
    function site_lockout_tasks(){
        nocache_headers();
        header("HTTP/1.0 503 Service Unavailable");
        remove_action('wp_head','head_addons',7);
        include_once(AIO_WP_SECURITY_PATH.'/other-includes/wp-security-visitor-lockout-page.php');
        exit();
    }
}