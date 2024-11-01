<nav class="nav-tab-wrapper">
    <a onclick="smsglobal_change_nav(this, 'smsglobal_nav_global_box')" class="nav-tab nav-tab-active">
		<?php echo _e( 'General', SMSGlobalConstants::TEXT_DOMAIN ); ?>
    </a>
	<?php
	if ( $hasWoocommerce || $hasWPmembers || $hasUltimate || $hasWPAM ) {
		?>
        <a onclick="smsglobal_change_nav(this, 'smsglobal_nav_css_box')" class="nav-tab">
			<?php echo _e( 'Customer Templates', SMSGlobalConstants::TEXT_DOMAIN ); ?>
        </a>
		<?php
	}

	if ( $hasWoocommerce ) { ?>
    <a onclick="smsglobal_change_nav(this, 'smsglobal_nav_admintemplates_box')" class="nav-tab">
        <?php echo _e( 'Admin Templates', SMSGlobalConstants::TEXT_DOMAIN ); ?>
    </a>
    <a onclick="smsglobal_change_nav(this, 'smsglobal_nav_callbacks_box')" class="nav-tab">
		<?php echo _e( 'Options', SMSGlobalConstants::TEXT_DOMAIN ); ?>
    </a>
    <?php } ?>
    <a onclick="smsglobal_change_nav(this, 'smsglobal_nav_support_box')" class="nav-tab">
		<?php echo _e( 'Support', SMSGlobalConstants::TEXT_DOMAIN ); ?>
    </a>
</nav>