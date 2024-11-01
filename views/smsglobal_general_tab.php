<?php if ( !$islogged ) { ?>
    <div class="smsglobal_wrapper">
        <strong><?php _e( $smsglobal_helper, SMSGlobalConstants::TEXT_DOMAIN ); ?></strong> <br/> <br/>
        <table class="form-table">
            <tr valign="top">
                <th scrope="row"><?php _e( 'API key', SMSGlobalConstants::TEXT_DOMAIN ); ?>
                    <span class="tooltip" data-title="Enter MXT API key"><span
                                class="dashicons dashicons-info"></span></span>
                </th>
                <td style="vertical-align: top;">
                    <input type="text" name="smsglobal_gateway[smsglobal_api_key]" id="smsglobal_gateway[smsglobal_api_key]"
                           value="" data-id="smsglobal_api_key">
                    <input type="hidden" name="action" value="save_sms_alert_settings"/>
                </td>
            </tr>

            <tr valign="top">
                <th scrope="row"><?php _e( 'Secret', SMSGlobalConstants::TEXT_DOMAIN ) ?>
                    <span class="tooltip" data-title="Enter MXT API secret"><span class="dashicons dashicons-info"></span></span>
                </th>
                <td>
                    <input type="password" name="smsglobal_gateway[smsglobal_secret]"
                           id="smsglobal_gateway[smsglobal_secret]" value=""
                           data-id="smsglobal_secret" >
                </td>
            </tr>
			<?php do_action( 'login' ) ?>
        </table>
    </div>
<?php } else { ?>
    <table class="form-table">
        <tr valign="top">
            <th scrope="row"><?php _e( 'Name:', SMSGlobalConstants::TEXT_DOMAIN ); ?></th>
            <td><?php echo $smsglobal_name; ?></td>
        </tr>
        <tr valign="top">
            <th scrope="row"><?php _e( 'Account Type:', SMSGlobalConstants::TEXT_DOMAIN ); ?></th>
            <td><?php echo $smsglobal_type; ?> PAID</td>
        </tr>
        <?php if ($smsglobal_type == 'PRE'): ?>
            <tr valign="top">
                <th scrope="row"><?php _e( 'Balance:', SMSGlobalConstants::TEXT_DOMAIN ); ?></th>
                <td><?php echo round($smsglobal_balance,2); ?></td>
            </tr>
        <?php endif; ?>
        <?php if (!empty($smsglobal_currency)): ?>
            <tr valign="top">
                <th scrope="row"><?php _e( 'Currency:', SMSGlobalConstants::TEXT_DOMAIN ); ?></th>
                <td><?php echo $smsglobal_currency; ?></td>
            </tr>
        <?php endif; ?>
        <tr valign="top">
            <th scrope="row"><?php _e( 'Country:', SMSGlobalConstants::TEXT_DOMAIN ); ?></th>
            <td><?php echo $smsglobal_country; ?></td>
        </tr>
        <tr>
            <th scrope="row">
            </th>
            <td>
                <button class="button-primary"
                   onclick="logout(); return false;"><?php echo _e( 'Logout', SMSGlobalConstants::TEXT_DOMAIN ); ?></button>
            </td>
        </tr>
    </table>
<?php } ?>
