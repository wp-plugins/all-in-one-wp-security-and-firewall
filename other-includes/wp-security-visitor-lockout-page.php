<?php
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head profile="http://gmpg.org/xfn/11">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php bloginfo('name'); ?></title>

	<link rel="stylesheet" type="text/css" href="<?php echo AIO_WP_SECURITY_URL ; ?>/css/wp-security-site-lockout-page.css" />
	<?php wp_head(); ?>
</head>

<body>
<div class="aiowps-site-lockout-body">
    <div class="aiowps-site-lockout-body-content">
        <div class="aiowps-site-lockout-box">
                <div class="aiowps-site-lockout-msg">
                    <p class="aiowps-site-lockout-text"><?php _e('This site is currently not available', 'aiowpsecurity'); ?></p>
                    <p class="aiowps-site-lockout-text"><?php _e('Please try again later', 'aiowpsecurity'); ?></p>
                </div>
        </div> <!-- end .aiowps-site-lockout-box -->
    </div> <!-- end .aiowps-site-lockout-body-content -->
</div> <!-- end .aiowps-site-lockout-body -->
</body>
</html>