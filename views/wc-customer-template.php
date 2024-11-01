<!-- accordion -->
<div class="cvt-accordion">
    <div class="accordion-section">

		<?php
		foreach ( $order_statuses as $ks => $vs ) {
			$prefix = 'wc-';
			$vs     = $ks;
			if ( substr( $vs, 0, strlen( $prefix ) ) == $prefix ) {
				$vs = substr( $vs, strlen( $prefix ) );
			}
			$current_val = ( is_array( $smsglobal_notification_status ) && array_key_exists( $vs, $smsglobal_notification_status ) ) ? $smsglobal_notification_status[ $vs ] : $vs;


			?>
            <a class="cvt-accordion-body-title" href="javascript:void(0)"
               data-href="#accordion_cust_<?php echo $ks; ?>"><input type="checkbox"
                                                                     name="smsglobal_general[order_status][<?php echo $vs; ?>]"
                                                                     id="smsglobal_general[order_status][<?php echo $vs; ?>]"
                                                                     class="notify_box" <?php echo( ( $current_val == $vs ) ? "checked='checked'" : '' ); ?>
                                                                     value="<?php echo $vs; ?>"/><label><?php _e( 'When Order is ' . ucwords( str_replace( '-', ' ', $vs ) ), SMSGlobalConstants::TEXT_DOMAIN ) ?></label>
                <span class="expand_btn"></span>
            </a>
            <div id="accordion_cust_<?php echo $ks; ?>" class="cvt-accordion-body-content">
                <table class="form-table">
                    <tr valign="top">
                        <td>
                            <div class="smsglobal_tokens"><?php echo $getvariables; ?></div>
                            <textarea name="smsglobal_message[sms_body_<?php echo $vs; ?>]"
                                      id="smsglobal_message[sms_body_<?php
							          echo $vs; ?>]" <?php echo( ( $current_val == $vs ) ? '' : "readonly='readonly'" ); ?>><?php

								echo smsglobal_get_option( 'sms_body_' . $vs, 'smsglobal_message', defined( 'SMSGlobalMessages::DEFAULT_BUYER_SMS_' . str_replace( '-', '_', strtoupper( $vs ) ) ) ? constant( 'SMSGlobalMessages::DEFAULT_BUYER_SMS_' . str_replace( '-', '_', strtoupper( $vs ) ) ) : SMSGlobalMessages::DEFAULT_BUYER_SMS_STATUS_CHANGED ); ?></textarea>
                        </td>
                    </tr>
                </table>
            </div>
			<?php
		}
		?>
		<?php if ( $hasWoocommerce ) { ?>
            <!-- accordion --5-->
            <a class="cvt-accordion-body-title" href="javascript:void(0)" data-href="#accordion_5">
                <input type="checkbox" name="smsglobal_general[buyer_notification_notes]"
                       id="smsglobal_general[buyer_notification_notes]"
                       class="notify_box" <?php echo( ( $smsglobal_notification_notes == 'on' ) ? "checked='checked'" : '' ) ?>/><label><?php _e( 'When a new note is added to order', SMSGlobalConstants::TEXT_DOMAIN ) ?></label>
                <span class="expand_btn"></span>
            </a>
            <div id="accordion_5" class="cvt-accordion-body-content">
                <table class="form-table">
                    <tr valign="top">
                        <td>
                            <div class="smsglobal_tokens"><?php echo $getvariables; ?><a href="#" val="[note]">order
                                    note</a></div>
                            <textarea name="smsglobal_message[sms_body_new_note]"
                                      id="smsglobal_message[sms_body_new_note]"><?php echo $sms_body_new_note; ?></textarea>
                        </td>
                    </tr>
                </table>
            </div>
		<?php } ?>
        <!-- accordion --6-->


        <!--user registration-->
		<?php if ( $hasUltimate ) { ?>
            <a class="cvt-accordion-body-title" href="javascript:void(0)" data-href="#accordion_7">
                <input type="checkbox" name="smsglobal_general[registration_msg]"
                       id="smsglobal_general[registration_msg]"
                       class="notify_box" <?php echo( ( $smsglobal_notification_reg_msg == 'on' ) ? "checked='checked'" : '' ) ?>/><label><?php _e( 'When a new user is registered', SMSGlobalConstants::TEXT_DOMAIN ) ?></label>
                <span class="expand_btn"></span>
            </a>
            <div id="accordion_7" class="cvt-accordion-body-content">
                <table class="form-table">
                    <tr valign="top">
                        <td>
                            <div class="smsglobal_tokens"><a href="#" val="[username]">Username</a> | <a href="#"
                                                                                                         val="[store_name]">Store
                                    Name</a>| <a href="#" val="[email]">Email</a>| <a href="#" val="[billing_phone]">Billing
                                    Phone</a></div>
                            <textarea name="smsglobal_message[sms_body_registration_msg]"
                                      id="smsglobal_message[sms_body_registration_msg]"><?php echo $sms_body_registration_msg; ?></textarea>
                        </td>
                    </tr>
                </table>
            </div>
		<?php } ?>
        <!--/user registration-->
    </div>
</div>
<!--end accordion-->
<?php submit_button( __( 'Save Changes' ), 'primary', 'Update' ); ?>
