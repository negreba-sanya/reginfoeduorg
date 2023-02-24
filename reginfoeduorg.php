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
  function __construct() {
    add_action( 'init', array( $this, 'my_plugin_add_sections' ) );
    add_filter( 'wp_nav_menu_items', array( $this, 'my_nav_menu_items' ), 10, 2 );
  }

 function my_plugin_add_sections() {
  // проверяем, существует ли страница с заголовком "New Page"
  $page_id = get_page_by_title( 'New Page' );

  // если страницы не существует, создаем новую страницу
  if ( !$page_id ) {
    $page_data = array(
      'post_title'    => 'New Page',
      'post_content'  => 'This is my new page!',
      'post_status'   => 'publish',
      'post_author'   => 1,
      'post_type'     => 'page',
    );
    $page_id = wp_insert_post( $page_data );
  }
}

  function my_nav_menu_items( $items, $args ) {
    $page_id = get_page_by_title( 'New Page' );

    // добавляем ссылку на раздел в меню сайта, только если страницы не существует
    if ( !$page_id ) {
      return $items;
    }

    $new_item = '<li><a href="' . get_permalink( $page_id ) . '">New Page</a></li>';

    return $items . $new_item;
  }
}

if(class_exists('RegInfoEduOrg')){
    new RegInfoEduOrg();
}
