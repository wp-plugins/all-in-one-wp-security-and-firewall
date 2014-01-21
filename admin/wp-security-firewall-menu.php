<?php

class AIOWPSecurity_Firewall_Menu extends AIOWPSecurity_Admin_Menu
{
    var $menu_page_slug = AIOWPSEC_FIREWALL_MENU_SLUG;
    
    /* Specify all the tabs of this menu in the following array */
    var $menu_tabs;

    var $menu_tabs_handler = array(
        'tab1' => 'render_tab1',
        'tab2' => 'render_tab2',
        'tab3' => 'render_tab3',
        );
    
    function __construct() 
    {
        $this->render_menu_page();
    }
    
    function set_menu_tabs() 
    {
        $this->menu_tabs = array(
        'tab1' => __('Basic Firewall Rules', 'aiowpsecurity'),
        'tab2' => __('Additional Firewall Rules', 'aiowpsecurity'),
        'tab3' => __('5G Blacklist Firewall Rules', 'aiowpsecurity'),
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
        global $aiowps_feature_mgr;
        global $aio_wp_security;
        if(isset($_POST['aiowps_apply_basic_firewall_settings']))//Do form submission tasks
        {
            $nonce=$_REQUEST['_wpnonce'];
            if (!wp_verify_nonce($nonce, 'aiowpsec-enable-basic-firewall-nonce'))
            {
                $aio_wp_security->debug_logger->log_debug("Nonce check failed on enable basic firewall settings!",4);
                die("Nonce check failed on enable basic firewall settings!");
            }

            //Save settings
            if(isset($_POST['aiowps_enable_basic_firewall']))
            {
                $aio_wp_security->configs->set_value('aiowps_enable_basic_firewall','1');
            } 
            else
            {
                $aio_wp_security->configs->set_value('aiowps_enable_basic_firewall','');
            }

            $aio_wp_security->configs->set_value('aiowps_enable_pingback_firewall',isset($_POST["aiowps_enable_pingback_firewall"])?'1':'');

            //Commit the config settings
            $aio_wp_security->configs->save_config();
            
            //Recalculate points after the feature status/options have been altered
            $aiowps_feature_mgr->check_feature_status_and_recalculate_points();

            //Now let's write the applicable rules to the .htaccess file
            $res = AIOWPSecurity_Utility_Htaccess::write_to_htaccess();

            if ($res)
            {
                $this->show_msg_updated(__('Settings were successfully saved', 'aiowpsecurity'));
            }
            else if($res == -1)
            {
                $this->show_msg_error(__('Could not write to the .htaccess file. Please check the file permissions.', 'aiowpsecurity'));
            }
        }

        ?>
        <h2><?php _e('Firewall Settings', 'aiowpsecurity')?></h2>
        <form action="" method="POST">
        <?php wp_nonce_field('aiowpsec-enable-basic-firewall-nonce'); ?>            

        <div class="aio_blue_box">
            <?php
            $backup_tab_link = '<a href="admin.php?page='.AIOWPSEC_SETTINGS_MENU_SLUG.'&tab=tab2" target="_blank">backup</a>';
            $info_msg = sprintf( __('This should not have any impact on your site\'s general functionality but if you wish you can take a %s of your .htaccess file before proceeding.', 'aiowpsecurity'), $backup_tab_link);
            echo '<p>'.__('The features in this tab allow you to activate some basic firewall security protection rules for your site.', 'aiowpsecurity').
            '<br />'.__('The firewall functionality is achieved via the insertion of special code into your currently active .htaccess file.', 'aiowpsecurity').
            '<br />'.$info_msg.'</p>';
            ?>
        </div>
        <?php 
        //Show the message if pingback rule is active
        if ($aio_wp_security->configs->get_value('aiowps_enable_pingback_firewall')=='1')
        {
        ?>
            <div class="aio_yellow_box">
                <p><?php _e('Attention:', 'aiowpsecurity'); ?>
                <br /><?php _e('Currently the ', 'aiowpsecurity'); ?><strong><?php _e('Enable Pingback Protection', 'aiowpsecurity'); ?></strong><?php _e(' is active.', 'aiowpsecurity'); ?></p>
                <p><strong><?php _e('Please beware that if you are using the WordPress iOS App, then you will need to deactivate this feature in order for the app to work properly.', 'aiowpsecurity'); ?></strong></p>
            </div>
            
        <?php
        }
        ?>

        <div class="postbox">
        <h3><label for="title"><?php _e('Basic Firewall Settings', 'aiowpsecurity'); ?></label></h3>
        <div class="inside">
        <?php
        //Display security info badge
        $aiowps_feature_mgr->output_feature_details_badge("firewall-basic-rules");
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Enable Basic Firewall Protection', 'aiowpsecurity')?>:</th>                
                <td>
                <input name="aiowps_enable_basic_firewall" type="checkbox"<?php if($aio_wp_security->configs->get_value('aiowps_enable_basic_firewall')=='1') echo ' checked="checked"'; ?> value="1"/>
                <span class="description"><?php _e('Check this if you want to apply basic firewall protection to your site.', 'aiowpsecurity'); ?></span>
                <span class="aiowps_more_info_anchor"><span class="aiowps_more_info_toggle_char">+</span><span class="aiowps_more_info_toggle_text"><?php _e('More Info', 'aiowpsecurity'); ?></span></span>
                <div class="aiowps_more_info_body">
                        <?php 
                        echo '<p class="description">'.__('This setting will implement the following basic firewall protection mechanisms on your site:', 'aiowpsecurity').'</p>';
                        echo '<p class="description">'.__('1) Protect your htaccess file by denying access to it.', 'aiowpsecurity').'</p>';
                        echo '<p class="description">'.__('2) Disable the server signature.', 'aiowpsecurity').'</p>';
                        echo '<p class="description">'.__('3) Limit file upload size (10MB).', 'aiowpsecurity').'</p>';
                        echo '<p class="description">'.__('4) Protect your wp-config.php file by denying access to it.', 'aiowpsecurity').'</p>';
                        echo '<p class="description">'.__('The above firewall features will be applied via your .htaccess file and should not affect your site\'s overall functionality.', 'aiowpsecurity').'</p>';
                        echo '<p class="description">'.__('You are still advised to take a backup of your active .htaccess file just in case.', 'aiowpsecurity').'</p>';
                        ?>
                </div>
                </td>
            </tr>            
        </table>
        </div></div>
        
        <div class="postbox">
        <h3><label for="title"><?php _e('WordPress Pingback Vulnerability Protection', 'aiowpsecurity'); ?></label></h3>
        <div class="inside">
        <?php
        //Display security info badge
        $aiowps_feature_mgr->output_feature_details_badge("firewall-pingback-rules");
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Enable Pingback Protection', 'aiowpsecurity')?>:</th>                
                <td>
                <input name="aiowps_enable_pingback_firewall" type="checkbox"<?php if($aio_wp_security->configs->get_value('aiowps_enable_pingback_firewall')=='1') echo ' checked="checked"'; ?> value="1"/>
                <span class="description"><?php _e('Check this if you are not using the WP XML-RPC functionality and you want to enable protection against WordPress pingback vulnerabilities.', 'aiowpsecurity'); ?></span>
                <span class="aiowps_more_info_anchor"><span class="aiowps_more_info_toggle_char">+</span><span class="aiowps_more_info_toggle_text"><?php _e('More Info', 'aiowpsecurity'); ?></span></span>
                <div class="aiowps_more_info_body">
                        <?php 
                        echo '<p class="description">'.__('This setting will add a directive in your .htaccess to disable access to the WordPress xmlrpc.php file which is responsible for the XML-RPC functionality such as pingbacks in WordPress.', 'aiowpsecurity').'</p>';
                        echo '<p class="description">'.__('Hackers can exploit various pingback vulnerabilities in the WordPress XML-RPC API in a number of ways such as:', 'aiowpsecurity').'</p>';
                        echo '<p class="description">'.__('1) Denial of Service (DoS) attacks', 'aiowpsecurity').'</p>';
                        echo '<p class="description">'.__('2) Hacking internal routers.', 'aiowpsecurity').'</p>';
                        echo '<p class="description">'.__('3) Scanning ports in internal networks to get info from various hosts.', 'aiowpsecurity').'</p>';
                        echo '<p class="description">'.__('Apart from the security protection benefit, this feature may also help reduce load on your server, particularly if your site currently has a lot of unwanted traffic hitting the XML-RPC API on your installation.', 'aiowpsecurity').'</p>';
                        echo '<p class="description">'.__('NOTE: You should only enable this feature if you are not currently using the XML-RPC functionality on your WordPress installation.', 'aiowpsecurity').'</p>';
                        ?>
                </div>
                </td>
            </tr>            
        </table>
        </div></div>
        <input type="submit" name="aiowps_apply_basic_firewall_settings" value="<?php _e('Save Basic Firewall Settings', 'aiowpsecurity')?>" class="button-primary" />
        </form>
        <?php
    }
    
    function render_tab2()
    {
        global $aio_wp_security;
        $error = '';
        if(isset($_POST['aiowps_apply_additional_firewall_settings']))//Do advanced firewall submission tasks
        {
            $nonce=$_REQUEST['_wpnonce'];
            if (!wp_verify_nonce($nonce, 'aiowpsec-enable-additional-firewall-nonce'))
            {
                $aio_wp_security->debug_logger->log_debug("Nonce check failed on enable advanced firewall settings!",4);
                die("Nonce check failed on enable advanced firewall settings!");
            }

            //Save settings
            if(isset($_POST['aiowps_disable_index_views']))
            {
                $aio_wp_security->configs->set_value('aiowps_disable_index_views','1');
            }
            else
            {
                $aio_wp_security->configs->set_value('aiowps_disable_index_views','');
            }
            
            if(isset($_POST['aiowps_disable_trace_and_track']))
            {
                $aio_wp_security->configs->set_value('aiowps_disable_trace_and_track','1');
            }
            else
            {
                $aio_wp_security->configs->set_value('aiowps_disable_trace_and_track','');
            }

            if(isset($_POST['aiowps_forbid_proxy_comments']))
            {
                $aio_wp_security->configs->set_value('aiowps_forbid_proxy_comments','1');
            } 
            else
            {
                $aio_wp_security->configs->set_value('aiowps_forbid_proxy_comments','');
            }

            if(isset($_POST['aiowps_deny_bad_query_strings']))
            {
                $aio_wp_security->configs->set_value('aiowps_deny_bad_query_strings','1');
            } 
            else
            {
                $aio_wp_security->configs->set_value('aiowps_deny_bad_query_strings','');
            }

            if(isset($_POST['aiowps_advanced_char_string_filter']))
            {
                $aio_wp_security->configs->set_value('aiowps_advanced_char_string_filter','1');
            } 
            else
            {
                $aio_wp_security->configs->set_value('aiowps_advanced_char_string_filter','');
            }

            //Commit the config settings
            $aio_wp_security->configs->save_config();

            //Now let's write the applicable rules to the .htaccess file
            $res = AIOWPSecurity_Utility_Htaccess::write_to_htaccess();

            if ($res)
            {
                $this->show_msg_updated(__('You have successfully saved the Additional Firewall Protection configuration', 'aiowpsecurity'));
            }
            else if($res == -1)
            {
                $this->show_msg_error(__('Could not write to the .htaccess file. Please check the file permissions.', 'aiowpsecurity'));
            }
            
            if($error)
            {
                $this->show_msg_error($error);
            }

        }
        ?>
        <h2><?php _e('Additional Firewall Protection', 'aiowpsecurity')?></h2>
        <div class="aio_blue_box">
            <?php
            $backup_tab_link = '<a href="admin.php?page='.AIOWPSEC_SETTINGS_MENU_SLUG.'&tab=tab2" target="_blank">backup</a>';
            $info_msg = sprintf( __('Due to the nature of the code being inserted to the .htaccess file, this feature may break some functionality for certain plugins and you are therefore advised to take a %s of .htaccess before applying this configuration.', 'aiowpsecurity'), $backup_tab_link);

            echo '<p>'.__('This feature allows you to activate more advanced firewall settings to your site.', 'aiowpsecurity').
            '<br />'.__('The advanced firewall rules are applied via the insertion of special code to your currently active .htaccess file.', 'aiowpsecurity').
            '<br />'.$info_msg.'</p>';
            ?>
        </div>

        <form action="" method="POST">
        <?php wp_nonce_field('aiowpsec-enable-additional-firewall-nonce'); ?>            

        <div class="postbox">
        <h3><label for="title"><?php _e('Listing of Directory Contents', 'aiowpsecurity'); ?></label></h3>
        <div class="inside">
        <?php
        //Display security info badge
        global $aiowps_feature_mgr;
        $aiowps_feature_mgr->output_feature_details_badge("firewall-disable-index-views");
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Disable Index Views', 'aiowpsecurity')?>:</th>                
                <td>
                <input name="aiowps_disable_index_views" type="checkbox"<?php if($aio_wp_security->configs->get_value('aiowps_disable_index_views')=='1') echo ' checked="checked"'; ?> value="1"/>
                <span class="description"><?php _e('Check this if you want to disable directory and file listing.', 'aiowpsecurity'); ?></span>
                <span class="aiowps_more_info_anchor"><span class="aiowps_more_info_toggle_char">+</span><span class="aiowps_more_info_toggle_text"><?php _e('More Info', 'aiowpsecurity'); ?></span></span>
                <div class="aiowps_more_info_body">
                    <p class="description">
                        <?php 
                        _e('By default, an Apache server will allow the listing of the contents of a directory if it doesn\'t contain an index.php file.', 'aiowpsecurity');
                        echo '<br />';
                        _e('This feature will prevent the listing of contents for all directories.', 'aiowpsecurity');
                        echo '<br />';
                        _e('NOTE: In order for this feature to work "AllowOverride" must be enabled in your httpd.conf file. Ask your hosting provider to check this if you don\'t have access to httpd.conf', 'aiowpsecurity');
                        ?>
                    </p>
                </div>
                </td>
            </tr>
        </table>
        </div></div>
        <div class="postbox">
        <h3><label for="title"><?php _e('Trace and Track', 'aiowpsecurity'); ?></label></h3>
        <div class="inside">
        <?php
        //Display security info badge
        global $aiowps_feature_mgr;
        $aiowps_feature_mgr->output_feature_details_badge("firewall-disable-trace-track");
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Disable Trace and Track', 'aiowpsecurity')?>:</th>                
                <td>
                <input name="aiowps_disable_trace_and_track" type="checkbox"<?php if($aio_wp_security->configs->get_value('aiowps_disable_trace_and_track')=='1') echo ' checked="checked"'; ?> value="1"/>
                <span class="description"><?php _e('Check this if you want to disable trace and track.', 'aiowpsecurity'); ?></span>
                <span class="aiowps_more_info_anchor"><span class="aiowps_more_info_toggle_char">+</span><span class="aiowps_more_info_toggle_text"><?php _e('More Info', 'aiowpsecurity'); ?></span></span>
                <div class="aiowps_more_info_body">
                    <p class="description">
                        <?php 
                        _e('HTTP Trace attack (XST) can be used to return header requests and grab cookies and other information.', 'aiowpsecurity');
                        echo '<br />';
                        _e('This hacking technique is usually used together with cross site scripting attacks (XSS).', 'aiowpsecurity');
                        echo '<br />';
                        _e('Disabling trace and track on your site will help prevent HTTP Trace attacks.', 'aiowpsecurity');
                        ?>
                    </p>
                </div>
                </td>
            </tr>
        </table>
        </div></div>
        <div class="postbox">
        <h3><label for="title"><?php _e('Proxy Comment Posting', 'aiowpsecurity'); ?></label></h3>
        <div class="inside">
        <?php
        //Display security info badge
        global $aiowps_feature_mgr;
        $aiowps_feature_mgr->output_feature_details_badge("firewall-forbid-proxy-comments");
        ?>
            
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Forbid Proxy Comment Posting', 'aiowpsecurity')?>:</th>                
                <td>
                <input name="aiowps_forbid_proxy_comments" type="checkbox"<?php if($aio_wp_security->configs->get_value('aiowps_forbid_proxy_comments')=='1') echo ' checked="checked"'; ?> value="1"/>
                <span class="description"><?php _e('Check this if you want to forbid proxy comment posting.', 'aiowpsecurity'); ?></span>
                <span class="aiowps_more_info_anchor"><span class="aiowps_more_info_toggle_char">+</span><span class="aiowps_more_info_toggle_text"><?php _e('More Info', 'aiowpsecurity'); ?></span></span>
                <div class="aiowps_more_info_body">
                    <p class="description">
                        <?php 
                        _e('This setting will deny any requests that use a proxy server when posting comments.', 'aiowpsecurity');
                        echo '<br />'.__('By forbidding proxy comments you are in effect eliminating some SPAM and other proxy requests.', 'aiowpsecurity');
                        ?>
                    </p>
                </div>
                </td>
            </tr>            
        </table>
        </div></div>
        <div class="postbox">
        <h3><label for="title"><?php _e('Bad Query Strings', 'aiowpsecurity'); ?></label></h3>
        <div class="inside">
        <?php
        //Display security info badge
        global $aiowps_feature_mgr;
        $aiowps_feature_mgr->output_feature_details_badge("firewall-deny-bad-queries");
        ?>
            
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Deny Bad Query Strings', 'aiowpsecurity')?>:</th>                
                <td>
                <input name="aiowps_deny_bad_query_strings" type="checkbox"<?php if($aio_wp_security->configs->get_value('aiowps_deny_bad_query_strings')=='1') echo ' checked="checked"'; ?> value="1"/>
                <span class="description"><?php _e('This will help protect you against malicious queries via XSS.', 'aiowpsecurity'); ?></span>
                <span class="aiowps_more_info_anchor"><span class="aiowps_more_info_toggle_char">+</span><span class="aiowps_more_info_toggle_text"><?php _e('More Info', 'aiowpsecurity'); ?></span></span>
                <div class="aiowps_more_info_body">
                    <p class="description">
                        <?php 
                        _e('This feature will write rules in your .htaccess file to prevent malicious string attacks on your site using XSS.', 'aiowpsecurity');
                        echo '<br />'.__('NOTE: Some of these strings might be used for plugins or themes and hence this might break some functionality.', 'aiowpsecurity');
                        echo '<br /><strong>'.__('You are therefore strongly advised to take a backup of your active .htaccess file before applying this feature.', 'aiowpsecurity').'<strong>';
                        ?>
                    </p>
                </div>
                </td>
            </tr>            
        </table>
        </div></div>
        <div class="postbox">
        <h3><label for="title"><?php _e('Advanced Character String Filter', 'aiowpsecurity'); ?></label></h3>
        <div class="inside">
        <?php
        //Display security info badge
        global $aiowps_feature_mgr;
        $aiowps_feature_mgr->output_feature_details_badge("firewall-advanced-character-string-filter");
        ?>
            
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Enable Advanced Character String Filter', 'aiowpsecurity')?>:</th>                
                <td>
                <input name="aiowps_advanced_char_string_filter" type="checkbox"<?php if($aio_wp_security->configs->get_value('aiowps_advanced_char_string_filter')=='1') echo ' checked="checked"'; ?> value="1"/>
                <span class="description"><?php _e('This will block bad character matches from XSS.', 'aiowpsecurity'); ?></span>
                <span class="aiowps_more_info_anchor"><span class="aiowps_more_info_toggle_char">+</span><span class="aiowps_more_info_toggle_text"><?php _e('More Info', 'aiowpsecurity'); ?></span></span>
                <div class="aiowps_more_info_body">
                    <p class="description">
                        <?php 
                        _e('This is an advanced character string filter to prevent malicious string attacks on your site coming from Cross Site Scripting (XSS).', 'aiowpsecurity');
                        echo '<br />'.__('This setting matches for common malicious string patterns and exploits and will produce a 403 error for the hacker attempting the query.', 'aiowpsecurity');
                        echo '<br />'.__('NOTE: Some strings for this setting might break some functionality.', 'aiowpsecurity');
                        echo '<br /><strong>'.__('You are therefore strongly advised to take a backup of your active .htaccess file before applying this feature.', 'aiowpsecurity').'<strong>';
                        ?>
                    </p>
                </div>
                </td>
            </tr>            
        </table>
        </div></div>
        <input type="submit" name="aiowps_apply_additional_firewall_settings" value="<?php _e('Save Additional Firewall Settings', 'aiowpsecurity')?>" class="button-primary" />
        </form>
        <?php
    }
    
    function render_tab3()
    {
        global $aio_wp_security;
        if(isset($_POST['aiowps_apply_5g_firewall_settings']))//Do form submission tasks
        {
            $nonce=$_REQUEST['_wpnonce'];
            if (!wp_verify_nonce($nonce, 'aiowpsec-enable-5g-firewall-nonce'))
            {
                $aio_wp_security->debug_logger->log_debug("Nonce check failed on enable 5G firewall settings!",4);
                die("Nonce check failed on enable 5G firewall settings!");
            }

            //Save settings
            if(isset($_POST['aiowps_enable_5g_firewall']))
            {
                $aio_wp_security->configs->set_value('aiowps_enable_5g_firewall','1');
            } 
            else
            {
                $aio_wp_security->configs->set_value('aiowps_enable_5g_firewall','');
            }

            //Commit the config settings
            $aio_wp_security->configs->save_config();

            //Now let's write the applicable rules to the .htaccess file
            $res = AIOWPSecurity_Utility_Htaccess::write_to_htaccess();

            if ($res)
            {
                $this->show_msg_updated(__('You have successfully saved the 5G Firewall Protection configuration', 'aiowpsecurity'));
            }
            else if($res == -1)
            {
                $this->show_msg_error(__('Could not write to the .htaccess file. Please check the file permissions.', 'aiowpsecurity'));
            }
        }

        ?>
        <h2><?php _e('Firewall Settings', 'aiowpsecurity')?></h2>
        <div class="aio_blue_box">
            <?php
            $backup_tab_link = '<a href="admin.php?page='.AIOWPSEC_SETTINGS_MENU_SLUG.'&tab=tab2" target="_blank">backup</a>';
            $info_msg = '<p>'.sprintf( __('This feature allows you to activate the 5G  firewall security protection rules designed and produced by %s.', 'aiowpsecurity'), '<a href="http://perishablepress.com/5g-blacklist-2013/" target="_blank">Perishable Press</a>').'</p>';
            $info_msg .= '<p>'.__('The 5G Blacklist is a simple, flexible blacklist that helps reduce the number of malicious URL requests that hit your website.', 'aiowpsecurity').'</p>';
            $info_msg .= '<p>'.__('The added advantage of applying the 5G firewall to your site is that it has been tested and confirmed by the people at PerishablePress.com to be an optimal and least disruptive set of .htaccess security rules for general WP sites running on an Apache server or similar.', 'aiowpsecurity').'</p>';
            $info_msg .= '<p>'.sprintf( __('Therefore the 5G firewall rules should not have any impact on your site\'s general functionality but if you wish you can take a %s of your .htaccess file before proceeding.', 'aiowpsecurity'), $backup_tab_link).'</p>';
            echo $info_msg;
            ?>
        </div>

        <div class="postbox">
        <h3><label for="title"><?php _e('5G Blacklist/Firewall Settings', 'aiowpsecurity'); ?></label></h3>
        <div class="inside">
        <?php
        //Display security info badge
        global $aiowps_feature_mgr;
        $aiowps_feature_mgr->output_feature_details_badge("firewall-enable-5g-blacklist");
        ?>
            
        <form action="" method="POST">
        <?php wp_nonce_field('aiowpsec-enable-5g-firewall-nonce'); ?>            
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Enable 5G Firewall Protection', 'aiowpsecurity')?>:</th>                
                <td>
                <input name="aiowps_enable_5g_firewall" type="checkbox"<?php if($aio_wp_security->configs->get_value('aiowps_enable_5g_firewall')=='1') echo ' checked="checked"'; ?> value="1"/>
                <span class="description"><?php _e('Check this if you want to apply the 5G Blacklist firewall protection from perishablepress.com to your site.', 'aiowpsecurity'); ?></span>
                <span class="aiowps_more_info_anchor"><span class="aiowps_more_info_toggle_char">+</span><span class="aiowps_more_info_toggle_text"><?php _e('More Info', 'aiowpsecurity'); ?></span></span>
                <div class="aiowps_more_info_body">
                        <?php 
                        echo '<p class="description">'.__('This setting will implement the 5G security firewall protection mechanisms on your site which include the following things:', 'aiowpsecurity').'</p>';
                        echo '<p class="description">'.__('1) Block forbidden characters commonly used in exploitative attacks.', 'aiowpsecurity').'</p>';
                        echo '<p class="description">'.__('2) Block malicious encoded URL characters such as the ".css(" string.', 'aiowpsecurity').'</p>';
                        echo '<p class="description">'.__('3) Guard against the common patterns and specific exploits in the root portion of targeted URLs.', 'aiowpsecurity').'</p>';
                        echo '<p class="description">'.__('4) Stop attackers from manipulating query strings by disallowing illicit characters.', 'aiowpsecurity').'</p>';
                        echo '<p class="description">'.__('....and much more.', 'aiowpsecurity').'</p>';
                        ?>
                </div>
                </td>
            </tr>            
        </table>
        <input type="submit" name="aiowps_apply_5g_firewall_settings" value="<?php _e('Save 5G Firewall Settings', 'aiowpsecurity')?>" class="button-primary" />
        </form>
        </div></div>
        <?php
    }
        
} //end class