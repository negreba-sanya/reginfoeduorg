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
        add_action('admin_menu', array($this, 'add_menu_pages'));
    }

    function print_sections_info($section) 
    {
        echo 'Выберите страницы, которые будут содержать информацию о вашей организации:';
    }

    function my_plugin_add_sections() 
    {
        // Check if parent page exists and create it if it doesn't
        $parent_page_id = get_page_by_title('Сведения об образовательной организации');
        if (!$parent_page_id) {
            $page_data = array(
                'post_title'    => 'Сведения об образовательной организации',
                'post_content'  => '',
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_type'     => 'page',
            );
            $parent_page_id = wp_insert_post($page_data);
        }

        // Define the child pages to create or delete
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

        // Loop through each child page
        foreach ($pages as $page) {
            $child_page_id = get_page_by_title($page, OBJECT, 'page');

            // Create the child page if it doesn't exist and the checkbox is checked
            if (!$child_page_id && isset($_POST['reginfoeduorg_options'][$page]) && $_POST['reginfoeduorg_options'][$page] == '1') {
                $child_page_id = wp_insert_post(array(
                    'post_title' => $page,
                    'post_content' => '',
                    'post_type' => 'page',
                    'post_parent' => $parent_page_id,
                    'post_status' => 'publish',
                ));
            }

            // Delete the child page if it exists and the checkbox is unchecked
            if ($child_page_id && (!isset($_POST['reginfoeduorg_options'][$page]) || $_POST['reginfoeduorg_options'][$page] != '1')) {
                wp_delete_post($child_page_id, true);
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
        $parent_page_id = get_page_by_title( 'Сведения об образовательной организации' );
        $existing_pages = get_pages( array( 'parent' => $parent_page_id ) );
        foreach ( $existing_pages as $page ) {
            if ( ! isset( $options[ $page->post_title ] ) ) {
                wp_delete_post( $page->ID, true );
            }
        }
        foreach ( $pages as $page ) {
            if ( isset( $options[ $page ] ) ) {
                if ( ! get_page_by_title( $page, 'OBJECT', 'page' ) ) {
                    $page_id = wp_insert_post( array(
                        'post_title' => $page,
                        'post_content' => '',
                        'post_type' => 'page',
                        'post_parent' => $parent_page_id,
                        'post_status' => 'publish'
                    ) );
                }
            } else {
                $page_id = get_page_by_title( $page, 'OBJECT', 'page' );
                if ( $page_id ) {
                    wp_delete_post( $page_id->ID, true );
                }
            }
        }
        $existing_pages = get_pages( array( 'parent' => $parent_page_id ) );
        foreach ( $existing_pages as $page ) {
            $checked = '';
            if ( isset( $options[ $page->post_title ] ) ) {
                $checked = 'checked';
            }
            echo '<label><input type="checkbox" name="reginfoeduorg_options[' . $page->post_title . ']" value="1" ' . $checked . ' /> ' . $page->post_title . '</label><br />';
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

    function my_plugin_options_validate( $input ) 
    {
        // Создаем родительскую страницу, если её еще нет
        $parent_page_id = get_page_by_title( 'Сведения об образовательной организации' );
        if ( ! $parent_page_id ) {
            $page_data = array(
                'post_title'    => 'Сведения об образовательной организации',
                'post_content'  => '',
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_type'     => 'page',
            );
            $parent_page_id = wp_insert_post( $page_data );
        }
    
        // Перед сохранением проверяем, есть ли на сайте страницы с нужными именами
        foreach ( $input as $key => $value ) {
            if ( $key == 'reginfoeduorg_pages' ) {
                continue;
            }
            if ( get_page_by_title( $key, 'OBJECT', 'page' ) ) {
                if ( $value == false ) {
                    // Если страница существует и checkbox не выбран, удаляем страницу
                    wp_delete_post( get_page_by_title( $key, 'OBJECT', 'page' )->ID, true );
                } else {
                    // Если страница существует и checkbox выбран, обновляем страницу
                    $page = get_page_by_title( $key, 'OBJECT', 'page' );
                    $my_post = array(
                        'ID'           => $page->ID,
                        'post_content' => '',
                        'post_title'   => $key,
                        'post_status'  => 'publish',
                        'post_type'    => 'page',
                        'post_parent'  => $parent_page_id // добавляем родительскую страницу
                    );
                    wp_update_post( $my_post );
                }
            } else {
                if ( $value == true ) {
                    // Если страница не существует и checkbox выбран, создаем страницу
                    $my_post = array(
                        'post_title'   => $key,
                        'post_content' => '',
                        'post_status'  => 'publish',
                        'post_author'  => 1,
                        'post_type'    => 'page',
                        'post_parent'  => $parent_page_id // добавляем родительскую страницу
                    );
                    $page_id = wp_insert_post( $my_post );
                }
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
      
    function add_menu_pages() 
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
