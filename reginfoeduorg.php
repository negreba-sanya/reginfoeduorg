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
        echo '<p>Родительская страница: ' . get_page_by_title('Сведения об образовательной организации')->post_title . '</p>';
        echo '<p>Выберите страницы, которые будут содержать информацию о вашей организации:</p>';
    }


    function my_plugin_add_sections() {
        $parent_page_id = get_page_by_title('Сведения об образовательной организации')->ID;

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

        foreach ($pages as $page) {
            $child_page_id = get_page_by_title($page, OBJECT, 'page')->ID;
            $selected = get_option('reginfoeduorg_options');
            $selected = isset($selected[$page]) && $selected[$page] === '1';

            if ($child_page_id) {
                if (!$selected) {
                    wp_delete_post($child_page_id, true);
                }
            } else {
                if ($selected) {
                    wp_insert_post(array(
                        'post_title' => $page,
                        'post_content' => '',
                        'post_type' => 'page',
                        'post_parent' => $parent_page_id,
                        'post_status' => 'publish',
                    ));
                }
            }
        }
    }
    
    
    function my_plugin_settings_menu() 
    {
        add_options_page('RegInfoEduOrg Settings', 'RegInfoEduOrg', 'manage_options', 'reginfoeduorg_settings', array($this, 'my_plugin_settings_page'));
    }

    function my_plugin_settings_init() 
    {
        add_settings_field('reginfoeduorg_pages', 'Выберите страницы', array($this, 'print_sections_input'), 'reginfoeduorg', 'reginfoeduorg_sections');
        register_setting('reginfoeduorg_options', 'reginfoeduorg_options', array($this, 'my_plugin_options_validate'));
    }

    function my_plugin_options_validate($input) 
    {
        $output = array();
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

        foreach ($pages as $page) {
            $output[$page] = isset($input[$page]) ? '1' : '0';
        }

        return $output;
    }


    function print_sections_input($options) 
    {
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

        foreach ($pages as $page) {
            $checked = isset($options[$page]) ? 'checked' : '';
            echo '<p><label><input type="checkbox" name="reginfoeduorg_options[]" value="' . $page . '" ' . $checked . ' /> ' . $page . '</label></p>';
        }
    }



    function my_plugin_settings_page() 
    {
        $options = get_option( 'reginfoeduorg_options' );
        ?>
        <div class="wrap">
            <h1>Настройки RegInfoEduOrg</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'reginfoeduorg_options' );
                do_settings_sections( 'reginfoeduorg' );
                ?>
                <table class="form-table">
                    <?php do_settings_fields('reginfoeduorg', 'reginfoeduorg_sections'); ?>
                </table>
                <?php submit_button(); ?>
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
