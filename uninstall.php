<?php

if(!defined('WP_UNINSTALL_PLUGIN')){
    die;
}

global $wpdb;
$wpdb -> query("DELETE FROM {$wpdb->posts} WHERE post_type IN ('reginfo');");