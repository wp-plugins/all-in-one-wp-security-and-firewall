<?php
class AIOWPSecurity_Backup
{
    var $last_backup_file_name;//Stores the name of the last backup file when execute_backup function is called
    var $last_backup_file_path;
    
    function __construct() 
    {
        add_action('aiowps_perform_scheduled_backup_tasks', array(&$this, 'aiowps_scheduled_backup_handler')); //TODO - this action never gets fired - need to investigate
    }
    
    /**
     * This function will perform a database backup
     */
    function execute_backup() 
    {
        global $wpdb, $aio_wp_security;
        
        @ini_set( 'auto_detect_line_endings', true );

        //get all of the tables
        $tables = $wpdb->get_results( 'SHOW TABLES', ARRAY_N );
        $return = '';

        //cycle through each table
        foreach($tables as $table) 
        {
            $result = $wpdb->get_results( 'SELECT * FROM `' . $table[0] . '`;', ARRAY_N );
            $num_fields = sizeof( $wpdb->get_results( 'DESCRIBE `' . $table[0] . '`;' ) );

            $return.= 'DROP TABLE IF EXISTS `' . $table[0] . '`;';
            $row2 = $wpdb->get_row( 'SHOW CREATE TABLE `' . $table[0] . '`;', ARRAY_N );
            $return.= PHP_EOL . PHP_EOL . $row2[1] . ";" . PHP_EOL . PHP_EOL;

            foreach( $result as $row ) 
            {
                $return .= 'INSERT INTO `' . $table[0] . '` VALUES(';

                for( $j=0; $j < $num_fields; $j++ ) {

                    $row[$j] = addslashes( $row[$j] );
                    $row[$j] = ereg_replace( PHP_EOL, "\n", $row[$j] );

                    if ( isset( $row[$j] ) ) { 
                            $return .= '"' . $row[$j] . '"' ; 
                    } else { 
                            $return.= '""'; 
                    }

                    if ( $j < ( $num_fields - 1 ) ) { 
                            $return .= ','; 
                    }

                }
                $return .= ");" . PHP_EOL;
            }
            $return .= PHP_EOL . PHP_EOL;
        }
        $return .= PHP_EOL . PHP_EOL;

        //save file
        $file = 'database-backup-' . current_time( 'timestamp' );
        $handle = @fopen( AIO_WP_SECURITY_BACKUPS_PATH . '/' . $file . '.sql', 'w+' );
        $fw_res = @fwrite( $handle, $return );
        if (!$fw_res)
        {
            return false;
        }
        @fclose( $handle );

        //zip the file
        if ( class_exists( 'ZipArchive' ) ) 
        {
            $zip = new ZipArchive();
            $archive = $zip->open(AIO_WP_SECURITY_BACKUPS_PATH . '/' . $file . '.zip', ZipArchive::CREATE);
            $zip->addFile(AIO_WP_SECURITY_BACKUPS_PATH . '/' . $file . '.sql', $file . '.sql' );
            $zip->close();

            //delete .sql and keep zip
            @unlink( AIO_WP_SECURITY_BACKUPS_PATH . '/' . $file . '.sql' );
            $fileext = '.zip';
        } else 
        {
            $fileext = '.sql';
        }
        $this->last_backup_file_name = $file . $fileext;//database-backup-1367644822.zip or database-backup-1367644822.sql
        $this->last_backup_file_path = AIO_WP_SECURITY_BACKUPS_PATH. '/' . $file . $fileext;

        $this->aiowps_send_backup_email(); //Send backup file via email if applicable
        $this->aiowps_delete_backup_files();
        return true;
    }
    
    function aiowps_send_backup_email()
    {
        global $aio_wp_security;
        if ( $aio_wp_security->configs->get_value('aiowps_send_backup_email_address') == '1' ) 
        {
            //Get the right email address.
            if ( is_email( $aio_wp_security->configs->get_value('aiowps_send_backup_email_address') ) ) 
            {
                    $toaddress = $aio_wp_security->configs->get_value('aiowps_send_backup_email_address');
            } else 
            {
                    $toaddress = get_site_option( 'admin_email' );
            }

            $to = $toaddress;
            $headers = 'From: ' . get_option( 'blogname' ) . ' <' . $to . '>' . PHP_EOL;
            $subject = __( 'All In One WP Security - Site Database Backup', 'aiowpsecurity' ) . ' ' . date( 'l, F jS, Y \a\\t g:i a', current_time( 'timestamp' ) );
            $attachment = array( $this->last_backup_file_path );
            $message = __( 'Attached is your latest DB backup file for site URL', 'aiowpsecurity' ) . ' ' . get_option( 'siteurl' ) . __( ' generated on', 'aiowpsecurity' ) . ' ' . date( 'l, F jS, Y \a\\t g:i a', current_time( 'timestamp' ) );

            wp_mail( $to, $subject, $message, $headers, $attachment );
        }
    }
    
    function aiowps_delete_backup_files()
    {
        global $aio_wp_security;
        if ( $aio_wp_security->configs->get_value('aiowps_backup_files_stored') > 0 ) 
        {
            $files = scandir( AIO_WP_SECURITY_BACKUPS_PATH. '/', 1 );

            $count = 0;

            foreach ( $files as $file ) 
            {
                if ( strstr( $file, 'database-backup' ) ) 
                {
                    if ( $count >= $aio_wp_security->configs->get_value('aiowps_backup_files_stored') ) 
                    {
                            @unlink( AIO_WP_SECURITY_BACKUPS_PATH. '/' . $file );
                    }
                    $count++;
                }

            }
        }
    }
    
    function aiowps_scheduled_backup_handler()
    {
        global $aio_wp_security;
        if($aio_wp_security->configs->get_value('aiowps_enable_automated_backups')=='1')
        {
            $aio_wp_security->debug_logger->log_debug_cron("DB Backup - Scheduled backup is enabled. Checking if a backup needs to be done now...");
            $current_time = strtotime(current_time('mysql'));
            $backup_frequency = $aio_wp_security->configs->get_value('aiowps_db_backup_frequency'); //Number of hours or days or months interval per backup
            $interval_setting = $aio_wp_security->configs->get_value('aiowps_db_backup_interval'); //Hours/Days/Months
            switch($interval_setting)
            {
                case '0':
                    $interval = 'hours';
                    break;
                case '1':
                    $interval = 'days';
                    break;
                case '2':
                    $interval = 'weeks';
                    break;
            }
            $last_backup_time = $aio_wp_security->configs->get_value('aiowps_last_backup_time');
            if ($last_backup_time != NULL)
            {
                $last_backup_time = strtotime($aio_wp_security->configs->get_value('aiowps_last_backup_time'));
                $next_backup_time = strtotime("+".abs($backup_frequency).$interval, $last_backup_time);
                if ($next_backup_time <= $current_time)
                {
                    //It's time to do a backup
                    $result = $this->execute_backup();
                    if ($result)
                    {
                        $aio_wp_security->configs->set_value('aiowps_last_backup_time', current_time('mysql'));
                        $aio_wp_security->configs->save_config();
                        $aio_wp_security->debug_logger->log_debug_cron("DB Backup - Scheduled backup was successfully completed.");
                    } 
                    else
                    {
                        $aio_wp_security->debug_logger->log_debug_cron("DB Backup - Scheduled backup operation failed!",4);
                    }
                }
            } 
            else
            {
                //Set the last backup time to now so it can trigger for the next scheduled period
                $aio_wp_security->configs->set_value('aiowps_last_backup_time', current_time('mysql'));
                $aio_wp_security->configs->save_config();
            }
        }
    }
}