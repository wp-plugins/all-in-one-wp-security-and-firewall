<?php
class AIOWPSecurity_User_Registration
{

    function __construct() 
    {
        add_action('user_register', array(&$this, 'aiowps_user_registration_action_handler'));
    }
    

    /*
     * This function will add a special meta string in the users table
     * Meta field name: 'aiowps_account_status'
     * Meta field value: 'pending' 
     */
    function aiowps_user_registration_action_handler($user_id)
    {
        global $wpdb, $aio_wp_security;
        //Check if auto pending new account status feature is enabled
        if ($aio_wp_security->configs->get_value('aiowps_enable_manual_registration_approval') == '1')
        {
            $res = add_user_meta($user_id, 'aiowps_account_status', 'pending');
            if (!$res){
                $aio_wp_security->debug_logger->log_debug("aiowps_user_registration_action_handler: Error adding user meta data: aiowps_account_status",4);
            }
        }
    }

    /*
     * This function will set the special meta string in the usermeta table so that the account becomes active
     * Meta field name: 'aiowps_account_status'
     * Meta field values: 'active', 'pending', etc
     */
    function aiowps_set_user_account_status($user_id, $status)
    {
        global $wpdb, $aio_wp_security;
        $res = update_user_meta($user_id, 'aiowps_account_status', $status);
        if (!$res){
            $aio_wp_security->debug_logger->log_debug("aiowps_set_user_account_status: Error updating user meta data: aiowps_account_status",4);
        }
    }
    
}