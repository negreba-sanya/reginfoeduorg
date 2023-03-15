<?php
/*
Plugin Name: RegInfoEduOrg
Description: Обеспечение публикации регламентированных сведений на сайтах образовательных организаций.
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
       //add_action('admin_init', array($this,'reginfoeduorg_add_roles_and_capabilities'));
    }

    function register_settings() 
    {
        register_setting( 'reginfoeduorg', 'reginfoeduorg_options' );
        add_settings_section( 'my-section-id', false, false, 'reginfoeduorg' );
        add_action( 'admin_init', 'my_plugin_users_page_handle' );
    }

    function add_menu_pages() 
    {
        add_menu_page( 
            'RegInfoEduOrg', 
            'RegInfoEduOrg', 
            'manage_options', 
            'my_plugin_settings', 
            array( $this, 'my_plugin_settings_page' )
        );

        add_submenu_page( 
            'my_plugin_settings',
            'Настройка прав доступа пользователей', 
            'Пользователи',
            'manage_options', 
            'my_plugin_users', 
            array( $this,'my_plugin_users_page') 
        );
    
        add_submenu_page(
            'reginfoeduorg',
            'Подразделы сайта',
            'Подразделы сайта',
            'manage_options',
            'reginfoeduorg-submenu',
            array( $this, 'submenu_page' )
        );

        add_submenu_page(
            'reginfoeduorg',
            'Настройка содержания подразделов',
            'Настройка содержания подразделов',
            'manage_options',
            'reginfoeduorg-content',
            array( $this, 'reginfoeduorg_submenu' )
        );
    }

    function reginfoeduorg_submenu() 
    {

        // Проверяем, была ли кнопка "Сохранить изменения" нажата
        if (isset($_POST['reginfoeduorg_save_changes'])) {
            // Получаем список подразделов из базы данных
            $sections = get_option('reginfoeduorg_subsections');
            
            // Перебираем все подразделы и обновляем контент, если он был изменен
            foreach ($sections as $key => $section) {
                $post_id = get_page_by_title($section)->ID;
                $content = $_POST['reginfoeduorg_subsections'][$key];
               
                if ($post_id && $content) {
                    $post = array(
                        'ID' => $post_id,
                        'post_content' => $content,
                    );
                    wp_update_post($post);
                }
            }
        
            // Выводим сообщение об успешном сохранении изменений
            echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Изменения сохранены.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
        }

        // Получаем список подразделов из базы данных
        $sections = get_option('reginfoeduorg_subsections');
        // Выводим верстку
        echo '<div class="wrap">';
        echo '<h1>Настройка содержания подразделов</h1>';
        echo '<form method="post" action="">';
        echo '<table class="form-table">';
        if (is_array($sections)) {
            foreach ($sections as $key => $section) {                
                echo '<tr><th><label for="section-'.$key.'">'.esc_attr($section).':</label></th><td>';
                $post = get_page_by_title($section);
                $content = $post ? $post->post_content : '';
                $editor_id = 'section-' . $key;
                $settings = array(
                    'textarea_name' => 'reginfoeduorg_subsections['.$key.']',
                    'editor_height' => 200,
                    'media_buttons' => true,
                );
                wp_editor($content, $editor_id, $settings);                
            }
        }
        echo '</table>';
        echo '<p><input type="submit" name="reginfoeduorg_save_changes" class="button-primary" value="Сохранить изменения"></p>';
        echo '</form>';
        echo '</div>';
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

    function my_plugin_add_sections() 
    {
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
            $child_page_args = array(
                'post_title' => $child_page_title,
                'post_status' => in_array($child_section, $checked_subsections) ? 'publish' : 'draft',
                'post_type' => 'page',
                'post_parent' => $parent_id
            );
            $child_page = get_page_by_title($child_page_title);
            if (empty($child_page)) {
                $child_page_id = wp_insert_post($child_page_args);
            } else {
                $child_page_id = $child_page->ID;
                $child_page_args['ID'] = $child_page_id;
                wp_update_post($child_page_args);
            }
            // Сохраняем контент подразделов на странице подраздела
            $section_key = array_search($child_section, $all_subsections);
            $section_content = isset($_POST['reginfoeduorg_subsection_content'][$section_key]) ? $_POST['reginfoeduorg_subsection_content'][$section_key] : '';
            update_post_meta($child_page_id, 'reginfoeduorg_subsection_content_'.$section_key, $section_content);
        }
    }

    function reginfoeduorg_add_roles_and_capabilities() 
    {
        $manager_role = add_role('reginfoeduorg_manager', 'Менеджер RegInfoEduOrg', array(
            'read' => false,
            'reginfoeduorg_edit_subsections' => true,
            'reginfoeduorg_edit_settings' => false,
        ));
        $user = get_user_by('login', 'alex');
        $user->add_role('reginfoeduorg_manager');
    }


    function my_plugin_settings_page() 
    {
        if ( !current_user_can( 'manage_options' ) ) 
        {
            return;
        }
        if ( isset( $_POST['my_plugin_roles_settings'] ) ) {
            $editor_capabilities = isset( $_POST['my_plugin_editor_capabilities'] ) ? $_POST['my_plugin_editor_capabilities'] : array();
            update_option( 'my_plugin_editor_capabilities', $editor_capabilities );
            echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Настройки ролей сохранены.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
        }
        if ( isset( $_POST['my_plugin_add_user'] ) ) {
            $username = isset( $_POST['my_plugin_username'] ) ? sanitize_text_field( $_POST['my_plugin_username'] ) : '';
            $password = isset( $_POST['my_plugin_password'] ) ? $_POST['my_plugin_password'] : '';
            $email = isset( $_POST['my_plugin_email'] ) ? sanitize_email( $_POST['my_plugin_email'] ) : '';

            if ( !empty( $username ) && !empty( $password ) && !empty( $email ) ) {
                $user_id = wp_create_user( $username, $password, $email );
                if ( !is_wp_error( $user_id ) ) {
                    echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Пользователь успешно добавлен.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
                } else {
                    echo '<div id="message" class="error notice notice-error is-dismissible"><p>Не удалось добавить пользователя.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
                }
            } 
            else 
            {
                echo '<div id="message" class="error notice notice-error is-dismissible"><p>Заполните все поля формы добавления пользователя.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
            }
        }

        if ( isset( $_POST['my_plugin_delete_user'] ) ) {
            $user_id = isset( $_POST['my_plugin_user_id'] ) ? intval( $_POST['my_plugin_user_id'] ) : 0;

            if ( $user_id > 0 ) {
                if ( wp_delete_user( $user_id ) ) {
                    echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Пользователь успешно удален.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
                } 
                else {
                    echo '<div id="message" class="error notice notice-error is-dismissible"><p>Не удалось удалить пользователя.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
                }
            } 
            else {
                echo '<div id="message" class="error notice notice-error is-dismissible"><p>Не выбран пользователь для удаления.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
            }
        }

        $users = get_users( array(
            'orderby' => 'ID',
            'order' => 'ASC',
        ) );

                // Получаем список прав доступа для пользователей-редакторов
        $editor_capabilities = get_option( 'my_plugin_editor_capabilities', array() );

        // Выводим верстку формы настроек ролей пользователей
        echo '<div class="wrap">';
        echo '<h1>Настройка ролей пользователей</h1>';
        echo '<form method="post" action="">';

        // Выводим список пользователей WordPress
        echo '<h2>Список пользователей</h2>';
        echo '<table class="widefat">';
        echo '<thead><tr><th>ID</th><th>Имя пользователя</th><th>Email</th><th>Роль</th><th>Действия</th></tr></thead>';
        echo '<tbody>';
        foreach ( $users as $user ) {
            echo '<tr>';
            echo '<td>' . $user->ID . '</td>';
            echo '<td>' . $user->user_login . '</td>';
            echo '<td>' . $user->user_email . '</td>';
            echo '<td>';
            echo '<select name="my_plugin_editor_capabilities[' . $user->ID . ']">';
            echo '<option value="">-- Не назначена --</option>';
            foreach ( wp_roles()->get_names() as $role_name ) {
                $selected = isset( $editor_capabilities[$user->ID] ) && $editor_capabilities[$user->ID] == $role_name ? ' selected="selected"' : '';
                echo '<option value="' . $role_name . '"' . $selected . '>' . $role_name . '</option>';
            }
            echo '</select>';
            echo '</td>';
            echo '<td><a href="' . get_edit_user_link( $user->ID ) . '">Редактировать</a> | ';
            echo '<button type="submit" class="button-link" name="my_plugin_delete_user" onclick="return confirm(\'Вы уверены, что хотите удалить этого пользователя?\')">Удалить</button>';
            echo '<input type="hidden" name="my_plugin_user_id" value="' . $user->ID . '">';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';

        echo '<h2>Добавление пользователя</h2>';
        echo '<table class="form-table">';
        echo '<tr><th><label for="my_plugin_username">Имя пользователя:</label></th><td><input type="text" name="my_plugin_username" id="my_plugin_username"></td></tr>';
        echo '<tr><th><label for="my_plugin_password">Пароль:</label></th><td><input type="password" name="my_plugin_password" id="my_plugin_password"></td></tr>';
        echo '<tr><th><label for="my_plugin_email">Email:</label></th><td><input type="email" name="my_plugin_email" id="my_plugin_email"></td></tr>';
        echo '<tr><th><label for="my_plugin_role">Роль:</label></th><td>';
        echo '<select name="my_plugin_role">';
        foreach ( wp_roles()->get_names() as $role_name ) {
            echo '<option value="' . $role_name . '">' . $role_name . '</option>';
        }
        echo '</select>';
        echo '</td></tr>';
        echo '</table>';
        echo '<p><input type="submit" class="button-primary" value="Добавить пользователя"></p>';
        echo '</form>';
        echo '</div>';
    }
    
    function my_plugin_settings_page_handle() 
    {
        if ( isset( $_POST['my_plugin_editor_capabilities'] ) ) {
            $editor_capabilities = $_POST['my_plugin_editor_capabilities'];
            update_option( 'my_plugin_editor_capabilities', $editor_capabilities );
            echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Настройки ролей пользователей успешно сохранены.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
        }

        if ( isset( $_POST['my_plugin_delete_user'] ) ) {
            $user_id = absint( $_POST['my_plugin_user_id'] );
            if ( $user_id > 0 ) {
                wp_delete_user( $user_id );
                echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Пользователь успешно удален.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
            } else {
                echo '<div id="message" class="error notice notice-error is-dismissible"><p>Не удалось удалить пользователя.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
            }
        }
        if ( isset( $_POST['my_plugin_add_user'] ) ) 
        {
            $username = sanitize_text_field( $_POST['my_plugin_username'] );
            $password = sanitize_text_field( $_POST['my_plugin_password'] );
            $email = sanitize_email( $_POST['my_plugin_email'] );
            $role = sanitize_text_field( $_POST['my_plugin_role'] );
            $user_id = wp_create_user( $username, $password, $email );
            if ( ! is_wp_error( $user_id ) ) {
                $user = get_user_by( 'id', $user_id );
                $user->set_role( $role );
                echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Пользователь успешно добавлен.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
            } 
            else 
            {
                echo '<div id="message" class="error notice notice-error is-dismissible"><p>Не удалось добавить пользователя.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</</span></button></div>';
            }
        }
    }

    function my_plugin_users_page() 
    {
        echo '<div class="wrap">';
        echo '<h1>Настройка прав доступа пользователей</h1>';
        $users = get_users();
        if ( ! empty( $users ) ) {
            echo '<table class="wp-list-table widefat striped">';
            echo '<thead><tr><th>ID</th><th>Логин</th><th>Email</th><th>Роль</th><th>Действия</th></tr></thead>';
            echo '<tbody>';
            foreach ( $users as $user ) {
                $user_id = $user->ID;
                $username = $user->user_login;
                $email = $user->user_email;
                $role = implode( ', ', $user->roles );
                echo '<tr>';
                echo '<td>' . $user_id . '</td>';
                echo '<td>' . $username . '</td>';
                echo '<td>' . $email . '</td>';
                echo '<td>' . $role . '</td>';
                echo '<td>';
                echo '<form method="post" action="">';
                echo '<input type="hidden" name="my_plugin_user_id" value="' . $user_id . '">';
                echo '<select name="my_plugin_user_role">';
                foreach ( wp_roles()->get_names() as $role_name ) {
                    $selected = selected( $role_name, $role, false );
                    echo '<option value="' . $role_name . '" ' . $selected . '>' . $role_name . '</option>';
                }
                echo '</select>';
                echo '<input type="submit" name="my_plugin_change_user_role" class="button" value="Сохранить">';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        } 
        else 
        {
            echo '<p>Нет зарегистрированных пользователей.</p>';
        }
    }
}

if(class_exists('RegInfoEduOrg'))
{
    new RegInfoEduOrg();
}
