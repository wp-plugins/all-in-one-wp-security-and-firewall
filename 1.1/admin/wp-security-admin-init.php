<?php
/* 
 * Inits the admin dashboard side of things.
 * Main admin file which loads all settings panels and sets up admin menus. 
 */
class AIOWPSecurity_Admin_Init
{
    var $main_menu_page;
    var $dashboard_menu;
    var $settings_menu;
    var $user_accounts_menu;
    var $user_login_menu;
    var $db_security_menu;
    var $filesystem_menu;
    var $whois_menu;
    var $blacklist_menu;
    var $firewall_menu;

    function __construct()
    {
        $this->admin_includes();
        add_action('admin_menu', array(&$this, 'create_admin_menus'));

        //make sure we are on our plugin's menu pages
        if (isset($_GET['page']) && strpos($_GET['page'], AIOWPSEC_MENU_SLUG_PREFIX ) !== false ) {
            add_action('admin_print_scripts', array(&$this, 'admin_menu_page_scripts'));
            add_action('admin_print_styles', array(&$this, 'admin_menu_page_styles'));            
            add_action('admin_init', array( &$this, 'admin_init_hook_handler')); //For changing button text inside media uploader (thickbox)
        }
    }
    
    function admin_includes()
    {
        include_once('wp-security-admin-menu.php');
    }

    function admin_menu_page_scripts() 
    {
        wp_enqueue_script('jquery');
        wp_enqueue_script('postbox');
        wp_enqueue_script('dashboard');
        wp_enqueue_script('thickbox');
        wp_enqueue_script('media-upload');
        wp_register_script('aiowpsec-admin-js', AIO_WP_SECURITY_URL. '/js/wp-security-admin-script.js', array('jquery'));
        wp_enqueue_script('aiowpsec-admin-js');
    }
    
    function admin_menu_page_styles() 
    {
        wp_enqueue_style('dashboard');
        wp_enqueue_style('thickbox');
        wp_enqueue_style('global');
        wp_enqueue_style('wp-admin');
        wp_enqueue_style('aiowpsec-admin-css', AIO_WP_SECURITY_URL. '/css/wp-security-admin-styles.css');
    }
    
    function admin_init_hook_handler()
    {
        $this->aiowps_media_uploader_modification();
        $this->initialize_feature_manager();
    }

    //For media uploader thickbox - change button text
    function aiowps_media_uploader_modification()
    {
        global $pagenow;
        if ('media-upload.php' == $pagenow || 'async-upload.php' == $pagenow)
        {
            // Here we will customize the 'Insert into Post' Button text inside Thickbox
            add_filter( 'gettext', array($this, 'aiowps_media_uploader_replace_thickbox_text'), 1, 2);
        }
    }

    function aiowps_media_uploader_replace_thickbox_text($translated_text, $text)
    {
        if ('Insert into Post' == $text)
        {
            $referer = strpos(wp_get_referer(), 'aiowpsec');
            if ($referer != '')
            {
                return ('Select File');
            }
        }
        return $translated_text;
    }

    function initialize_feature_manager()
    {
        $aiowps_feature_mgr  = new AIOWPSecurity_Feature_Item_Manager();
        $aiowps_feature_mgr->initialize_features();
        $aiowps_feature_mgr->check_and_set_feature_status();
        $aiowps_feature_mgr->calculate_total_points(); 
        $GLOBALS['aiowps_feature_mgr'] = $aiowps_feature_mgr;
    }
    
