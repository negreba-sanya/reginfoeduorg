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
        add_action('admin_menu', array($this, 'add_menu_pages'));
    }

    function my_plugin_add_sections() {
        $page_title = 'Сведения об образовательной организации';
        $page_content = '';
        $parent_id = 0;
        $parent_page = get_page_by_title($page_title);
    
        // Check if the parent page exists, if not create one
        if (empty($parent_page)) {
            $page_args = array(
                'post_title' => $page_title,
                'post_content' => $page_content,
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_parent' => $parent_id
            );
        
            $parent_id = wp_insert_post($page_args);
        } else {
            $parent_id = $parent_page->ID;
        }
    
        // Check if each child page exists, if not create one
        $child_sections = array(
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
            'Вакантные места для приема (перевода)'
        );
    
        foreach ($child_sections as $child_section) {
            $child_page_title = $child_section;
            $child_page_content = '';
            $child_page_args = array(
                'post_title' => $child_page_title,
                'post_content' => $child_page_content,
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_parent' => $parent_id
            );
        
            $child_page = get_page_by_title($child_page_title);
        
            if (empty($child_page)) {
                wp_insert_post($child_page_args);
            }
        }
    }
    
    
    function my_plugin_settings_menu() 
    {
        add_options_page('RegInfoEduOrg Settings', 'RegInfoEduOrg', 'manage_options', 'reginfoeduorg_settings', array($this, 'my_plugin_settings_page'));
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
