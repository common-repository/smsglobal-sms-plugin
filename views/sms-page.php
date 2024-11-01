<div>
    <h3 class="page-title"><?php _e( 'Send Message' ) ?></h3>
</div>
<?php
if(isset($_GET['status'])){ ?>
    <div class="notice notice-info is-dismissible">
        <p><strong><?php echo str_replace('-', ' ',$_GET['status']); ?></strong></p>
        <button type="button" class="notice-dismiss">
            <span class="screen-reader-text">Dismiss this notice.</span>
        </button>
    </div>
<?php }
?>
<div class="smsglobal_wrapper">
    <form action="/wp-admin/admin.php?page=smsglobal_sms&option=send" method="post" id="send-form">
        <table class="form-table">
            <tr valign="top">
                <th scrope="row"><?php _e( 'From:' ) ?>
                    <span class="tooltip" data-title="Enter SenderID"><span
                                class="dashicons dashicons-info"></span></span>
                </th>
                <td style="vertical-align: top;">
                    <input type="text" name="senderid" id="senderid" value="" data-id="senderid">
                </td>
            </tr>
            <tr valign="top">
                <th scrope="row"><?php _e( 'To:' ) ?>
                    <span class="tooltip" data-title="Enter Recipients(comma separted if many)"><span
                                class="dashicons dashicons-info"></span></span>
                </th>
                <td style="vertical-align: top;">
                    <input type="text" name="recipients" id="recipients" value="" data-id="senderid">
                </td>
            </tr>
            <tr valign="top">
                <th scrope="row"><?php _e( 'Message:' ) ?>
                    <span class="tooltip" data-title="Enter Recipients(comma separted if many)"><span
                                class="dashicons dashicons-info"></span></span>
                </th>
                <td style="vertical-align: top;">
                    <textarea rows="10" cols="50" name="message" id="message" value="" data-id="message"></textarea>
                </td>
            </tr>
            <tr valign="top">
                <th scrope="row"><?php _e( 'Schedule Message:' ) ?>
                    <span class="tooltip" data-title="Schedule"><span class="dashicons dashicons-info"></span></span>
                </th>
                <td style="vertical-align: top;">
                    <input type="text" name="dateScheduled" id="schedule-date-scheduled"
                           class="input-scheduled form-control"
                           placeholder="DD/MM/YYYY">

                    <input type="text" name="timeScheduled" id="schedule-time-scheduled"
                           class="input-scheduled"
                           placeholder="hh:mm AM/PM">
                </td>
            </tr>
            <tr>
                <td colspan="2" valign="top" align="center">
                    <button id="send-button" class="button button-primary"
                            type="submit"><?php esc_html_e( 'Send Message' ) ?></button>
                </td>
            </tr>
        </table>
    </form>
</div>
