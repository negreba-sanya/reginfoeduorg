<?php
/*
Plugin Name: RegInfoEduOrg
Description: Обеспечение публикации решламентированных сведений на сайтах образовательных организаций.
Version: 1.0
Author: Негреба Александр
License: GPLv2 or later
Text Domain: reginfoeduorg
*/

if (!defined('ABSPATH')) {
    die;
}

class RegInfoEduOrg
{
    function __construct() 
    {
        add_action('init', array($this, 'my_plugin_add_sections'));
        add_action('admin_menu', array($this, 'my_plugin_settings_menu'));
        add_action('admin_init', array($this, 'my_plugin_settings_init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    function print_sections_info($section) 
    {
        echo 'Выберите страницы, которые будут содержать информацию о вашей организации:';
    }

    function my_plugin_add_sections() 
    {

        // Проверяем, существует ли страница "Сведения об образовательной организации"
        $parent_page_id = get_page_by_title( 'Сведения об образовательной организации' );

        // Если страницы не существует, создаем новую страницу
        if ( ! $parent_page_id ) 
        {
            $page_data = array(
                'post_title'    => 'Сведения об образовательной организации',
                'post_content'  => '',
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_type'     => 'page',
            );
            $parent_page_id = wp_insert_post( $page_data );
        }

        // Создаем подразделы на странице "Сведения об образовательной организации"
        $pages = array(
            'Основные сведения',
            'Структура и органы управления образовательной организацией',
            'Документы',
            'Образование',
            'Образовательные стандарты',
            'Руководство. Педагогический (научно-педагогический) состав',
            'Материально-техническое обеспечение и оснащенность образовательного процесса',
            'Стипендии и иные виды материальной поддержки',
            'Платные образовательные услуги',
            'Финансово-хозяйственная деятельность',
            'Вакантные места для приема (перевода)',
        );

        foreach ( $pages as $page ) 
        {
            // проверяем, есть ли на сайте необходимые разделы и если их нет, добавляем
            if ( ! get_page_by_title( $page, 'OBJECT', 'page' ) ) 
            {
                $page_id = wp_insert_post( array(
                    'post_title' => $page,
                    'post_content' => '',
                    'post_type' => 'page',
                    'post_parent' => $parent_page_id,
                    'post_status' => 'publish'
                ) );
            }
        }  
    }          

    function print_sections_input() 
    {
        $options = get_option( 'reginfoeduorg_options' );
        $pages = array(
            'Основные сведения',
            'Структура и органы управления образовательной организацией',
            'Документы',
            'Образование',
            'Образовательные стандарты',
            'Руководство. Педагогический (научно-педагогический) состав',
            'Материально-техническое обеспечение и оснащенность образовательного процесса',
            'Стипендии и иные виды материальной поддержки',
            'Платные образовательные услуги',
            'Финансово-хозяйственная деятельность',
            'Вакантные места для приема (перевода)',
        );
        foreach ( $pages as $page ) {
            $checked = '';
            if ( isset( $options[ $page ] ) ) {
                $checked = 'checked';
            }
            echo '<label><input type="checkbox" name="reginfoeduorg_options[' . $page . ']" value="1" ' . $checked . ' /> ' . $page . '</label><br />';
        }
    }

    function my_plugin_settings_menu() 
    {
        add_options_page('RegInfoEduOrg Settings', 'RegInfoEduOrg', 'manage_options', 'reginfoeduorg_settings', array($this, 'my_plugin_settings_page'));
    }

    function my_plugin_settings_init() 
    {
        add_settings_section( 'reginfoeduorg_sections', 'Настройки RegInfoEduOrg', array($this, 'print_sections_info'), 'reginfoeduorg' );
        add_settings_field( 'reginfoeduorg_pages', 'Выберите страницы', array($this, 'print_sections_input'), 'reginfoeduorg', 'reginfoeduorg_sections' );
        register_setting( 'reginfoeduorg_options', 'reginfoeduorg_options', array($this,'my_plugin_options_validate') );
    }

    function my_plugin_options_validate( $input ) {
        $parent_page_id = get_page_by_title( 'Сведения об образовательной организации' );

        // Перед сохранением проверяем, есть ли на сайте страницы с нужными именами
        foreach ( $input as $key => $value ) {
            if ( get_page_by_title( $key, 'OBJECT', 'page' ) ) {
                $page = get_page_by_title( $key, 'OBJECT', 'page' );
                if ($value) {
                    $my_post = array(
                      'ID'           => $page->ID,
                      'post_content' => '',
                      'post_title'   => $key,
                      'post_status'  => 'publish',
                      'post_type'    => 'page',
                      'post_parent'  => $parent_page_id
                    );
                    wp_update_post( $my_post );
                } else {
                    wp_delete_post( $page->ID );
                }
            } elseif ($value) {
                $my_post = array(
                  'post_title'   => $key,
                  'post_content' => '',
                  'post_status'  => 'publish',
                  'post_author'  => 1,
                  'post_type'    => 'page',
                  'post_parent'  => $parent_page_id
                );
                $page_id = wp_insert_post( $my_post );
            }
        }
        return $input;
    }

    function my_plugin_settings_page() 
    {
        ?>
        <div class="wrap">
            <h1>Настройки RegInfoEduOrg</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'reginfoeduorg_options' );
                do_settings_sections( 'reginfoeduorg' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
      
    function add_admin_menu() 
    {
        add_menu_page(
            'RegInfoEduOrg',
            'RegInfoEduOrg',
            'manage_options',
            'reginfoeduorg',
            array( $this, 'my_plugin_settings_page' )
        );
    }
}
 

if(class_exists('RegInfoEduOrg'))
{
    new RegInfoEduOrg();
}
