 <!-- Admin-accordion -->
<div class="cvt-accordion"><!-- cvt-accordion -->
<div class="accordion-section">
<?php
 foreach($order_statuses as $ks => $vs)
 {
	$prefix = 'wc-';
	$vs = $ks;
	if (substr($vs, 0, strlen($prefix)) == $prefix) {
		$vs = substr($vs, strlen($prefix));
	}
	 ?>
	<a class="cvt-accordion-body-title" href="javascript:void(0)" data-href="#accordion_<?php echo $ks; ?>"><input type="checkbox" name="smsglobal_general[admin_notification_<?php echo $vs; ?>]" id="smsglobal_general[admin_notification_<?php echo $vs; ?>]" class="notify_box" <?php echo ((smsglobal_get_option( 'admin_notification_'.$vs, 'smsglobal_general', 'on')=='on')?"checked='checked'":''); ?>/><label><?php _e('When Order is '.ucwords(str_replace('-', ' ', $vs )), SMSGlobalConstants::TEXT_DOMAIN ) ?></label>
	<span class="expand_btn"></span>
	</a>
	<div id="accordion_<?php echo $ks; ?>" class="cvt-accordion-body-content">
		<table class="form-table">
			<tr valign="top">
			<td><div class="smsglobal_tokens"><?php echo $getvariables; ?></div>
			<textarea name="smsglobal_message[admin_sms_body_<?php echo $vs; ?>]" id="smsglobal_message[admin_sms_body_<?php echo $vs; ?>]" <?php echo((smsglobal_get_option( 'admin_notification_'.$vs, 'smsglobal_general', 'on')=='on')?'' : "readonly='readonly'"); ?>><?php
	  echo smsglobal_get_option('admin_sms_body_'.$vs, 'smsglobal_message', defined('SMSGlobalMessages::DEFAULT_ADMIN_SMS_'.str_replace('-', '_', strtoupper($vs))) ? constant('SMSGlobalMessages::DEFAULT_ADMIN_SMS_'.str_replace('-', '_', strtoupper($vs))) : SMSGlobalMessages::DEFAULT_ADMIN_SMS_STATUS_CHANGED);

			?></textarea>
			</td>
			</tr>
		</table>
	</div>
	 <?php
 }
 ?>


 <!--user registration-->
				<?php if ($hasUltimate){?>
					<a class="cvt-accordion-body-title" href="javascript:void(0)" data-href="#accordion_7">
					<input type="checkbox" name="smsglobal_general[admin_registration_msg]" id="smsglobal_general[admin_registration_msg]" class="notify_box" <?php echo (($smsglobal_notification_reg_admin_msg=='on')?"checked='checked'":'')?>/><label><?php _e( 'When a new user is registered', SMSGlobalConstants::TEXT_DOMAIN ) ?></label>
					<span class="expand_btn"></span>
					</a>
					<div id="accordion_7" class="cvt-accordion-body-content">
						<table class="form-table">
							<tr valign="top">
							<td>
							<div class="smsglobal_tokens"><a href="#" val="[username]">Username</a> | <a href="#" val="[store_name]">Store Name</a>| <a href="#" val="[email]">Email</a>| <a href="#" val="[billing_phone]">Billing Phone</a></div>
							<textarea name="smsglobal_message[sms_body_registration_admin_msg]" id="smsglobal_message[sms_body_registration_admin_msg]"><?php echo $sms_body_registration_admin_msg; ?></textarea>
							</td>
							</tr>
						</table>
					</div>
				<?php }?>
				<!--/user registration-->
 </div>
 </div><!-- /-cvt-accordion -->
 <?php submit_button( __( 'Save Changes' ), 'primary', 'Update' ); ?>
