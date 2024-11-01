<?php

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// All the options used
$optionList = ['isLoggedIn', 'isPostPaid', 'balance', 'key', 'secret', 'countryCode', 'name', 'timezone', 'currency'];

foreach ($optionList as $optionName) {
    $optionName = 'smsglobal_'.$optionName;
    delete_option($optionName);
}
