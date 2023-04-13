<?php
/*
Plugin Name: RegInfoEduOrg
Description: Обеспечение публикации регламентированных сведений на сайтах образовательных организаций.
Version: 0.0.1
Author: Negreba Aleksandr
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
            'reginfoeduorg', 
            array( $this, 'my_plugin_settings_page' )
        );
        

        add_submenu_page( 
            'reginfoeduorg',
            'Настройка присвоения ролей пользователям', 
            'Пользователи',
            'manage_options', 
            'my_plugin_users', 
            array( $this,'my_plugin_users_page') 
        );

        add_submenu_page( 
            'reginfoeduorg', 
            'Настройка ролей', 
            'Роли', 
            'manage_options', 
            'my_plugin_roles', 
            array( $this,'my_plugin_roles_page')
        );
        
        add_submenu_page(
            'reginfoeduorg',
            'Настройка отображения подразделов сайта',
            'Подразделы сайта',
            'manage_options',
            'reginfoeduorg-submenu',
            array( $this, 'submenu_page' )
        );

        add_submenu_page(
            'reginfoeduorg',
            'Настройка содержания подразделов сайта',
            'Содержание подразделов сайта',
            'manage_options',
            'reginfoeduorg-content',
            array( $this, 'reginfoeduorg_submenu' )
        );
    }




    //Настройка ролей
    function my_plugin_roles_page() {
        // получаем текущего пользователя
        $current_user = wp_get_current_user();
        $user_id = $current_user->user_login;

        // получаем список ролей пользователей из опции my_plugin_users_roles
        $user_roles = get_option( 'my_plugin_users_roles', array() );

        // проверяем, принадлежит ли текущий пользователь какой-то роли
        if ( isset( $user_roles[ $user_id ] ) ) {
            $user_role = $user_roles[ $user_id ];

            // получаем список разрешений доступа для текущей роли из опции my_plugin_access_settings_{role}
            $access_settings = get_option( 'my_plugin_access_settings_' . $user_role, array() );

            // проверяем, разрешен ли доступ к нужному подразделу меню
            if ( $access_settings['Настройка ролей']['read'] == 0 ) {
                wp_die( 'У вас нет доступа' );
            }
            elseif ( $access_settings['Настройка ролей']['edit'] == 0 ) 
            {
                $roles = get_option('my_plugin_roles', array());
                $sections = array(
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
                    'Начальная страница плагина', 
                    'Настройка присвоения ролей пользователям', 
                    'Настройка ролей', 
                    'Настройка отображения подразделов сайта', 
                    'Настройка содержания подразделов сайта'
                );

                $menus = array(
                    'Начальная страница плагина', 
                    'Настройка присвоения ролей пользователям', 
                    'Настройка ролей', 
                    'Настройка отображения подразделов сайта', 
                    'Настройка содержания подразделов сайта'
                );

                $selected_role = '';
            


                // Получаем настройки доступа для выбранной роли
                $access_settings = get_option('my_plugin_access_settings_' . $selected_role, array());

                // JavaScript для динамического изменения checkbox
?>

<script>
    jQuery(document).ready(function () {
        jQuery('#role').change(function () {  
            var selectedRole = jQuery(this).val();
            jQuery('#submit_role').click();            
            var access_settings = <?php echo json_encode(get_option('my_plugin_access_settings_' . $selected_role, array())); ?>;
            console.log(selectedRole);
            console.log(access_settings);
            for (var section in access_settings) {
                if (access_settings.hasOwnProperty(section)) {
                    var read_checkbox = jQuery('input[name="' + section + '_read"]');
                    var edit_checkbox = jQuery('input[name="' + section + '_edit"]');
                    read_checkbox.prop('checked', access_settings[section]['read'] === 1);
                    edit_checkbox.prop('checked', access_settings[section]['edit'] === 1);
                }
            }
        });
    });
</script>

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

    <div class="wrap">
        <h1>Роли для пользователей:</h1>
        <form method="post">
            <input type="hidden" name="selected_role" value="<?php echo $selected_role; ?>">
            <table class="form-table">
                <tr>
                    <th><label for="add-role">Добавить роль</label></th>
                    <td>
                    </td>
                </tr>
                <tr>
                    <th><label for="delete-role">Удалить роль</label></th>
                    <td>
                        <select name="delete_role" id="delete-role">
                            <option value="">Выберите роль</option>
                            <?php foreach ($roles as $role => $name) : ?>
                                <option value="<?php echo $role; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="rename-role">Переименовать роль</label></th>
                    <td>
                        <select name="rename_role" id="rename-role">
                            <option value="">Выберите роль</option>
                            <?php foreach ($roles as $role => $name) : ?>
                                <option value="<?php echo $role; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <h2>Настройка доступа роли: </h2>
            

            <select name="role" id="role">
               <option value="">Выберите роль</option>
               <?php foreach ($roles as $role => $name) : ?>
                  <option value="<?php echo $role; ?>"<?php echo ($selected_role == $role) ? 'selected' : ''; ?>><?php echo $name; ?></option>
               <?php endforeach; ?>
            </select>

            <table class="form-table">
                <?php foreach ($sections as $section) : ?>
                <?php
                          
                          $read_name = sanitize_title(str_replace(['(', ')'], '', $section)) . '_read';
                          $edit_name = sanitize_title(str_replace(['(', ')'], '', $section))  . '_edit';

                          $read_value = isset($access_settings[$section]['read']) ? $access_settings[$section]['read'] : 0;
                          $edit_value = isset($access_settings[$section]['edit']) ? $access_settings[$section]['edit'] : 0;
                          //var_dump($read_name.'<br>');
                          //var_dump($edit_name.'<br>');
                          
                          if($section == "Основные сведения")
                          {?> 
                               <tr>  
                                   <th><h3>Подразделы сайта:</h3></th>
                                   </tr><?php
                          }
                          if($section == "Начальная страница плагина")
                          {?> 
                               <tr>  
                                   <th><h3>Подпункты меню:</h3></th>
                                   </tr><?php
                          }
                                        ?> 
                <tr>                     
                    <th><?php echo $section; ?></th>
                  <td>
                    <label>Чтение</label>   
                    <input type="checkbox" name="<?php echo $read_name; ?>" id="<?php echo $read_name; ?>" value="1" <?php echo ($read_value == 1) ? 'checked' : ''; ?>>
                    <label>Изменение</label>
                    <input type="checkbox" name="<?php echo $edit_name; ?>" id="<?php echo $edit_name; ?>" value="1" <?php echo ($edit_value == 1) ? 'checked' : ''; ?>>
                  </td>
                   
                </tr>
                <?php endforeach; ?>
            </table>                    

            <button type="button" class="button" id="select-all-button">Выбрать все</button>
        </form>
    </div>
<?php
            }
            else{

                $roles = get_option('my_plugin_roles', array());
                $sections = array(
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
                    'Начальная страница плагина', 
                    'Настройка присвоения ролей пользователям', 
                    'Настройка ролей', 
                    'Настройка отображения подразделов сайта', 
                    'Настройка содержания подразделов сайта'
                );

                $menus = array(
                    'Начальная страница плагина', 
                    'Настройка присвоения ролей пользователям', 
                    'Настройка ролей', 
                    'Настройка отображения подразделов сайта', 
                    'Настройка содержания подразделов сайта'
                );

                $selected_role = '';

                if (isset($_POST['submit_role'])) {
                    $selected_role = sanitize_text_field($_POST['role']);            
                }
                // Обработка отправленной формы добавления роли
                if (isset($_POST['submit_add_role'])) {
                    $new_role = sanitize_text_field($_POST['add_role']);
                    if (!empty($new_role)) {
                        // Добавляем новую роль в опцию my_plugin_roles
                        $roles = get_option('my_plugin_roles', array());
                        $roles[$new_role] = $new_role;
                        update_option('my_plugin_roles', $roles);

                        // Добавляем новую роль в систему WordPress
                        add_role($new_role, $new_role);

                        echo '<div class="updated"><p>Роль ' . $new_role . ' успешно добавлена.</p></div>';
                    }
                }

                // Обработка отправленной формы настройки доступа к подразделам
                if (isset($_POST['submit_access_settings'])) {
                    $selected_role = sanitize_text_field($_POST['role']);
                    if (!empty($selected_role)) {
                        $access_settings = array();           
                        foreach ($sections as $section) {
                            $access_settings[$section] = array(
                                'read' => isset($_POST[sanitize_title(str_replace(['(', ')'], '', $section)) . '_read']) ? 1 : 0,
                                'edit' => isset($_POST[sanitize_title(str_replace(['(', ')'], '', $section))  . '_edit']) ? 1 : 0,
                            );
                        }
                        

                        update_option('my_plugin_access_settings_' . $selected_role, $access_settings);
                        echo '<div class="updated"><p>Настройки доступа для роли ' . $selected_role . ' успешно сохранены.</p></div>';
                    }
                }

                // Обработка отправленной формы удаления роли
                if (isset($_POST['submit_delete_role'])) {
                    $role_to_delete = sanitize_text_field($_POST['delete_role']);
                    if (!empty($role_to_delete)) {
                        remove_role($role_to_delete);
                        $roles = get_option('my_plugin_roles', array());
                        unset($roles[$role_to_delete]);
                        update_option('my_plugin_roles', $roles);
                        delete_option('my_plugin_access_settings_' . $role_to_delete);
                        echo '<div class="updated"><p>Роль ' . $roles[$role_to_delete] . ' успешно удалена.</p></div>';
                    }
                }

                // Обработка отправленной формы переименования роли
                if (isset($_POST['submit_rename_role'])) {
                    $role_to_rename = sanitize_text_field($_POST['rename_role']);
                    $new_name = sanitize_text_field($_POST['new_name']);
                    if (!empty($role_to_rename) && !empty($new_name)) {
                        $result = add_role($new_name, $new_name, get_role($role_to_rename)->capabilities);
                        if ($result !== null) {
                            remove_role($role_to_rename);
                            $roles = get_option('my_plugin_roles', array());
                            $roles[$new_name] = $new_name;
                            unset($roles[$role_to_rename]);
                            update_option('my_plugin_roles', $roles);
                            delete_option('my_plugin_access_settings_' . $role_to_rename);
                            echo '<div class="updated"><p>Роль ' . $role_to_rename . ' успешно переименована в ' . $new_name . '.</p></div>';
                        }
                    }
                }


                // Получаем настройки доступа для выбранной роли
                $access_settings = get_option('my_plugin_access_settings_' . $selected_role, array());

                // JavaScript для динамического изменения checkbox
?>

<script>
    jQuery(document).ready(function () {
        jQuery('#role').change(function () {  
            var selectedRole = jQuery(this).val();
            jQuery('#submit_role').click();            
            var access_settings = <?php echo json_encode(get_option('my_plugin_access_settings_' . $selected_role, array())); ?>;
            console.log(selectedRole);
            console.log(access_settings);
            for (var section in access_settings) {
                if (access_settings.hasOwnProperty(section)) {
                    var read_checkbox = jQuery('input[name="' + section + '_read"]');
                    var edit_checkbox = jQuery('input[name="' + section + '_edit"]');
                    read_checkbox.prop('checked', access_settings[section]['read'] === 1);
                    edit_checkbox.prop('checked', access_settings[section]['edit'] === 1);
                }
            }
        });
    });
</script>

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

    <div class="wrap">
        <h1>Роли для пользователей:</h1>
        <form method="post">
            <input type="hidden" name="selected_role" value="<?php echo $selected_role; ?>">
            <table class="form-table">
                <tr>
                    <th><label for="add-role">Добавить роль</label></th>
                    <td>
                        <input type="text" name="add_role" id="add-role" />
                        <input type="submit" name="submit_add_role"  id="submit_add_role" class="button-secondary" value="Добавить" />                        
                    </td>
                </tr>
                <tr>
                    <th><label for="delete-role">Удалить роль</label></th>
                    <td>
                        <select name="delete_role" id="delete-role">
                            <option value="">Выберите роль</option>
                            <?php foreach ($roles as $role => $name) : ?>
                                <option value="<?php echo $role; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="submit" name="submit_delete_role" class="button-secondary" value="Удалить" />
                    </td>
                </tr>
                <tr>
                    <th><label for="rename-role">Переименовать роль</label></th>
                    <td>
                        <select name="rename_role" id="rename-role">
                            <option value="">Выберите роль</option>
                            <?php foreach ($roles as $role => $name) : ?>
                                <option value="<?php echo $role; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="new_name" />
                        <input  type="submit" name="submit_rename_role" class="button-secondary" value="Переименовать" />
                    </td>
                </tr>
            </table>

            <h2>Настройка доступа роли: </h2>
            

            <select name="role" id="role">
               <option value="">Выберите роль</option>
               <?php foreach ($roles as $role => $name) : ?>
                  <option value="<?php echo $role; ?>"<?php echo ($selected_role == $role) ? 'selected' : ''; ?>><?php echo $name; ?></option>
               <?php endforeach; ?>
            </select>

            <table class="form-table">
                <input type="submit" name="submit_role"  id="submit_role" class="button-secondary" value="Показать" />
                <?php foreach ($sections as $section) : ?>
                <?php
                          
                          $read_name = sanitize_title(str_replace(['(', ')'], '', $section)) . '_read';
                          $edit_name = sanitize_title(str_replace(['(', ')'], '', $section))  . '_edit';

                          $read_value = isset($access_settings[$section]['read']) ? $access_settings[$section]['read'] : 0;
                          $edit_value = isset($access_settings[$section]['edit']) ? $access_settings[$section]['edit'] : 0;
                          //var_dump($read_name.'<br>');
                          //var_dump($edit_name.'<br>');
                          
                          if($section == "Основные сведения")
                          {?> 
                               <tr>  
                                   <th><h3>Подразделы сайта:</h3></th>
                                   </tr><?php
                          }
                          if($section == "Начальная страница плагина")
                          {?> 
                               <tr>  
                                   <th><h3>Подпункты меню:</h3></th>
                                   </tr><?php
                          }
                                        ?> 
                <tr>                     
                    <th><?php echo $section; ?></th>
                  <td>
                    <label>Чтение</label>   
                    <input type="checkbox" name="<?php echo $read_name; ?>" id="<?php echo $read_name; ?>" value="1" <?php echo ($read_value == 1) ? 'checked' : ''; ?>>
                    <label>Изменение</label>
                    <input type="checkbox" name="<?php echo $edit_name; ?>" id="<?php echo $edit_name; ?>" value="1" <?php echo ($edit_value == 1) ? 'checked' : ''; ?>>
                  </td>
                   
                </tr>
                <?php endforeach; ?>
            </table>
                      

            <button type="button" class="button" id="select-all-button">Выбрать все</button>
            <input type="submit" name="submit_access_settings" class="button-primary" value="Сохранить" />
        </form>
    </div>
<?php
            }
        }
    }

    //Настройка содержания подразделов сайта
    function reginfoeduorg_submenu() 
    {
        // получаем текущего пользователя
        $current_user = wp_get_current_user();
        $user_id = $current_user->user_login;

        // получаем список ролей пользователей из опции my_plugin_users_roles
        $user_roles = get_option( 'my_plugin_users_roles', array() );

        // проверяем, принадлежит ли текущий пользователь какой-то роли
        if ( isset( $user_roles[ $user_id ] ) ) {
            $user_role = $user_roles[ $user_id ];

            // получаем список разрешений доступа для текущей роли из опции my_plugin_access_settings_{role}
            $access_settings = get_option( 'my_plugin_access_settings_' . $user_role, array() );

            // проверяем, разрешен ли доступ к нужному подразделу меню
            if ( $access_settings['Настройка содержания подразделов сайта']['read'] == 0 ) {
                wp_die( 'У вас нет доступа' );
            }
            elseif ( $access_settings['Настройка содержания подразделов сайта']['edit'] == 0 ) 
            {
                // Получаем список подразделов из базы данных
                $sections = get_option('reginfoeduorg_subsections');
                // Выводим верстку
                echo '<div class="wrap">';
                echo '<h1>Изменение содержания подразделов:</h1>';
                echo '<form method="post" action="" enctype="multipart/form-data">';
                echo '<table class="form-table">';
                if (is_array($sections)) {
                    foreach ($sections as $key => $section) {                
                        if ( $access_settings[$section]['read'] == 1) { 
                            if( $access_settings[$section]['edit'] == 1){
                                
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
                            else
                            {
                                echo '<tr><th><label for="section-'.$key.'">'.esc_attr($section).':</label></th><td>';
                                $post = get_page_by_title($section);
                                $content = $post ? $post->post_content : '';
                                echo '<p>'.$content.'<p>';
                            }
                        }      
                    }
                }
                echo '</table>';                   
                echo '</form>';
                echo '</div>'; 
            }
            else
            {
                if ( isset( $_POST['import_file_submit'] ) && isset( $_FILES['import_file'] ) ) {
                    if ( $_FILES['import_file']['error'] === UPLOAD_ERR_OK ) {
                        $xml = simplexml_load_file( $_FILES['import_file']['tmp_name'] );
                        $editor_id = '';
                        $section_title = 'Основные сведения';
                        $file_contents = '';
                        foreach ( $xml->section as $section ) {
                            switch ((string) $section->section_title)
                            {
                                case 'Основные сведения':
                                    $editor_id = 'section-0';
                                    $section_content = $section->section_content->general_information;
                                    $adress_one = $section_content->addresses_educational_activities;
                                    $adress_two = $section_content->addresses_structural_subdivisions;
                                    $file_contents = '<h4>Основные сведения:</h4><br>';
                                    $file_contents .= "<b>Полное название образовательной организации:</b> {$section_content->full_name}<br>";
                                    $file_contents .= "<b>Краткое название образовательной организации:</b> {$section_content->short_name}<br>";
                                    $file_contents .= "<b>Дата создания образовательной организации:</b> {$section_content->creation_date}<br>";
                                    $file_contents .= "<b>Учредитель:</b> {$section_content->founder}<br>";
                                    $file_contents .= "<b>Адреса осуществления образовательной деятельности:</b><br><ul>";                                    
                                    foreach ($adress_one->children() as $child) {
                                        $file_contents .= "<li>{$child}</li>";
                                    }
                                    $file_contents .= "</ul><b>Адреса расположения структурных подразделений:</b><br><ul>";                                     
                                    foreach ($adress_two->children() as $child) {
                                        $file_contents .= "<li>{$child}</li>";
                                    }
                                    $file_contents .= "</ul><br><b>Место нахождения образовательной организации:</b> {$section_content->location}<br>";
                                    $file_contents .= "<b>Филиалы образовательной организации:</b> {$section_content->branches}<br>";
                                    $file_contents .= "<b>График работы:</b> {$section_content->working_hours}<br>";                                    
                                    $file_contents .= "<b>Контактные телефоны:</b> {$section_content->contact_phones}<br>";
                                    $file_contents .= "<b>Адреса электронной почты:</b> {$section_content->email_addresses}<br>";
?>
                                <script>
                                jQuery(document).ready(function() {
                                    var new_content = <?php echo json_encode($file_contents); ?>;
                                    var editor = tinyMCE.get('<?php echo $editor_id; ?>');
                                    editor.setContent(new_content);
                                });
                                </script>
                            <?php
                                    break;

                                case 'Структура и органы управления образовательной организацией':
                                    $editor_id = 'section-1';
                                    $section_content = $section->section_content->management_structure;
                                    $file_contents = '<p><b>Структурные подразделения</b></p>';

                                    // выводим информацию о структурных подразделениях
                                    $structural_units = $section_content->structural_units;
                                    foreach ($structural_units->children() as $child) {
                                        $file_contents .= "<b>Наименование структурного подразделения:</b> {$child->name}<br>";
                                        $file_contents .= "<b>ФИО руководителя структурного подразделения:</b> {$child->leader}<br>";
                                        $file_contents .= "<b>Должность руководителя структурного подразделения:</b> {$child->position}<br>";
                                        $file_contents .= "<b>Местонахождение структурного подразделения:</b> {$child->location}<br>";
                                        $file_contents .= "<b>Адрес официального сайта структурного подразделения:</b> {$child->official_website}<br>";
                                        $file_contents .= "<b>Адрес электронной почты структурного подразделения:</b> {$child->email}<br>";
                                        $file_contents .= "<b>Сведения о положении о структурном подразделении (об органе управления) с приложением копии указанного положения:</b> {$child->regulations}<br><br>";
                                    }

                                    $file_contents .= '<p><b>Органы управления</b></p>';
                                    // выводим информацию об органах управления
                                    $management_bodies = $section_content->management_bodies;
                                    foreach ($management_bodies->children() as $child) {
                                        $file_contents .= "<b>Наименование органа управления:</b> {$child->name}<br>";
                                        $file_contents .= "<b>ФИО руководителя органа управления:</b> {$child->leader}<br>";
                                        $file_contents .= "<b>Должность руководителя органа управления:</b> {$child->position}<br>";
                                        $file_contents .= "<b>Местонахождение органа управления:</b> {$child->location}<br>";
                                        $file_contents .= "<b>Адрес официального сайта органа управления:</b> {$child->official_website}<br>";
                                        $file_contents .= "<b>Адрес электронной почты органа управления:</b> {$child->email}<br>";
                                        $file_contents .= "<b>Сведения о положении об органе управления с приложением копии указанного положения:</b> {$child->regulations}<br><br>";
                                    }

                            ?>
                            <script>
                                jQuery(document).ready(function() {
                                    var new_content = <?php echo json_encode($file_contents); ?>;
                                    var editor = tinyMCE.get('<?php echo $editor_id; ?>');
                                    editor.setContent(new_content);
                                });
                            </script>
                            <?php
                                    break;

                                case 'Документы':
                                    $editor_id = 'section-2';
                                    $section_content = $section->section_content->documents;
                                    $file_contents = '<strong>Документы:</strong><br>';
                                    foreach ($section_content->children() as $child) {
                                        $title = $child->getName();
                                        if ($title === 'normative_acts') {
                                            $file_contents .= "<strong>Нормативные акты:</strong><br>";
                                            foreach ($child->children() as $inner_child) {
                                                $inner_title = $inner_child->getName();
                                                $inner_info = (string) $inner_child;
                                                $file_contents .= "<a href='$inner_child'>$inner_child</a><br>";
                                            }
                                        }
                                        if ($title === 'self_evaluation_report') 
                                        {
                                            $file_contents .= "<strong>Отчеты:</strong><br>";
                                            $info = (string) $child;
                                            $file_contents .= "<a href='$info'>$info</a><br>";
                                        }
                                        if ($title === 'paid_services') {
                                            $file_contents .= "<strong>Платные образовательные услуги:</strong><br>";
                                            foreach ($child->children() as $inner_child) {
                                                $inner_title = $inner_child->getName();
                                                $inner_info = (string) $inner_child;
                                                $file_contents .= "<a href='$inner_child'>$inner_child</a><br>";
                                            }
                                        } 
                                        else {
                                            $info = (string) $child;
                                            $file_contents .= "<a href='$info'>$info</a><br>";
                                        }
                                    }
                            ?>
                            <script>
                                jQuery(document).ready(function() {
                                    var new_content = <?php echo json_encode($file_contents); ?>;
                                    var editor = tinyMCE.get('<?php echo $editor_id; ?>');
                                    editor.setContent(new_content);
                                });
                            </script>
                            <?php
                                    break;


                                case 'Образование':
                                    $editor_id = 'section-3';
                                    $section_content = $section->section_content;
                                    $file_contents = '';
                                    
                                    $education_levels = $section_content->education_levels;
                                    $file_contents .= '<p><b>Уровни образования:</b></p>';
                                    $level_info = $education_levels->level_info;
                                    $file_contents .= "<p>$level_info</p>";
                                    

                                    // выводим информацию об образовательных программах
                                    $educational_programs = $section_content->educational_programs;
                                    $file_contents .= '<p><b>Образовательные программы:</b></p>';
                                    $program_info = $educational_programs->program_info;
                                    $program_attachment = $educational_programs->program_attachment;
                                    $file_contents .= "<p>$program_info</p><p><b>Приложение с копией образовательной программы:</b> $program_attachment</p>";
                                    

                                    // выводим информацию об учебном плане
                                    $educational_plan = $section_content->educational_plan;
                                    $plan_title = $educational_plan->plan_title;
                                    $plan_info = $educational_plan->plan_info;
                                    $file_contents .= "<p><b>$plan_title:</b> $plan_info</p>";

                                    // выводим информацию о календарном учебном графике
                                    $educational_schedule = $section_content->educational_schedule;
                                    $schedule_title = $educational_schedule->schedule_title;
                                    $schedule_info = $educational_schedule->schedule_info;
                                    $file_contents .= "<p><b>$schedule_title:</b> $schedule_info</p>";

                                    // выводим информацию о документах для обеспечения образовательного процесса
                                    $educational_documents = $section_content->educational_documents;
                                    $documents_title = $educational_documents->documents_title;
                                    $documents_info = $educational_documents->documents_info;
                                    $file_contents .= "<p><b>$documents_title:</b> $documents_info</p>";
                            ?>
                        <script>
                        jQuery(document).ready(function() {
                            var new_content = <?php echo json_encode($file_contents); ?>;
                            var editor = tinyMCE.get('<?php echo $editor_id; ?>');
                            editor.setContent(new_content);
                        });
                        </script>
                        <?php
                                    break;

                                case 'Образовательные стандарты':
                                    $editor_id = 'section-4';
                                    $section_content = $section->section_content;
                                    $file_contents = '';
                                    foreach ($section_content->children() as $child) {
                                        foreach ( $child->children() as $inner_child ) {
                                            $title = $inner_child->getName();
                                            $info = (string) $inner_child;
                                            $file_contents .= "$info<br>";
                                        }
                                    }
                        ?>
                        <script>
                        jQuery(document).ready(function() {
                            var new_content = <?php echo json_encode($file_contents); ?>;
                            var editor = tinyMCE.get('<?php echo $editor_id; ?>');
                            editor.setContent(new_content);
                        });
                        </script>
                        <?php
                                    break;

                                case 'Руководство. Педагогический (научно-педагогический) состав':
                                    $editor_id = 'section-5';
                                    $section_content = $section->section_content;
                                    $file_contents = '';
                                    foreach ($section_content->children() as $inner_child) {
                                        $title = $inner_child->getName();
                                        if ($title == 'management') {
                                            $director = $inner_child->director;
                                            $contents = "<b>Должность:</b> {$director->position}<br>";
                                            $contents .= "<b>Контактный телефон:</b> {$director->phone}<br>";
                                            $contents .= "<b>Адрес электронной почты:</b> {$director->email}<br>";
                                            $contents .= "<b>Дисциплины:</b> {$director->disciplines}<br>";
                                            $contents .= "<b>Образование:</b> {$director->education}<br>";
                                            $contents .= "<b>Специализация:</b> {$director->specialization}<br>";
                                            $contents .= "<b>Повышение квалификации:</b> {$director->qualification_improvement}<br>";
                                            $contents .= "<b>Карьера:</b> {$director->career}<br>";
                                            $contents .= "<b>Общий стаж:</b> {$director->overall_experience}<br>";
                                            $contents .= "<b>Стаж по специализации:</b> {$director->specialization_experience}<br><br>";
                                            $contents .= "</div>";

                                            
                                            $full_name = (string) $director->full_name;
                                            $position = (string) $director->position;
                                            $phone = (string) $director->phone;
                                            $email = (string) $director->email;
                                            $disciplines = (string) $director->disciplines;
                                            $education = (string) $director->education;
                                            $specialization = (string) $director->specialization;
                                            $qualification_improvement = (string) $director->qualification_improvement;
                                            $career = (string) $director->career;
                                            $overall_experience = (string) $director->overall_experience;
                                            $specialization_experience = (string) $director->specialization_experience;


                                            $post_args = array(
                                                    'post_title' => $full_name,
                                                    'post_content' => $contents,
                                                    'post_status' => 'private',
                                                    'exclude_from_search' => true,
                                                    'menu_order' => null,
                                                    'post_parent' => -1,
                                                    'post_type' => 'page'
                                                );
                                            $parent_page = get_page_by_title($full_name);
                                            if (empty($parent_page)) {
                                                // создание новой страницы
                                                $post_id = wp_insert_post($post_args);

                                                // добавление метаполей
                                                update_post_meta($post_id, 'position', $position);
                                                update_post_meta($post_id, 'phone', $phone);
                                                update_post_meta($post_id, 'email', $email);
                                                update_post_meta($post_id, 'disciplines', $disciplines);
                                                update_post_meta($post_id, 'education', $education);
                                                update_post_meta($post_id, 'specialization', $specialization);
                                                update_post_meta($post_id, 'qualification_improvement', $qualification_improvement);
                                                update_post_meta($post_id, 'career', $career);
                                                update_post_meta($post_id, 'overall_experience', $overall_experience);
                                                update_post_meta($post_id, 'specialization_experience', $specialization_experience);
                                            } else {
                                                // обновление существующей страницы
                                                $post_id = $parent_page->ID;

                                                // обновление содержимого страницы
                                                $post_args['ID'] = $post_id;
                                                $post_args['post_content'] = $contents;
                                                wp_update_post($post_args);

                                                // обновление метаполей
                                                update_post_meta($post_id, 'position', $position);
                                                update_post_meta($post_id, 'phone', $phone);
                                                update_post_meta($post_id, 'email', $email);
                                                update_post_meta($post_id, 'disciplines', $disciplines);
                                                update_post_meta($post_id, 'education', $education);
                                                update_post_meta($post_id, 'specialization', $specialization);
                                                update_post_meta($post_id, 'qualification_improvement', $qualification_improvement);
                                                update_post_meta($post_id, 'career', $career);
                                                update_post_meta($post_id, 'overall_experience', $overall_experience);
                                                update_post_meta($post_id, 'specialization_experience', $specialization_experience);
                                            }

                                            // создание ссылки на созданную страницу
                                            $permalink = get_permalink($post_id);


                                            $file_contents .= "<h4>Руководитель:</h4>";
                                            $file_contents .= "<div style='width: 145px;text-align: center;display: inline-flex;'>";
                                            $file_contents .= "<a href = '{$permalink}'>{$director->full_name}</a><br>";
                                            $file_contents .= "</div>";


                                            $deputy_directors = $inner_child->deputy_directors;
                                            $file_contents .= "<h4>Заместители руководителя:</h4>";
                                            foreach ($deputy_directors->children() as $deputy_director) {

                                                $contents = "<b>Должность:</b> {$deputy_director->position}<br>";
                                                $contents .= "<b>Контактный телефон:</b> {$deputy_director->phone}<br>";
                                                $contents .= "<b>Адрес электронной почты:</b> {$deputy_director->email}<br>";
                                                $contents .= "<b>Дисциплины:</b> {$deputy_director->disciplines}<br>";
                                                $contents .= "<b>Образование:</b> {$deputy_director->education}<br>";
                                                $contents .= "<b>Специализация:</b> {$deputy_director->specialization}<br>";
                                                $contents .= "<b>Повышение квалификации:</b> {$deputy_director->qualification_improvement}<br>";
                                                $contents .= "<b>Карьера:</b> {$deputy_director->career}<br>";
                                                $contents .= "<b>Общий стаж:</b> {$deputy_director->overall_experience}<br>";
                                                $contents .= "<b>Стаж по специализации:</b> {$deputy_director->specialization_experience}<br><br>";
                                                $contents .= "</div>";

                                                $full_name = (string) $deputy_director->full_name;
                                                $position = (string) $deputy_director->position;
                                                $phone = (string) $deputy_director->phone;
                                                $email = (string) $deputy_director->email;
                                                $disciplines = (string) $deputy_director->disciplines;
                                                $education = (string) $deputy_director->education;
                                                $specialization = (string) $deputy_director->specialization;
                                                $qualification_improvement = (string) $deputy_director->qualification_improvement;
                                                $career = (string) $deputy_director->career;
                                                $overall_experience = (string) $deputy_director->overall_experience;
                                                $specialization_experience = (string) $deputy_director->specialization_experience;

                                                $post_args = array(
                                                        'post_title' => $full_name,
                                                        'post_content' => $contents,
                                                        'post_status' => 'private',
                                                        'exclude_from_search' => true,
                                                        'menu_order' => null,
                                                        'post_parent' => -1,
                                                        'post_type' => 'page'
                                                    );
                                                $parent_page = get_page_by_title($full_name);
                                                if (empty($parent_page)) {
                                                    // создание новой страницы
                                                    $post_id = wp_insert_post($post_args);

                                                    // добавление метаполей
                                                    update_post_meta($post_id, 'position', $position);
                                                    update_post_meta($post_id, 'phone', $phone);
                                                    update_post_meta($post_id, 'email', $email);
                                                    update_post_meta($post_id, 'disciplines', $disciplines);
                                                    update_post_meta($post_id, 'education', $education);
                                                    update_post_meta($post_id, 'specialization', $specialization);
                                                    update_post_meta($post_id, 'qualification_improvement', $qualification_improvement);
                                                    update_post_meta($post_id, 'career', $career);
                                                    update_post_meta($post_id, 'overall_experience', $overall_experience);
                                                    update_post_meta($post_id, 'specialization_experience', $specialization_experience);
                                                } else {
                                                    // обновление существующей страницы
                                                    $post_id = $parent_page->ID;

                                                    // обновление содержимого страницы
                                                    $post_args['ID'] = $post_id;
                                                    $post_args['post_content'] = $contents;
                                                    wp_update_post($post_args);

                                                    // обновление метаполей
                                                    update_post_meta($post_id, 'position', $position);
                                                    update_post_meta($post_id, 'phone', $phone);
                                                    update_post_meta($post_id, 'email', $email);
                                                    update_post_meta($post_id, 'disciplines', $disciplines);
                                                    update_post_meta($post_id, 'education', $education);
                                                    update_post_meta($post_id, 'specialization', $specialization);
                                                    update_post_meta($post_id, 'qualification_improvement', $qualification_improvement);
                                                    update_post_meta($post_id, 'career', $career);
                                                    update_post_meta($post_id, 'overall_experience', $overall_experience);
                                                    update_post_meta($post_id, 'specialization_experience', $specialization_experience);
                                                }

                                                // создание ссылки на созданную страницу
                                                $permalink = get_permalink($post_id);


                                                
                                                $file_contents .= "<div style='width: 145px;text-align: center;display: inline-flex;'>";
                                                $file_contents .= "<a href = '{$permalink}'> {$deputy_director->full_name}<br>";
                                                $file_contents .= "</div>";
                                            }
                                            $branch_directors = $inner_child->branch_directors;
                                            $file_contents .= "<h4>Руководители филиалов:</h4>";
                                            foreach ($branch_directors->children() as $branch_director) {
                                                $contents = "<b>Должность:</b> {$branch_director->position}<br>";
                                                $contents .= "<b>Контактный телефон:</b> {$branch_director->phone}<br>";
                                                $contents .= "<b>Адрес электронной почты:</b> {$branch_director->email}<br>";
                                                $contents .= "<b>Дисциплины:</b> {$branch_director->disciplines}<br>";
                                                $contents .= "<b>Образование:</b> {$branch_director->education}<br>";
                                                $contents .= "<b>Специализация:</b> {$branch_director->specialization}<br>";
                                                $contents .= "<b>Повышение квалификации:</b> {$branch_director->qualification_improvement}<br>";
                                                $contents .= "<b>Карьера:</b> {$branch_director->career}<br>";
                                                $contents .= "<b>Общий стаж:</b> {$branch_director->overall_experience}<br>";
                                                $contents .= "<b>Стаж по специализации:</b> {$branch_director->specialization_experience}<br><br>";
                                                $contents .= "</div>";

                                                $full_name = (string) $branch_director->full_name;
                                                $position = (string) $branch_director->position;
                                                $phone = (string) $branch_director->phone;
                                                $email = (string) $branch_director->email;
                                                $disciplines = (string) $branch_director->disciplines;
                                                $education = (string) $branch_director->education;
                                                $specialization = (string) $branch_director->specialization;
                                                $qualification_improvement = (string) $branch_director->qualification_improvement;
                                                $career = (string) $branch_director->career;
                                                $overall_experience = (string) $branch_director->overall_experience;
                                                $specialization_experience = (string) $branch_director->specialization_experience;

                                                $post_args = array(
                                                        'post_title' => $full_name,
                                                        'post_content' => $contents,
                                                        'post_status' => 'private',
                                                        'exclude_from_search' => true,
                                                        'menu_order' => null,
                                                        'post_parent' => -1,
                                                        'post_type' => 'page'
                                                    );
                                                $parent_page = get_page_by_title($full_name);
                                                if (empty($parent_page)) {
                                                    // создание новой страницы
                                                    $post_id = wp_insert_post($post_args);

                                                    // добавление метаполей
                                                    update_post_meta($post_id, 'position', $position);
                                                    update_post_meta($post_id, 'phone', $phone);
                                                    update_post_meta($post_id, 'email', $email);
                                                    update_post_meta($post_id, 'disciplines', $disciplines);
                                                    update_post_meta($post_id, 'education', $education);
                                                    update_post_meta($post_id, 'specialization', $specialization);
                                                    update_post_meta($post_id, 'qualification_improvement', $qualification_improvement);
                                                    update_post_meta($post_id, 'career', $career);
                                                    update_post_meta($post_id, 'overall_experience', $overall_experience);
                                                    update_post_meta($post_id, 'specialization_experience', $specialization_experience);
                                                } else {
                                                    // обновление существующей страницы
                                                    $post_id = $parent_page->ID;

                                                    // обновление содержимого страницы
                                                    $post_args['ID'] = $post_id;
                                                    $post_args['post_content'] = $contents;
                                                    wp_update_post($post_args);

                                                    // обновление метаполей
                                                    update_post_meta($post_id, 'position', $position);
                                                    update_post_meta($post_id, 'phone', $phone);
                                                    update_post_meta($post_id, 'email', $email);
                                                    update_post_meta($post_id, 'disciplines', $disciplines);
                                                    update_post_meta($post_id, 'education', $education);
                                                    update_post_meta($post_id, 'specialization', $specialization);
                                                    update_post_meta($post_id, 'qualification_improvement', $qualification_improvement);
                                                    update_post_meta($post_id, 'career', $career);
                                                    update_post_meta($post_id, 'overall_experience', $overall_experience);
                                                    update_post_meta($post_id, 'specialization_experience', $specialization_experience);
                                                }

                                                // создание ссылки на созданную страницу
                                                $permalink = get_permalink($post_id);


                                                $file_contents .= "<div style='width: 145px;text-align: center;display: inline-flex;'>";
                                                $file_contents .= "<a href = '{$permalink}'> {$branch_director->full_name}</a><br>";
                                                $file_contents .= "</div>";
                                            }
                                        } else if ($title == 'pedagogical_staff') {
                                            $pedagogical_workers = $inner_child->pedagogical_worker;
                                            $file_contents .= "<h4>Педагогические работники:</h4>";
                                            foreach ($pedagogical_workers as $pedagogical_worker) {
                                                
                                                
                                                $contents = "<b>Должность:</b> {$pedagogical_worker->position}<br>";
                                                $contents .= "<b>Контактный телефон:</b> {$pedagogical_worker->phone}<br>";
                                                $contents .= "<b>Адрес электронной почты:</b> {$pedagogical_worker->email}<br>";
                                                $contents .= "<b>Дисциплины:</b> {$pedagogical_worker->disciplines}<br>";
                                                $contents .= "<b>Образование:</b> {$pedagogical_worker->education}<br>";
                                                $contents .= "<b>Специализация:</b> {$pedagogical_worker->specialization}<br>";
                                                $contents .= "<b>Повышение квалификации:</b> {$pedagogical_worker->qualification_improvement}<br>";
                                                $contents .= "<b>Карьера:</b> {$pedagogical_worker->career}<br>";
                                                $contents .= "<b>Общий стаж:</b> {$pedagogical_worker->overall_experience}<br>";
                                                $contents .= "<b>Стаж по специализации:</b> {$pedagogical_worker->specialization_experience}<br><br>";
                                                $contents .= "</div>";

                                                $full_name = (string) $pedagogical_worker->full_name;
                                                $position = (string) $pedagogical_worker->position;
                                                $phone = (string) $pedagogical_worker->phone;
                                                $email = (string) $pedagogical_worker->email;
                                                $disciplines = (string) $pedagogical_worker->disciplines;
                                                $education = (string) $pedagogical_worker->education;
                                                $specialization = (string) $pedagogical_worker->specialization;
                                                $qualification_improvement = (string) $pedagogical_worker->qualification_improvement;
                                                $career = (string) $pedagogical_worker->career;
                                                $overall_experience = (string) $pedagogical_worker->overall_experience;
                                                $specialization_experience = (string) $pedagogical_worker->specialization_experience;

                                                $post_args = array(
                                                    'post_title' => $full_name,
                                                    'post_content' => $contents,
                                                    'post_status' => 'private',
                                                    'exclude_from_search' => true,
                                                    'menu_order' => null,
                                                    'post_parent' => -1,
                                                    'post_type' => 'page'
                                                );
                                                $parent_page = get_page_by_title($full_name);
                                                if (empty($parent_page)) {
                                                    // создание новой страницы
                                                    $post_id = wp_insert_post($post_args);

                                                    // добавление метаполей
                                                    update_post_meta($post_id, 'position', $position);
                                                    update_post_meta($post_id, 'phone', $phone);
                                                    update_post_meta($post_id, 'email', $email);
                                                    update_post_meta($post_id, 'disciplines', $disciplines);
                                                    update_post_meta($post_id, 'education', $education);
                                                    update_post_meta($post_id, 'specialization', $specialization);
                                                    update_post_meta($post_id, 'qualification_improvement', $qualification_improvement);
                                                    update_post_meta($post_id, 'career', $career);
                                                    update_post_meta($post_id, 'overall_experience', $overall_experience);
                                                    update_post_meta($post_id, 'specialization_experience', $specialization_experience);
                                                } else {
                                                    // обновление существующей страницы
                                                    $post_id = $parent_page->ID;

                                                    // обновление содержимого страницы
                                                    $post_args['ID'] = $post_id;
                                                    $post_args['post_content'] = $contents;
                                                    wp_update_post($post_args);

                                                    // обновление метаполей
                                                    update_post_meta($post_id, 'position', $position);
                                                    update_post_meta($post_id, 'phone', $phone);
                                                    update_post_meta($post_id, 'email', $email);
                                                    update_post_meta($post_id, 'disciplines', $disciplines);
                                                    update_post_meta($post_id, 'education', $education);
                                                    update_post_meta($post_id, 'specialization', $specialization);
                                                    update_post_meta($post_id, 'qualification_improvement', $qualification_improvement);
                                                    update_post_meta($post_id, 'career', $career);
                                                    update_post_meta($post_id, 'overall_experience', $overall_experience);
                                                    update_post_meta($post_id, 'specialization_experience', $specialization_experience);
                                                }

                                                // создание ссылки на созданную страницу
                                                $permalink = get_permalink($post_id);




                                                $file_contents .= "<div style='width: 145px;text-align: center;display: inline-flex;'>";
                                                $file_contents .= "<a href='{$permalink}'>{$pedagogical_worker->full_name}</a><br>";
                                                $file_contents .= "</div>";
                                            }
                                        }
                                    }
                                    
                        ?>
                            <script>
                            jQuery(document).ready(function() {
                                var new_content = <?php echo json_encode($file_contents); ?>;
                                var editor = tinyMCE.get('<?php echo $editor_id; ?>');
                                editor.setContent(new_content);
                            });
                            </script>
                            <?php
                                    break;


                                case 'Материально-техническое обеспечение и оснащенность образовательного процесса':
                                    $editor_id = 'section-6';
                                    $section_content = $section->section_content;
                                    $file_contents = '';
                                    foreach ($section_content->technical_equipment->children() as $child) {
                                        $title = $child->getName();
                                        if ($title == 'classrooms') {
                                            $file_contents .= "<h4>Учебные кабинеты:</h4><br>";
                                            foreach ($child->children() as $classroom) {
                                                $file_contents .= "<b>Наименование:</b> {$classroom->name}<br>";
                                                $file_contents .= "<b>Тип:</b> {$classroom->type}<br>";
                                                $file_contents .= "<b>Оборудование:</b> {$classroom->equipment}<br>";
                                                $file_contents .= "<b>Доступность для инвалидов:</b> {$classroom->accessibility}<br><br>";
                                            }
                                        } else if ($title == 'training_objects') {
                                            $file_contents .= "<h4>Объекты для практических занятий:</h4><br>";
                                            foreach ($child->children() as $training_object) {
                                                $file_contents .= "<b>Наименование:</b> {$training_object->name}<br>";
                                                $file_contents .= "<b>Тип:</b> {$training_object->type}<br>";
                                                $file_contents .= "<b>Оборудование:</b> {$training_object->equipment}<br>";
                                                $file_contents .= "<b>Доступность для инвалидов:</b> {$training_object->accessibility}<br><br>";
                                            }
                                        } else if ($title == 'library') {
                                            $file_contents .= "<h4>Библиотека:</h4><br>";
                                            $file_contents .= "<b>Название:</b> {$child->name}<br>";
                                            $file_contents .= "<b>Коллекция:</b> {$child->collection}<br>";
                                            $file_contents .= "<b>Услуги:</b> {$child->services}<br>";
                                            $file_contents .= "<b>Доступность для инвалидов:</b> {$child->accessibility}<br><br>";
                                        } else if ($title == 'sports_facilities') {
                                            $file_contents .= "<h4>Спортивные объекты:</h4><br>";
                                            foreach ($child->children() as $sports_facility) {
                                                $file_contents .= "<b>Наименование:</b> {$sports_facility->name}<br>";
                                                $file_contents .= "<b>Тип:</b> {$sports_facility->type}<br>";
                                                $file_contents .= "<b>Оборудование:</b> {$sports_facility->equipment}<br>";
                                                $file_contents .= "<b>Доступность для инвалидов:</b> {$sports_facility->accessibility}<br><br>";
                                            }
                                        } else if ($title == 'learning_resources') {
                                            $file_contents .= "<h4>Средства обучения и воспитания:</h4><br>";
                                            foreach ($child->children() as $resource) {
                                                $file_contents .= "<b>Тип:</b> {$resource->type}<br>";
                                                $file_contents .= "<b>Доступность для инвалидов:</b> {$resource->accessibility}<br><br>";
                                            }
                                        }
                                        else if ($title == 'facilities_for_disabled') {
                                            $file_contents .= "<h4>Объекты для инвалидов:</h4><br>";
                                            foreach ($child->children() as $facility) {
                                                $file_contents .= "<b>Тип:</b> {$facility->type}<br>";
                                                $file_contents .= "<b>Список оборудования:</b> {$facility->equipment}<br><br>";
                                            }
                                        }
                                    }
                            ?>
                            <script>
                            jQuery(document).ready(function() {
                                var new_content = <?php echo json_encode($file_contents); ?>;
                                var editor = tinyMCE.get('<?php echo $editor_id; ?>');
                                editor.setContent(new_content);
                            });
                            </script>
                            <?php
                                    break;


                                
                                case 'Студенческие стипендии и материальная поддержка':
                                    $editor_id = 'section-7';
                                    $section_content = $section->section_content;
                                    $file_contents = '';
                                    foreach ($section_content->children() as $child) {
                                        $title = $child->getName();
                                        if ($title == 'scholarships') {
                                            $scholarship_title = $child->scholarship_title;
                                            $file_contents .= "<h4>{$scholarship_title}</h4><br>";
                                            $scholarship_info = $child->scholarship_info;
                                            $file_contents .= "<p>{$scholarship_info}</p><br>";
                                        } else if ($title == 'social_support') {
                                            $support_title = $child->support_title;
                                            $file_contents .= "<h4>{$support_title}</h4><br>";
                                            $support_info = $child->support_info;
                                            $file_contents .= "<p>{$support_info}</p><br>";
                                        } else if ($title == 'dormitories') {
                                            $dormitory_title = $child->dormitory_title;
                                            $file_contents .= "<h4>{$dormitory_title}</h4><br>";
                                            $dormitory_info = $child->dormitory_info;
                                            $file_contents .= "<p>{$dormitory_info}</p><br>";
                                        } else if ($title == 'employment') {
                                            $employment_title = $child->employment_title;
                                            $file_contents .= "<h4>{$employment_title}</h4><br>";
                                            $employment_info = $child->employment_info;
                                            $file_contents .= "<p>{$employment_info}</p><br>";
                                        }
                                    }
                            ?>
                            <script>
                            jQuery(document).ready(function() {
                                var new_content = <?php echo json_encode($file_contents); ?>;
                                var editor = tinyMCE.get('<?php echo $editor_id; ?>');
                                editor.setContent(new_content);
                            });
                            </script>
                            <?php
                                    break;


                                case 'Платные образовательные услуги':
                                    $editor_id = 'section-8';
                                    $section_content = $section->section_content;
                                    $file_contents = '';
                                    foreach ($section_content->children() as $child) {
                                        $info = (string) $child;
                                        $file_contents .= "$info<br>";
                                    }
                            ?>
                            <script>
                                jQuery(document).ready(function() {
                                    var new_content = <?php echo json_encode($file_contents); ?>;
                                    var editor = tinyMCE.get('<?php echo $editor_id; ?>');
                                    editor.setContent(new_content);
                                });
                            </script>
                            <?php
                                    break;

                                case 'Финансово-хозяйственная деятельность':
                                    $editor_id = 'section-9';
                                    $section_content = $section->section_content;
                                    $file_contents = '';
                                    foreach ($section_content->children() as $child) {
                                        foreach ( $child->children() as $inner_child ) {
                                            $title = $inner_child->getName();
                                            $info = (string) $inner_child;
                                            $file_contents .= "$info<br>";
                                        }
                                    }
                            ?>
                            <script>
                                jQuery(document).ready(function() {
                                    var new_content = <?php echo json_encode($file_contents); ?>;
                                    var editor = tinyMCE.get('<?php echo $editor_id; ?>');
                                    editor.setContent(new_content);
                                });
                            </script>
                            <?php
                                    break;

                                case 'Вакантные места для приема (перевода)':
                                    $editor_id = 'section-10';
                                    $vacancies = $section->section_content->vacancies_info;
                                    $file_contents = '<p><b>Информация о вакантных местах</b></p>';
                                    foreach ($vacancies->vacancies_list->program as $program) {
                                        $program_name = (string)$program->program_name;
                                        $profession = (string)$program->profession;
                                        $specialization = (string)$program->specialization;
                                        $study_direction = (string)$program->study_direction;
                                        $budget_vacancies = (string)$program->budget_vacancies;
                                        $contract_vacancies = (string)$program->contract_vacancies;
                                        $file_contents .= "<p>$program_name<br>$profession, $specialization, $study_direction<br>Бюджетные места: $budget_vacancies, по договорам: $contract_vacancies</p>";
                                    }
                            ?>
                            <script>
                                jQuery(document).ready(function() {
                                    var new_content = <?php echo json_encode($file_contents); ?>;
                                    var editor = tinyMCE.get('<?php echo $editor_id; ?>');
                                    editor.setContent(new_content);
                                });
                            </script>
                            <?php
                                    break;


                                
                            } 
                            
                        }
                        echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Импорт успешно выполнен</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
                        
                    }
                    else {
                        echo '<div id="message" class="error notice notice-error is-dismissible"><p>Файл не загружен</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
                    }
                }


                
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
                echo '<h1>Изменение содержания подразделов:</h1>';
                echo '<form method="post" action="" enctype="multipart/form-data">';
                echo '<table class="form-table">';
                if (is_array($sections)) {
                    foreach ($sections as $key => $section) {    
                        if ( $access_settings[$section]['read'] == 1) { 
                            if( $access_settings[$section]['edit'] == 1){
                        
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
                            else
                            {
                                 echo '<tr><th><label for="section-'.$key.'">'.esc_attr($section).':</label></th><td>';
                        $post = get_page_by_title($section);
                        $content = $post ? $post->post_content : '';
                        echo '<p>'.$content.'<p>';
                            }
                        }        
                    }
                }
                echo '</table>';
                echo '<p><input type="submit" name="reginfoeduorg_save_changes" class="button-primary" value="Сохранить изменения"></p>';
                
                
                echo '<table class="form-table">';
                echo '<tr>';
                echo '<th><label for="import-file">Загрузить XML файл:</label></th>';
                echo '<td><input type="file" name="import_file" id="import-file" /></td>';
                echo '</tr>';
                echo '</table>';
                echo '<p><input type="submit" class="button-primary" name="import_file_submit" value="Импортировать данные"></p>';
                
                echo '</form>';
                echo '</div>'; 
            }
        }



        
    }
    
    //Настройка отображения подразделов сайта
    function submenu_page() 
    {
        // получаем текущего пользователя
        $current_user = wp_get_current_user();
        $user_id = $current_user->user_login;

        // получаем список ролей пользователей из опции my_plugin_users_roles
        $user_roles = get_option( 'my_plugin_users_roles', array() );

        // проверяем, принадлежит ли текущий пользователь какой-то роли
        if ( isset( $user_roles[ $user_id ] ) ) {
            $user_role = $user_roles[ $user_id ];

            // получаем список разрешений доступа для текущей роли из опции my_plugin_access_settings_{role}
            $access_settings = get_option( 'my_plugin_access_settings_' . $user_role, array() );

            // проверяем, разрешен ли доступ к нужному подразделу меню
            if ( $access_settings['Настройка отображения подразделов сайта']['read'] == 0 ) {
                wp_die( 'У вас нет доступа' );
            }
            elseif ( $access_settings['Настройка отображения подразделов сайта']['edit'] == 0 ) 
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

                $reginfoeduorg_subsections = get_option('reginfoeduorg_subsections', array());

                            ?>
        <div class="wrap">
            <h1>Отображение подразделов сайта:</h1>
            <form method="post" action="">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Выберите подразделы, которые будут отображаться на сайте:</th>
                        <td>
                            <?php foreach ($this->child_sections as $child_section) : ?>
                                <label>
                                    <input type="checkbox" name="reginfoeduorg_subsections[]" value="<?php echo $child_section; ?>" <?php checked(in_array($child_section, $reginfoeduorg_subsections)); ?>>
                                    <?php echo $child_section; ?>
                                </label>
                                <br>
                            <?php endforeach; ?>
                            <br>
                        </td>
                    </tr>
                </table>
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
            else{


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
            <h1>Отображение подразделов сайта:</h1>
            <form method="post" action="">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Выберите подразделы, которые будут отображаться на сайте:</th>
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
                <?php submit_button( 'Сохранить', 'primary', 'submit', false ); ?>
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
        }
    }

    //Проверка наличия подразделов сайта
    function my_plugin_add_sections() 
    {
        $page_title = 'Сведения об образовательной организации';
        $page_content = '';
        $parent_id = 0;
        $parent_page = get_page_by_title($page_title);

        // Check if the parent page exists, if not create one
        if (empty($parent_page)) {
            $page_args = array('post_title' => $page_title,
                'post_content' => $page_content,
                'post_status' => 'publish',
                'post_type' => 'page'
                ,
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

    //Начальная страница плагина
    function my_plugin_settings_page() 
    {
        // получаем текущего пользователя
        $current_user = wp_get_current_user();
        $user_id = $current_user->user_login;

        // получаем список ролей пользователей из опции my_plugin_users_roles
        $user_roles = get_option( 'my_plugin_users_roles', array() );

        // проверяем, принадлежит ли текущий пользователь какой-то роли
        if ( isset( $user_roles[ $user_id ] ) ) {
            $user_role = $user_roles[ $user_id ];

            // получаем список разрешений доступа для текущей роли из опции my_plugin_access_settings_{role}
            $access_settings = get_option( 'my_plugin_access_settings_' . $user_role, array() );

            // проверяем, разрешен ли доступ к нужному подразделу меню
            if ( $access_settings['Начальная страница плагина']['read'] == 0 ) {
                wp_die( 'У вас нет доступа' );
            }
            elseif ( $access_settings['Начальная страница плагина']['edit'] == 0 ) 
            {
                
            }
            else{                
                          
        ?>
<div style="display: flex; align-items: center; justify-content: center; height: 100vh;">
  <div style="background-color: #f5f5f5; padding: 20px; border-radius: 5px; max-width: 800px;">
    <h2 style="font-size: 24px; margin-bottom: 10px;">RegInfoEduOrg</h2>
    <p style="font-size: 18px; margin-bottom: 20px;">Данный плагин предоставляет следующие возможности:</p>
    <ol style="font-size: 18px; margin-bottom: 20px;">
      <li>Создание и управление разделами информации об образовательной организации</li>
      <li>Настройка доступа к разделам для различных ролей пользователей</li>
      <li>Возможность редактирования содержимого разделов с помощью встроенного редактора</li>
      <li>Импорт данных из XML файла, с выгруженной информацией из сторонних информационных систем, таких как 1С. <a href="<?php echo plugins_url( 'structure.xml', __FILE__ ); ?>" target="_blank" style="text-decoration: none; color: #007bff;">Структура XML файла</a></li>
      <li>Внешняя обработка для экспорта данных из информационной системы 1С. <a href="https://example.com/export_1c.php" target="_blank" style="text-decoration: none; color: #007bff;"> Пример внешней обработки</a></li>
    </ol>
    <p style="font-size: 18px;">Данный плагин очень полезен для любой образовательной организации, которая хочет эффективно управлять своей информацией, а также упрощает работу сотрудникам, которые занимаются администрированием сайта.</p>
  </div>
</div>



<?php
            }
        }
    }
    
    //Настройка присвоения ролей пользователям
    function my_plugin_users_page() {
        // получаем текущего пользователя
        $current_user = wp_get_current_user();
        $user_id = $current_user->user_login;

        // получаем список ролей пользователей из опции my_plugin_users_roles
        $user_roles = get_option( 'my_plugin_users_roles', array() );

        // проверяем, принадлежит ли текущий пользователь какой-то роли
        if ( isset( $user_roles[ $user_id ] ) ) {
            $user_role = $user_roles[ $user_id ];

            // получаем список разрешений доступа для текущей роли из опции my_plugin_access_settings_{role}
            $access_settings = get_option( 'my_plugin_access_settings_' . $user_role, array() );

            // проверяем, разрешен ли доступ к нужному подразделу меню
            if ( $access_settings['Настройка присвоения ролей пользователям']['read'] == 0 ) {
                wp_die( 'У вас нет доступа' );
            }
            elseif ( $access_settings['Настройка присвоения ролей пользователям']['edit'] == 0 ) 
            {
                // получаем список пользователей
                $users = get_users();

                $users_roles = get_option('my_plugin_users_roles', array());



                if ( isset( $_POST['my_plugin_change_user_role'] ) ) {
                    $user_id = intval( $_POST['my_plugin_user_id'] );
                    $new_role = sanitize_text_field( $_POST['my_plugin_user_role'] );

                    // Получаем список пользователей и их ролей из опции my_plugin_users_roles
                    $users_roles = get_option( 'my_plugin_users_roles', array() );

                    // Получаем пользователя по его ID
                    $user = get_user_by( 'id', $user_id );

                    // Если пользователь найден
                    if ( $user ) {
                        $username = $user->user_login;

                        // Присваиваем новую роль пользователю
                        $users_roles[ $username ] = $new_role;

                        // Сохраняем изменения в опцию my_plugin_users_roles
                        update_option( 'my_plugin_users_roles', $users_roles );
                    }
                }



                // выводим таблицу с пользователями
                echo '<div class="wrap">';
                echo '<h1>Настройка прав доступа пользователей</h1>';

                if ( ! empty( $users ) ) {
                    echo '<table class="wp-list-table widefat striped">';
                    echo '<thead><tr><th>ID</th><th>Логин</th><th>Email</th><th>Роль</th><th>Действия</th></tr></thead>';
                    echo '<tbody>';

                    foreach ( $users as $user ) {
                        $user_id = $user->ID;
                        $username = $user->user_login;
                        $email = $user->user_email;
                        $user_role = isset($users_roles[$username]) ? $users_roles[$username] : '';$role = implode( ', ', $user->roles );
                        $access = isset( $user_access[ $username ] ) ? $user_access[ $username ] : array();

                        echo '<tr>';
                        echo '<td>' . $user_id . '</td>';
                        echo '<td>' . $username . '</td>';
                        echo '<td>' . $email . '</td>';
                        echo '<td>' . $user_role . '</td>';

                        
                        echo '</td>';
                        echo '<td>';
                        echo '<form method="post">';
                        echo '<input type="hidden" name="my_plugin_user_id" value="' . $user_id . '">';
                        echo '<input type="hidden" name="my_plugin_user_username" value="' . $username . '">';
                        echo '<select name="my_plugin_user_role">';

                        // получаем список ролей из опции my_plugin_roles
                        $roles = get_option( 'my_plugin_roles', array() );
                        foreach ( $roles as $role ) {
                            $selected = selected( $role, $user->roles[0], false );
                            echo '<option value="' . $role . '" ' . $selected . '>' . $role . '</option>';
                        }

                        echo '</select>';
                        echo '</form>';
                        echo '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody>';
                    echo '</table>';
                } else {
                    echo '<p>Нет зарегистрированных пользователей.</p>';
                }

                echo '</div>';
            }
            else{
                
                // получаем список пользователей
                $users = get_users();

                $users_roles = get_option('my_plugin_users_roles', array());



                if ( isset( $_POST['my_plugin_change_user_role'] ) ) {
                    $user_id = intval( $_POST['my_plugin_user_id'] );
                    $new_role = sanitize_text_field( $_POST['my_plugin_user_role'] );

                    // Получаем список пользователей и их ролей из опции my_plugin_users_roles
                    $users_roles = get_option( 'my_plugin_users_roles', array() );

                    // Получаем пользователя по его ID
                    $user = get_user_by( 'id', $user_id );

                    // Если пользователь найден
                    if ( $user ) {
                        $username = $user->user_login;

                        // Присваиваем новую роль пользователю
                        $users_roles[ $username ] = $new_role;

                        // Сохраняем изменения в опцию my_plugin_users_roles
                        update_option( 'my_plugin_users_roles', $users_roles );
                    }
                }



                // выводим таблицу с пользователями
                echo '<div class="wrap">';
                echo '<h1>Настройка прав доступа пользователей</h1>';

                if ( ! empty( $users ) ) {
                    echo '<table class="wp-list-table widefat striped">';
                    echo '<thead><tr><th>ID</th><th>Логин</th><th>Email</th><th>Роль</th><th>Действия</th></tr></thead>';
                    echo '<tbody>';

                    foreach ( $users as $user ) {
                        $user_id = $user->ID;
                        $username = $user->user_login;
                        $email = $user->user_email;
                        $user_role = isset($users_roles[$username]) ? $users_roles[$username] : '';$role = implode( ', ', $user->roles );
                        $access = isset( $user_access[ $username ] ) ? $user_access[ $username ] : array();

                        echo '<tr>';
                        echo '<td>' . $user_id . '</td>';
                        echo '<td>' . $username . '</td>';
                        echo '<td>' . $email . '</td>';
                        echo '<td>' . $user_role . '</td>';

                        
                        echo '</td>';
                        echo '<td>';
                        echo '<form method="post">';
                        echo '<input type="hidden" name="my_plugin_user_id" value="' . $user_id . '">';
                        echo '<input type="hidden" name="my_plugin_user_username" value="' . $username . '">';
                        echo '<select name="my_plugin_user_role">';

                        // получаем список ролей из опции my_plugin_roles
                        $roles = get_option( 'my_plugin_roles', array() );
                        foreach ( $roles as $role ) {
                            $selected = selected( $role, $user->roles[0], false );
                            echo '<option value="' . $role . '" ' . $selected . '>' . $role . '</option>';
                        }

                        echo '</select>';
                        echo '<input type="submit" name="my_plugin_change_user_role" class="button" value="Сохранить">';
                        echo '</form>';
                        echo '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody>';
                    echo '</table>';
                } else {
                    echo '<p>Нет зарегистрированных пользователей.</p>';
                }

                echo '</div>';
            }
        }
    }







}

if(class_exists('RegInfoEduOrg'))
{
    new RegInfoEduOrg();
}
