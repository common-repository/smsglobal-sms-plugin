<div>
    <h3 class="page-title"><?php _e('Report') ?></h3>
</div>
<?php if (!empty($error)) : ?>
    <div class="notice notice-error is-dismissible">
        <p>
            <?php esc_html_e($error); ?>
        </p>
    </div>
<?php else: ?>

    <!-- messages report table-->
    <div id="table-wrapper">
        <table id="report-table">
            <tr>
                <th>Date</th>
                <th>Origin</th>
                <th>Destination</th>
                <th>Message</th>
                <th>Status</th>
            </tr>
            <?php foreach ($messages as $message) : ?>
                <tr>
                    <td>
                        <?php
                        // Convert to local time
                        $time = new DateTime($message->dateTime);
                        $formattedDate = $time->format('M d, Y, h:i a');
                        esc_html_e($formattedDate)
                        ?>
                    </td>
                    <td><?php esc_html_e($message->origin) ?></td>
                    <td><?php esc_html_e($message->destination) ?></td>
                    <td><?php esc_html_e($message->message) ?></td>
                    <?php
                    $status = $message->status;
                    if ($status == 'Error 1028') {
                        $status = 'Rejected';
                    } else {
                        $status = ucfirst($status);
                    }
                    ?>
                    <td class="<?php if ($status == 'Delivered') {
                        esc_attr_e('message-delivered');
                    } ?>"><?php esc_html_e($status) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Pagination-->
    <div class="pagination">
        <?php if ($offset > 1): ?>
            <li><a href="/wp-admin/admin.php?page=smsglobal-sms-log&offset=1"><?php esc_html_e('« First') ?></a></li>
            <li>
                <a href="/wp-admin/admin.php?page=smsglobal-sms-log&offset=<?php esc_attr_e($offset - 50) ?>"><?php esc_html_e('< Previous') ?></a>
            </li>
        <?php else: ?>
            <li><a class="disabled-link"><?php esc_html_e('« First') ?></a></li>
            <li><a class="disabled-link"><?php esc_html_e('< Previous') ?></a></li>
        <?php endif; ?>

        <?php for ($i = 0; $i <= $pageNumber; $i++) : ?>
            <?php if ($i * 50 + 1 == $offset): ?>
                <li><a class="disabled-link"><?php esc_html_e($i + 1) ?></a></li>
            <?php else : ?>
                <li>
                    <a href="/wp-admin/admin.php?page=smsglobal-sms-log&offset=<?php esc_attr_e($i * 50 + 1) ?>"><?php esc_html_e($i + 1) ?></a>
                </li>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($offset < $pageNumber * 50 + 1): ?>
            <li>
                <a href="/wp-admin/admin.php?page=smsglobal-sms-log&offset=<?php esc_attr_e($offset + 50) ?>"><?php esc_html_e('Next >') ?></a>
            </li>
            <li>
                <a href="/wp-admin/admin.php?page=smsglobal-sms-log&offset=<?php esc_attr_e($pageNumber * 50 + 1) ?>"><?php esc_html_e('Last »') ?></a>
            </li>
        <?php else: ?>
            <li><a class="disabled-link"><?php esc_html_e('Next >') ?></a></li>
            <li><a class="disabled-link"><?php esc_html_e('Last »') ?></a></li>
        <?php endif; ?>
    </div>

<?php endif; ?>