    function create_admin_menus()
    {
        $menu_icon_url = AIO_WP_SECURITY_URL.'/images/plugin-icon.png';
        $this->main_menu_page = add_menu_page(__('WP Security', 'aiowpsecurity'), __('WP Security', 'aiowpsecurity'), AIOWPSEC_MANAGEMENT_PERMISSION, AIOWPSEC_MAIN_MENU_SLUG , array(&$this, 'handle_dashboard_menu_rendering'), $menu_icon_url);
        add_submenu_page(AIOWPSEC_MAIN_MENU_SLUG, __('Dashboard', 'aiowpsecurity'),  __('Dashboard', 'aiowpsecurity') , AIOWPSEC_MANAGEMENT_PERMISSION, AIOWPSEC_MAIN_MENU_SLUG, array(&$this, 'handle_dashboard_menu_rendering'));
        add_submenu_page(AIOWPSEC_MAIN_MENU_SLUG, __('Settings', 'aiowpsecurity'),  __('Settings', 'aiowpsecurity') , AIOWPSEC_MANAGEMENT_PERMISSION, AIOWPSEC_SETTINGS_MENU_SLUG, array(&$this, 'handle_settings_menu_rendering'));
        add_submenu_page(AIOWPSEC_MAIN_MENU_SLUG, __('User Accounts', 'aiowpsecurity'),  __('User Accounts', 'aiowpsecurity') , AIOWPSEC_MANAGEMENT_PERMISSION, AIOWPSEC_USER_ACCOUNTS_MENU_SLUG, array(&$this, 'handle_user_accounts_menu_rendering'));
        add_submenu_page(AIOWPSEC_MAIN_MENU_SLUG, __('User Login', 'aiowpsecurity'),  __('User Login', 'aiowpsecurity') , AIOWPSEC_MANAGEMENT_PERMISSION, AIOWPSEC_USER_LOGIN_MENU_SLUG, array(&$this, 'handle_user_login_menu_rendering'));
        add_submenu_page(AIOWPSEC_MAIN_MENU_SLUG, __('Database Security', 'aiowpsecurity'),  __('Database Security', 'aiowpsecurity') , AIOWPSEC_MANAGEMENT_PERMISSION, AIOWPSEC_DB_SEC_MENU_SLUG, array(&$this, 'handle_database_menu_rendering'));
        add_submenu_page(AIOWPSEC_MAIN_MENU_SLUG, __('Filesystem Security', 'aiowpsecurity'),  __('Filesystem Security', 'aiowpsecurity') , AIOWPSEC_MANAGEMENT_PERMISSION, AIOWPSEC_FILESYSTEM_MENU_SLUG, array(&$this, 'handle_filesystem_menu_rendering'));
        add_submenu_page(AIOWPSEC_MAIN_MENU_SLUG, __('WHOIS Lookup', 'aiowpsecurity'),  __('WHOIS Lookup', 'aiowpsecurity') , AIOWPSEC_MANAGEMENT_PERMISSION, AIOWPSEC_WHOIS_MENU_SLUG, array(&$this, 'handle_whois_menu_rendering'));
        add_submenu_page(AIOWPSEC_MAIN_MENU_SLUG, __('Blacklist Manager', 'aiowpsecurity'),  __('Blacklist Manager', 'aiowpsecurity') , AIOWPSEC_MANAGEMENT_PERMISSION, AIOWPSEC_BLACKLIST_MENU_SLUG, array(&$this, 'handle_blacklist_menu_rendering'));
        add_submenu_page(AIOWPSEC_MAIN_MENU_SLUG, __('Firewall', 'aiowpsecurity'),  __('Firewall', 'aiowpsecurity') , AIOWPSEC_MANAGEMENT_PERMISSION, AIOWPSEC_FIREWALL_MENU_SLUG, array(&$this, 'handle_firewall_menu_rendering'));
        do_action('aiowpsecurity_admin_menu_created');
    }
        
    function handle_dashboard_menu_rendering()
    {
        include_once('wp-security-dashboard-menu.php');
        $this->dashboard_menu = new AIOWPSecurity_Dashboard_Menu();
    }

    function handle_settings_menu_rendering()
    {
        include_once('wp-security-settings-menu.php');
        $this->settings_menu = new AIOWPSecurity_Settings_Menu();
        
    }
    
    function handle_user_accounts_menu_rendering()
    {
        include_once('wp-security-user-accounts-menu.php');
        $this->user_accounts_menu = new AIOWPSecurity_User_Accounts_Menu();
    }
    
    function handle_user_login_menu_rendering()
    {
        include_once('wp-security-user-login-menu.php');
        $this->user_login_menu = new AIOWPSecurity_User_Login_Menu();
    }
    
    function handle_database_menu_rendering()
    {
        include_once('wp-security-database-menu.php');
        $this->db_security_menu = new AIOWPSecurity_Database_Menu();
    }

    function handle_filesystem_menu_rendering()
    {
        include_once('wp-security-filesystem-menu.php');
        $this->filesystem_menu = new AIOWPSecurity_Filescan_Menu();
    }

    function handle_whois_menu_rendering()
    {
        include_once('wp-security-whois-menu.php');
        $this->whois_menu = new AIOWPSecurity_WhoIs_Menu();
    }

    function handle_blacklist_menu_rendering()
    {
        include_once('wp-security-blacklist-menu.php');
        $this->blacklist_menu = new AIOWPSecurity_Blacklist_Menu();
    }

    function handle_firewall_menu_rendering()
    {
        include_once('wp-security-firewall-menu.php');
        $this->firewall_menu = new AIOWPSecurity_Firewall_Menu();
    }
}//End of class

