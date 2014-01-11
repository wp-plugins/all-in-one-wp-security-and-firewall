<?php

class AIOWPSecurity_Brute_Force_Menu extends AIOWPSecurity_Admin_Menu
{
    var $menu_page_slug = AIOWPSEC_BRUTE_FORCE_MENU_SLUG;
    
    /* Specify all the tabs of this menu in the following array */
    var $menu_tabs;

    var $menu_tabs_handler = array(
        'tab1' => 'render_tab1',
        'tab2' => 'render_tab2',
        );
    
    function __construct() 
    {
        $this->render_menu_page();
    }
    
    function set_menu_tabs() 
    {
        $this->menu_tabs = array(
        'tab1' => __('Rename Login Page','aiowpsecurity'),
        //'tab2' => __('TODO','aiowpsecurity'), 
        );
    }

    function get_current_tab() 
    {
        $tab_keys = array_keys($this->menu_tabs);
        $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $tab_keys[0];
        return $tab;
    }

    /*
     * Renders our tabs of this menu as nav items
     */
    function render_menu_tabs() 
    {
        $current_tab = $this->get_current_tab();

        echo '<h2 class="nav-tab-wrapper">';
        foreach ( $this->menu_tabs as $tab_key => $tab_caption ) 
        {
            $active = $current_tab == $tab_key ? 'nav-tab-active' : '';
            echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->menu_page_slug . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';	
        }
        echo '</h2>';
    }
    
    /*
     * The menu rendering goes here
     */
    function render_menu_page() 
    {
        $this->set_menu_tabs();
        $tab = $this->get_current_tab();
        ?>
        <div class="wrap">
        <div id="poststuff"><div id="post-body">
        <?php 
        $this->render_menu_tabs();
        //$tab_keys = array_keys($this->menu_tabs);
        call_user_func(array(&$this, $this->menu_tabs_handler[$tab]));
        ?>
        </div></div>
        </div><!-- end of wrap -->
        <?php
    }
    
    function render_tab1()
    {
        global $wpdb, $aio_wp_security;
        global $aiowps_feature_mgr;
        $aiowps_login_page_slug = '';
        
        if (get_option('permalink_structure')){
            $home_url = trailingslashit(home_url());
        }else{
            $home_url = trailingslashit(home_url()) . '?';
        }

        if(isset($_POST['aiowps_save_rename_login_page_settings']))//Do form submission tasks
        {
            $error = '';
            $nonce=$_REQUEST['_wpnonce'];
            if (!wp_verify_nonce($nonce, 'aiowpsec-rename-login-page-nonce'))
            {
                $aio_wp_security->debug_logger->log_debug("Nonce check failed for rename login page save!",4);
                die("Nonce check failed for rename login page save!");
            }

            if (empty($_POST['aiowps_login_page_slug']) && isset($_POST["aiowps_enable_rename_login_page"])){
                $error .= '<br />'.__('Please enter a value for your login page slug.','aiowpsecurity');
            }else if (!empty($_POST['aiowps_login_page_slug'])){
                $aiowps_login_page_slug = sanitize_text_field($_POST['aiowps_login_page_slug']);
            }
            
            if($error){
                $this->show_msg_error(__('Attention!','aiowpsecurity').$error);
            }else{
                //Save all the form values to the options
                $aio_wp_security->configs->set_value('aiowps_enable_rename_login_page',isset($_POST["aiowps_enable_rename_login_page"])?'1':'');
                $aio_wp_security->configs->set_value('aiowps_login_page_slug',$aiowps_login_page_slug);
                $aio_wp_security->configs->save_config();

                //Recalculate points after the feature status/options have been altered
                $aiowps_feature_mgr->check_feature_status_and_recalculate_points();
                $this->show_msg_settings_updated();
            }
        }
        
        ?>
        <div class="aio_blue_box">
            <?php
            $cookie_based_feature_url = '<a href="admin.php?page='.AIOWPSEC_FIREWALL_MENU_SLUG.'&tab=tab4" target="_blank">Cookie Based Brute Force Prevention</a>';
            $white_list_feature_url = '<a href="admin.php?page='.AIOWPSEC_USER_LOGIN_MENU_SLUG.'&tab=tab3" target="_blank">Login Page White List</a>';
            echo '<p>'.__('An effective Brute Force prevention technique is to change the default WordPress login page URL.', 'aiowpsecurity').'</p>'.
            '<p>'.__('Normally if you wanted to login to WordPress you would type your site\'s home URL followed by wp-login.php.', 'aiowpsecurity').'</p>'.
            '<p>'.__('This feature allows you to change the login URL by setting your own slug and renaming the last portion of the login URL which contains the <strong>wp-login.php</strong> to any string that you like.', 'aiowpsecurity').'</p>'.
            '<p>'.__('By doing doing this, malicious bots and hackers will not be able to access your login page because they will not know the correct login page URL.', 'aiowpsecurity').'</p>'.
            '<div class="aio_section_separator_1"></div>'.
            '<p>'.__('You may also be interested in the following alternative brute force prevention features:', 'aiowpsecurity').'</p>'.
            '<p>'.$cookie_based_feature_url.'</p>'.
            '<p>'.$white_list_feature_url.'</p>';
            ?>
        </div>
        <?php 
        //Show the user the new login URL if this feature is active
        if ($aio_wp_security->configs->get_value('aiowps_enable_rename_login_page')=='1')
        {
        ?>
            <div class="aio_yellow_box">
                <p><?php _e('Your WordPress login page URL has been renamed.', 'aiowpsecurity'); ?></p>
                <p><?php _e('Your current login URL is:', 'aiowpsecurity'); ?></p>
                <p><strong><?php echo $home_url.$aio_wp_security->configs->get_value('aiowps_login_page_slug'); ?></strong></p>
            </div>
            
        <?php
        }
        ?>
        <div class="postbox">
        <h3><label for="title"><?php _e('Rename Login Page Settings', 'aiowpsecurity'); ?></label></h3>
        <div class="inside">
        <?php
        //Display security info badge
        global $aiowps_feature_mgr;
        $aiowps_feature_mgr->output_feature_details_badge("bf-rename-login-page");
        ?>

        <form action="" method="POST">
        <?php wp_nonce_field('aiowpsec-rename-login-page-nonce'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Enable Rename Login Page Feature', 'aiowpsecurity')?>:</th>                
                <td>
                <input name="aiowps_enable_rename_login_page" type="checkbox"<?php if($aio_wp_security->configs->get_value('aiowps_enable_rename_login_page')=='1') echo ' checked="checked"'; ?> value="1"/>
                <span class="description"><?php _e('Check this if you want to enable the rename login page feature', 'aiowpsecurity'); ?></span>
                </td>
            </tr>            
            <tr valign="top">
                <th scope="row"><?php _e('Login Page URL', 'aiowpsecurity')?>:</th>
                <td><code><?php echo $home_url; ?></code><input type="text" size="5" name="aiowps_login_page_slug" value="<?php echo $aio_wp_security->configs->get_value('aiowps_login_page_slug'); ?>" />
                <span class="description"><?php _e('Enter a string which will represent your secure login page slug. You are enouraged to choose something which is hard to guess and only you will remember.', 'aiowpsecurity'); ?></span>
                </td> 
            </tr>
        </table>
        <input type="submit" name="aiowps_save_rename_login_page_settings" value="<?php _e('Save Settings', 'aiowpsecurity')?>" class="button-primary" />
        </form>
        </div></div>
        
        <?php
    }
    
    function render_tab2()
    {
    }

} //end class