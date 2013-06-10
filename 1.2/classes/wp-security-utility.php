<?php

class AIOWPSecurity_Utility
{
    function __construct(){
        //NOP
    }
    
    static function get_current_page_url() 
    {
	$pageURL = 'http';
	if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
	    $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} 
	else{
	    $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	return $pageURL;
    }
    
    static function redirect_to_url($url,$delay='0',$exit='1')
    {
        if(empty($url)){
            echo "<br /><strong>Error! The URL value is empty. Please specify a correct URL value to redirect to!</strong>";
            exit;
        }
        if (!headers_sent()){
            header('Location: ' . $url);
        }
        else{
            echo '<meta http-equiv="refresh" content="'.$delay.';url='.$url.'" />';
        }
        if($exit == '1'){
            exit;
        }
    }

    static function get_logout_url_with_after_logout_url_value($after_logout_url)
    {
        return AIOWPSEC_WP_URL.'?aiowpsec_do_log_out=1&after_logout='.$after_logout_url;        
    }
    
    /*
     * Checks if a particular username exists in the WP Users table
     */
    static function check_user_exists($username) 
    {
        global $wpdb;

        //if username is empty just return false
        if ( $username == '' ) {
            return false;
        }

        //check users table
        $user = $wpdb->get_var( "SELECT user_login FROM `" . $wpdb->users . "` WHERE user_login='" . sanitize_text_field( $username ) . "';" );
        $userid = $wpdb->get_var( "SELECT ID FROM `" . $wpdb->users . "` WHERE ID='" . sanitize_text_field( $username ) . "';" );

        if ( $user == $username || $userid == $username ) {
            return true;
        } else {
            return false;
        }
    }
    
    /*
     * This function will return a list of user accounts which have login and nick names which are identical
     */
    static function check_identical_login_and_nick_names() {
        global $wpdb;
        $accounts_found = $wpdb->get_results( "SELECT ID,user_login FROM `" . $wpdb->users . "` WHERE user_login<=>display_name;", ARRAY_A);
        return $accounts_found;
    }

    
    static function add_query_data_to_url($url, $name, $value)
    {
        if (strpos($url, '?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }
        $url .= $name . '='. $value;
        return $url;
    }

    
    /*
     * Generates a random alpha-numeric number
     */
    static function generate_alpha_numeric_random_string($string_length)
    {
        //Charecters present in table prefix
        $allowed_chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $string = '';
        //Generate random string
        for ($i = 0; $i < $string_length; $i++) {
            $string .= $allowed_chars[rand(0, strlen($allowed_chars) - 1)];
        }
        return $string;
    }
    
}
