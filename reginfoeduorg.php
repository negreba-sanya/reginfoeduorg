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
        add_action('admin_init', array( $this, 'register_settings' ) );
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('the_content', array($this, 'filter_content'));
    }



    function register_settings() 
    {
        register_setting( 'reginfoeduorg', 'reginfoeduorg_options' );
        add_settings_section( 'my-section-id', false, false, 'reginfoeduorg' );
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
    
        add_submenu_page(
            'reginfoeduorg',
            'Подразделы сайта',
            'Подразделы сайта',
            'manage_options',
            'reginfoeduorg-submenu',
            array( $this, 'submenu_page' )
        );

        /*add_submenu_page(
            'reginfoeduorg',
            'Настройка содержания подразделов',
            'Настройка содержания подразделов',
            'manage_options',
            'reginfoeduorg-content',
            array( $this, 'display_selected_subsections' )
        );*/
    }



    function get_subsection_content($subsection_title) {
        $subsection = get_page_by_title($subsection_title);
        if ($subsection) {
            return get_post_meta($subsection->ID, '_reginfoeduorg_content', true);
        }
        return '';
    }


    function submenu_page() 
    {
        // Определение массива подразделов сайта
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
        $this->child_sections = $child_sections;

        if (isset($_POST['submit'])) {
            // Обрабатываем отправленную форму здесь и сохраняем данные в базу данных
            // Используйте значение $_POST['reginfoeduorg_options'] для сохранения значения поля ввода
            update_option('reginfoeduorg_subsections', $_POST['reginfoeduorg_subsections']);
            // Опционально: добавьте сообщение об успешном сохранении
            echo '<div id="message" class="updated notice is-dismissible"><p>Настройки сохранены</p></div>';
        }

        $reginfoeduorg_subsections = get_option('reginfoeduorg_subsections', array());

        ?>
        <div class="wrap">
            <h1>Подразделы сайта:</h1>
            <form method="post" action="">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Выберите подразделы:</th>
                        <td>
                            <?php foreach ($this->child_sections as $child_section) : ?>
                                <label>
                                    <input type="checkbox" name="reginfoeduorg_subsections[]" value="<?php echo $child_section; ?>" <?php checked(in_array($child_section, $reginfoeduorg_subsections)); ?>>
                                    <?php echo $child_section; ?>
                                </label>
                                <br>
                            <?php endforeach; ?>
                            <br>
                            <button type="button" class="button" id="select-all-button">Выбрать все</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button( 'Сохранить настройки', 'primary', 'submit', false ); ?>
            </form>
        </div>
        <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                const selectAllButton = document.getElementById("select-all-button");
                const checkboxes = document.querySelectorAll("input[type='checkbox']");

                selectAllButton.addEventListener("click", function() {
                    checkboxes.forEach(function(checkbox) {
                        checkbox.checked = true;
                    });
                });
            });
        </script>
        <?php
    }

    function filter_content($content) {
        // Получаем список выбранных подразделов
        $selected_subsections = get_option('reginfoeduorg_subsections', array());

        // Получаем ID родительской страницы
        $parent_page = get_page_by_title('Сведения об образовательной организации');
        $parent_id = $parent_page->ID;

        // Получаем список дочерних страниц
        $child_pages = get_pages(array(
            'child_of' => $parent_id
        ));

        // Обходим список дочерних страниц
        foreach ($child_pages as $child_page) {
            $child_section = $child_page->post_title;
            $child_section_id = $child_page->ID;

            // Если подраздел не выбран, скрываем его
            if (!in_array($child_section, $selected_subsections)) {
                $content = str_replace('[reginfoeduorg-' . sanitize_title($child_section) . ']', '', $content);
            }
        }

        return $content;
    }



    function show_child_sections() 
    {
        $reginfoeduorg_subsections = get_option('reginfoeduorg_subsections', array());

        $parent_page = get_page_by_title('Сведения об образовательной организации');
        $args = array(
            'post_parent' => $parent_page->ID,
            'post_type' => 'page',
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'post_status' => 'publish',
            'numberposts' => -1,
        );
        $child_pages = get_children($args);

        foreach ($child_pages as $child_page) {
            $title = $child_page->post_title;
            if (in_array($title, $reginfoeduorg_subsections)) {
                echo '<h2>' . $title . '</h2>';
                echo apply_filters('the_content', $child_page->post_content);
            }
        }
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

        // Get checked subsections
        $checked_subsections = get_option('reginfoeduorg_subsections', array());

        // Define all subsections
        $all_subsections = array(
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

        // Check if each child page exists and either publish or unpublish it based on the checked subsections
        foreach ($all_subsections as $child_section) {
            $child_page_title = $child_section;
            $child_page_content = '';
            $child_page_args = array(
                'post_title' => $child_page_title,
                'post_content' => $child_page_content,
                'post_status' => in_array($child_section, $checked_subsections) ? 'publish' : 'draft',
                'post_type' => 'page',
                'post_parent' => $parent_id
            );

            $child_page = get_page_by_title($child_page_title);

            if (empty($child_page)) {
                wp_insert_post($child_page_args);
            } else {
                $child_page_args['ID'] = $child_page->ID;
                wp_update_post($child_page_args);
            }
        }
    }

    public function get_selected_subsections() {
        $selected_subsections = get_option('reginfoeduorg_selected_subsections');
        if (empty($selected_subsections)) {
            $selected_subsections = array();
        }
        return $selected_subsections;
    }  


    function my_plugin_settings_page() 
    {
        if (isset($_POST['submit'])) {
            // Обрабатываем отправленную форму здесь и сохраняем данные в базу данных
            // Используйте значение $_POST['reginfoeduorg_options'] для сохранения значения поля ввода
            update_option('my_setting_1', sanitize_text_field($_POST['reginfoeduorg_options']));
            // Опционально: добавьте сообщение об успешном сохранении
            echo '<div id="message" class="updated notice is-dismissible"><p>Настройки сохранены</p></div>';
        }

        ?>
        <div class="wrap">
            <h1>Настройки плагина:</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'reginfoeduorg' ); ?>
                <?php do_settings_sections( 'reginfoeduorg' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Укажите своё имя:</th>
                        <td><input type="text" name="reginfoeduorg_options" value="<?php echo esc_attr( get_option( 'reginfoeduorg_options' ) ); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button( 'Сохранить настройки', 'primary', 'submit', false ); ?>
            </form>
        </div>
        <?php
    }
}
 

if(class_exists('RegInfoEduOrg'))
{
    new RegInfoEduOrg();
}
