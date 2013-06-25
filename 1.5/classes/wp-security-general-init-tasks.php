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
        
        //Add more tasks that need to be executed at init time
    }
    
    function remove_wp_generator_meta_info()
    {
        return '';
    }
    
}