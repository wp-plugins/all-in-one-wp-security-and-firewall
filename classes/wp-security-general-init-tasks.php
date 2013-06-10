<?php

class AIOWPSecurity_General_Init_Tasks
{
    function __construct(){
        global $aio_wp_security;
        
        if($aio_wp_security->configs->get_value('aiowps_remove_wp_generator_meta_info') == '1'){
            add_filter('the_generator', array(&$this,'remove_wp_generator_meta_info'));
        }
        
        //Add more tasks that need to be executed at init time
        
    }
    
    function remove_wp_generator_meta_info()
    {
        return '';
    }
    
}