<?php
/*
Plugin Name: RegInfoEduOrg
Description: Обеспечение публикации решламентированных сведений на сайтах образовательных организаций.
Version: 1.0
Author: Негреба Александр
License: GPLv2 or later
Text Domain: reginfoeduorg
*/

if(!defined('ABSPATH')){
    die;
}

class RegInfoEduOrg
{
    function __construct(){
        add_action('init', [$this, 'custom_post_type']);
    }

    function custom_post_type(){
        register_post_type('reginfo',
            [
                'public' => true,
                'label' => esc_html__('RegInfo', 'reginfoeduorg'),
            ]
        ); 
    }
}

if(class_exists('RegInfoEduOrg')){
    new RegInfoEduOrg();
}
