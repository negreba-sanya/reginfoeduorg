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

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class General_Information_Table extends WP_List_Table {
    private $search_field;
    public function __construct($subsection_id, $search_field) {
        parent::__construct(array(
            'singular' => 'reginfoeduorg',
            'plural'   => 'reginfoeduorgs',
            'ajax'     => false
        ));
        $this-> search_field = $search_field;
    }

    public function get_columns() {
        return array(
            'cb'                => '<input type="checkbox" />',
            'full_name'         => 'Полное название образовательной организации',
            'short_name'        => 'Краткое название образовательной организации',
            'creation_date'     => 'Дата создания образовательной организации',
            'founder'           => 'Учредитель',
            'location'          => 'Место нахождения образовательной организации',
            'branches'          => 'Филиалы образовательной организации',
            'working_hours'     => 'График работы',
            'contact_phones'    => 'Контактные телефоны',
            'email_addresses'   => 'Адреса электронной почты'
        );
    }  


    public function prepare_items() {
        global $wpdb;
        $search = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : false;

        $do_search = ( $search ) ? $wpdb->prepare(" WHERE  $this->search_field LIKE '%%%s%%'", $search) : '';

        $data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_general_information{$do_search}", ARRAY_A);
        $this->items = $data;
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
    }


    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="item[]" value="%s" />',
            $item['id']
        );
    }

    public function column_default($item, $column_name) {
        // Если выбрано действие "edit", преобразуем поле вывода в поле для ввода
        if ($this->current_action() == 'edit') {
            return '<textarea name="data[' . $column_name. ']">'.$item[$column_name].'</textarea>';
        } else {
            return $item[$column_name];
        }        
    }

    
    public function get_sortable_columns() {
        return array(
            'full_name'         => 'full_name',
            'short_name'        => 'short_name',
            'creation_date'     => 'creation_date',
            'founder'           => 'founder',
            'location'          => 'location',
            'branches'          => 'branches',
            'working_hours'     => 'working_hours',
            'contact_phones'    => 'contact_phones',
            'email_addresses'   => 'email_addresses'
        );
    }
    
    public function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Удалить',
            'edit'      => 'Редактировать'
        );
        return $actions;
    }


    
}

class Documents_Table extends WP_List_Table {
    private $subsection_id;
    private $search_field;
    public function __construct($subsection_id, $search_field) {
        parent::__construct(array(
            'singular' => 'reginfoeduorg',
            'plural'   => 'reginfoeduorgs',
            'ajax'     => false
        ));
        $this->subsection_id = $subsection_id;
        $this->search_field = $search_field;
    }
    

    public function get_columns() {
        return array(
            'cb'                => '<input type="checkbox" />',
            'document_name'     => 'Название документа',
            'document_type'     => 'Тип документа',
            'document_link'     => 'Ссылка на документ',
        );
    }  


    public function prepare_items() {
        global $wpdb;
        $search = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : false;

        $do_search = ( $search ) ? $wpdb->prepare(" AND $this->search_field  LIKE '%%%s%%'", $search) : '';

        $data = $wpdb->get_results("SELECT d.id, d.document_name, dt.document_type, d.document_link 
         FROM {$wpdb->prefix}reginfoeduorg_documents as d
         JOIN {$wpdb->prefix}reginfoeduorg_document_types as dt
         ON d.document_type = dt.id
         WHERE d.subsection_id = '$this->subsection_id'{$do_search}", ARRAY_A);
        
        $this->items = $data;
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
    }


    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="item[]" value="%s" />',
            $item['id']
        );
    }

    public function column_default($item, $column_name) {
        if ($this->current_action() == 'edit') {
            // здесь мы используем id элемента как ключ в массиве $_POST['data']
            return '<textarea name="data[' . $item['id'] . '][' . $column_name . ']">'.$item[$column_name].'</textarea>';
        } else {
            return $item[$column_name];
        }        
    } 


    
    public function get_sortable_columns() {
        return array(
            'document_name'     => 'document_name',
            'document_type'     => 'document_type'
        );
    }
    
    public function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Удалить',
            'edit'      => 'Редактировать'

        );
        return $actions;
    }

    
}

class Management_Structure_Table extends WP_List_Table {
    private $search_field;
    public function __construct($subsection_id, $search_field) {
        parent::__construct(array(
            'singular' => 'reginfoeduorg',
            'plural'   => 'reginfoeduorgs',
            'ajax'     => false
        ));
        $this->search_field = $search_field;
    }
    

    public function get_columns() {
        return array(

            'cb'                => '<input type="checkbox" />',
            'full_name'     => 'ФИО',
            'position'     => 'Должность',
            'start_date'     => 'Дата начала',
            'basis_document'     => 'Основание (документ)',
            'document_date'     => 'Дата документа',
            'document_number'     => 'Номер документа',
            'structure_image_url'     => 'URL изображения структуры',
        );
    }  


    public function prepare_items() {
        global $wpdb;
        $search = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : false;

        $do_search = ( $search ) ? $wpdb->prepare("WHERE $this->search_field LIKE '%%%s%%'", $search) : '';
        $data = $wpdb->get_results("
    SELECT ms.id, s.full_name, s.position, ms.start_date, ms.basis_document, ms.document_date, ms.document_number, ms.structure_image_url 
    FROM {$wpdb->prefix}reginfoeduorg_management_structure ms 
    INNER JOIN {$wpdb->prefix}reginfoeduorg_staff s 
    ON ms.staff_id = s.id {$do_search}", ARRAY_A);
        
        $this->items = $data;
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
    }



    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="item[]" value="%s" />',
            $item['id']
        );
    }

    public function column_default($item, $column_name) {
        // Если выбрано действие "edit", преобразуем поле вывода в поле для ввода
        if ($this->current_action() == 'edit') {
            return '<textarea name="data[' . $column_name. ']">'.$item[$column_name].'</textarea>';
        } else {
            return $item[$column_name];
        }        
    }

    
    public function get_sortable_columns() {
        return array(
            'document_name'     => 'document_name',
            'document_type'     => 'document_type'
        );
    }
    
    public function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Удалить',
            'edit'      => 'Редактировать'
        );
        return $actions;
    }

    
}

class International_Cooperation_Table extends WP_List_Table {
    private $search_field;
    public function __construct($subsection_id, $search_field) {
        parent::__construct(array(
            'singular' => 'reginfoeduorg',
            'plural'   => 'reginfoeduorgs',
            'ajax'     => false
        ));
        $this->search_field = $search_field;
    }
    

    public function get_columns() {
        return array(

            'cb'       => '<input type="checkbox" />',
            'info'     => 'Пункт',
            'value'    => 'Значение'
        );
    }  


    public function prepare_items() {
        global $wpdb;
        $search = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : false;

        $do_search = ( $search ) ? $wpdb->prepare("WHERE $this->search_field LIKE '%%%s%%'", $search) : '';
        $data = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}reginfoeduorg_international_cooperation {$do_search}"), ARRAY_A);
        
        $this->items = $data;
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
    }


    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="item[]" value="%s" />',
            $item['id']
        );
    }

    public function column_default($item, $column_name) {
        // Если выбрано действие "edit", преобразуем поле вывода в поле для ввода
        if ($this->current_action() == 'edit') {
            return '<textarea name="data[' . $column_name. ']">'.$item[$column_name].'</textarea>';
        } else {
            return $item[$column_name];
        }        
    }

    
    public function get_sortable_columns() {
        return array(
            'info'     => 'info'
        );
    }
    
    public function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Удалить',
            'edit'      => 'Редактировать'
        );
        return $actions;
    }    
}

class Staff_Table extends WP_List_Table {
    private $search_field;
    public function __construct($subsection_id, $search_field) {
        parent::__construct(array(
            'singular' => 'reginfoeduorg',
            'plural'   => 'reginfoeduorgs',
            'ajax'     => false
        ));
        $this->search_field = $search_field;
    }
    

    public function get_columns() {
        return array(
            'cb'                            => '<input type="checkbox" />',
            'full_name'                     => 'ФИО',
            'position'                      => 'Должность',
            'email'                         => 'Email',
            'phone'                         => 'Телефон',
            'specialization'                => 'Специальность',
            'education'                     => 'Информация об образовании',
            'career'                        => 'Информация о карьере',
            'disciplines'                   => 'Дисциплины',
            'qualification_improvement'     => 'Повышение квалификации',
            'overall_experience'            => 'Общий стаж',
            'specialization_experience'     => 'Стаж по специализации',
            'small_image_url'               => 'Ссылка на мелкое фото сотрудника',
            'big_image_url'                 => 'Ссылка на крупное фото сотрудника'
        );
    }  
    


    public function prepare_items() {
        global $wpdb;
        $search = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : false;

        $do_search = ( $search ) ? $wpdb->prepare("WHERE $this->search_field LIKE '%%%s%%'", $search) : '';
        $data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_staff {$do_search}", ARRAY_A);
        
        $this->items = $data;
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
    }


    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="item[]" value="%s" />',
            $item['id']
        );
    }

    public function column_default($item, $column_name) {
        // Если выбрано действие "edit", преобразуем поле вывода в поле для ввода
        if ($this->current_action() == 'edit') {
            return '<textarea name="data[' . $item['id'] . '][' . $column_name. ']">'.$item[$column_name].'</textarea>';
        } else {
            return $item[$column_name];
        }        
    }

    
    public function get_sortable_columns() {
        return array(
            'full_name'     => 'full_name'
        );
    }
    
    public function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Удалить',
            'edit'      => 'Редактировать'

        );
        return $actions;
    }    
}

class Education_Programs_Table extends WP_List_Table {

    public function __construct($subsection_id, $search_field) {
        parent::__construct(array(
            'singular' => 'reginfoeduorg',
            'plural'   => 'reginfoeduorgs',
            'ajax'     => false
        ));
    }
    



    public function get_columns() {
        return array(
            'cb'       => '<input type="checkbox" />',
            'major_group'    => 'Группа направлений',
            'training_program'  => 'Программа обучения',
            'level_of_training'    => 'Уровень обучения',
            'qualification' => 'Квалификация',
            'form_of_education' => 'Форма обучения',
            'term_based_on_9_class' => 'Срок обучения на основе 9 классов',
            'term_based_on_11_class' => 'Срок обучения на основе 11 классов',
            'study_group_prefix' => 'Префикс учебной группы'
        );
    }  
    
    public function prepare_items() {
        global $wpdb;
        $search = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : false;

        $do_search = ( $search ) ? $wpdb->prepare("WHERE major_group LIKE '%%%s%%'", $search) : '';
        $data = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}reginfoeduorg_education_programs {$do_search}"), ARRAY_A);
        
        $this->items = $data;
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
    }


    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="item_education_programs[]" value="%s" />',
            $item['id']
        );
    }

    public function column_default($item, $column_name) {
        // Если выбрано действие "edit", преобразуем поле вывода в поле для ввода
        if ($this->current_action() == 'edit_education_programs') {
            return '<textarea name="data_education_programs[' . $item['id'] . '][' . $column_name. ']">'.$item[$column_name].'</textarea>';
        } else {
            return $item[$column_name];
        }        
    }

    
    public function get_sortable_columns() {
        return array(
            'major_group'     => 'major_group'
        );
    }
    
    public function get_bulk_actions() {
        $actions = array(
            'delete_education_programs'    => 'Удалить',
            'edit_education_programs'      => 'Редактировать'
        );
        return $actions;
    }    
}

class Accreditation_Table extends WP_List_Table {

    public function __construct($subsection_id, $search_field) {
        parent::__construct(array(
            'singular' => 'reginfoeduorg',
            'plural'   => 'reginfoeduorgs',
            'ajax'     => false
        ));
    }

    public function get_columns() {
        return array(
            'cb'       => '<input type="checkbox" />',
            'date_end'    => 'Дата окончания аккредитации',
            'detail'  => 'Дополнительное описание'
        );
    }  
    
    public function prepare_items() {
        global $wpdb;
        $search = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : false;

        $do_search = ( $search ) ? $wpdb->prepare("WHERE major_group LIKE '%%%s%%'", $search) : '';
        $data = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}reginfoeduorg_accreditation {$do_search}"), ARRAY_A);
        
        $this->items = $data;
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
    }


    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="item_accreditation[]" value="%s" />',
            $item['id']
        );
    }

    public function column_default($item, $column_name) {
        // Если выбрано действие "edit", преобразуем поле вывода в поле для ввода
        if ($this->current_action() == 'edit_accreditation') {
            return '<textarea name="data_accreditation[' . $item['id'] . '][' . $column_name. ']">'.$item[$column_name].'</textarea>';
        } else {
            return $item[$column_name];
        }        
    }

    
    public function get_sortable_columns() {
        return array(
            'major_group'     => 'major_group'
        );
    }
    
    public function get_bulk_actions() {
        $actions = array(
            'delete_accreditation'    => 'Удалить',
            'edit_accreditation'      => 'Редактировать'

        );
        return $actions;
    }    
}

class Directions_Results_Scientific_Table extends WP_List_Table {

    public function __construct($subsection_id, $search_field) {
        parent::__construct(array(
            'singular' => 'reginfoeduorg',
            'plural'   => 'reginfoeduorgs',
            'ajax'     => false
        ));
    }

    public function get_columns() {
        return array(
            'cb'       => '<input type="checkbox" />',
            'detail'    => 'Информация'
        );
    }  

    public function prepare_items() {
        global $wpdb;
        $search = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : false;

        $do_search = ( $search ) ? $wpdb->prepare("WHERE major_group LIKE '%%%s%%'", $search) : '';
        $data = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}reginfoeduorg_directions_results_scientific {$do_search}"), ARRAY_A);
        
        $this->items = $data;
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
    }


    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="item_directions_results_scientific[]" value="%s" />',
            $item['id']
        );
    }

    public function column_default($item, $column_name) {
        // Если выбрано действие "edit", преобразуем поле вывода в поле для ввода
        if ($this->current_action() == 'edit_directions_results_scientific') {
            return '<textarea name="data_directions_results_scientific[' . $item['id'] . '][' . $column_name. ']">'.$item[$column_name].'</textarea>';
        } else {
            return $item[$column_name];
        }        
    }

    
    public function get_sortable_columns() {
        return array(
            'major_group'     => 'major_group'
        );
    }
    
    public function get_bulk_actions() {
        $actions = array(
            'delete_directions_results_scientific'    => 'Удалить',
            'edit_directions_results_scientific'      => 'Редактировать'
        );
        return $actions;
    }    
}

class Contingent_Table extends WP_List_Table {

    public function __construct($subsection_id,$search_field) {
        parent::__construct(array(
            'singular' => 'reginfoeduorg',
            'plural'   => 'reginfoeduorgs',
            'ajax'     => false
        ));
    }

    public function get_columns() {
        return array(
            'cb'       => '<input type="checkbox" />',
            'type_education'    => 'Тип обучения',
            'name'    => 'Наименование',
            'budget'    => 'Количество мест на бюджете',
            'contract'    => 'Количество мест на договорной основе',
            'foreigners'    => 'Количество иностранных граждан'
        );
    }  

    public function prepare_items() {
        global $wpdb;
        $search = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : false;

        $do_search = ( $search ) ? $wpdb->prepare("WHERE name LIKE '%%%s%%'", $search) : '';
        $data = $wpdb->get_results("SELECT contingent.*, te.type_education FROM {$wpdb->prefix}reginfoeduorg_contingent contingent JOIN {$wpdb->prefix}reginfoeduorg_type_education te ON contingent.type_education = te.id {$do_search}", ARRAY_A);

        
        $this->items = $data;
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
    }


    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="item_contingent[]" value="%s" />',
            $item['id']
        );
    }

    public function column_default($item, $column_name) {
        // Если выбрано действие "edit", преобразуем поле вывода в поле для ввода
        if ($this->current_action() == 'edit_contingent') {
            return '<textarea name="data_contingent[' . $item['id'] . '][' . $column_name. ']">'.$item[$column_name].'</textarea>';
        } else {
            return $item[$column_name];
        }        
    }

    
    public function get_sortable_columns() {
        return array(
            'major_group'     => 'major_group'
        );
    }
    
    public function get_bulk_actions() {
        $actions = array(
            'delete_contingent'    => 'Удалить',
            'edit_contingent'      => 'Редактировать'
        );
        return $actions;
    }    
}

class Resources_Table extends WP_List_Table {

    public function __construct($subsection_id,$search_field) {
        parent::__construct(array(
            'singular' => 'reginfoeduorg',
            'plural'   => 'reginfoeduorgs',
            'ajax'     => false
        ));
    }

    public function get_columns() {
        return array(
            'cb'       => '<input type="checkbox" />',
            'resource_name'    => 'Название ресурса',
            'type_name'    => 'Тип ресурса',
            'details'    => 'Детали'
        );
    }  

    public function prepare_items() {
        global $wpdb;
        $search = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : false;

        $do_search = ( $search ) ? $wpdb->prepare("WHERE resource_name LIKE '%%%s%%'", $search) : '';
        $data = $wpdb->get_results("SELECT resources.id, resources.resource_name, resource_types.type_name, resources.details FROM {$wpdb->prefix}reginfoeduorg_resources resources LEFT JOIN {$wpdb->prefix}reginfoeduorg_resource_types resource_types ON resources.resource_type = resource_types.id {$do_search}", ARRAY_A);
        $this->items = $data;
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
    }


    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="item_resources[]" value="%s" />',
            $item['id']
        );
    }

    public function column_default($item, $column_name) {
        // Если выбрано действие "edit", преобразуем поле вывода в поле для ввода
        if ($this->current_action() == 'edit_resources') {
            return '<textarea name="data_resources[' . $column_name. ']">'.$item[$column_name].'</textarea>';
        } else {
            return $item[$column_name];
        }        
    }

    
    public function get_sortable_columns() {
        return array(
            'resource_name'     => 'resource_name'
        );
    }
    
    public function get_bulk_actions() {
        $actions = array(
            'delete_resources'    => 'Удалить',
            'edit_resources'      => 'Редактировать'

        );
        return $actions;
    }    
}

class Special_Conditions_Table extends WP_List_Table {
    private $search_field;
    public function __construct($subsection_id, $search_field) {       
        parent::__construct(array(
            'singular' => 'reginfoeduorg',
            'plural'   => 'reginfoeduorgs',
            'ajax'     => false
        ));
        $this->search_field = $search_field;
    }

    public function get_columns() {
        return array(
            'cb'       => '<input type="checkbox" />',
            'info'    => 'Пункт',
            'value'    => 'Значение'
        );
    }  

    public function prepare_items() {
        global $wpdb;
        $search = ( isset( $_REQUEST[$this->search_field] ) ) ? $_REQUEST[$this->search_field] : false;

        $do_search = ( $search ) ? $wpdb->prepare("WHERE LOWER($this->search_field) LIKE LOWER('%%%s%%')", $search) : '';
        
        $data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_special_conditions {$do_search}", ARRAY_A);

        $this->items = $data;
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
    }


    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="item_special_conditions[]" value="%s" />',
            $item['id']
        );
    }

    public function column_default($item, $column_name) {
        if ($this->current_action() == 'edit_special_conditions') {
            return '<textarea name="data_special_conditions[' . $column_name. ']">'.$item[$column_name].'</textarea>';
        } else {
            return $item[$column_name];
        }        
    }

    
    public function get_sortable_columns() {
        return array(
            'info'     => 'info'
        );
    }
    
    public function get_bulk_actions() {
        $actions = array(
            'delete_special_conditions'    => 'Удалить',
            'edit_special_conditions'      => 'Редактировать'
        );
        return $actions;
    }    
}


class RegInfoEduOrg
{
    private $plugin_file;
    
    //Конструктор
    public function __construct($plugin_file) 
    {
        $this->plugin_file = $plugin_file;

        register_activation_hook($this->plugin_file, array($this, 'create_custom_tables'));
        add_action('init', array($this, 'my_plugin_add_sections'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('init', array($this,'create_staff_post_type'));
        //Шорткоды
        add_shortcode('general_info', array($this,'general_info_shortcode'));
        add_shortcode('documents_info', array($this,'documents_info_shortcode'));
        add_shortcode('paid_services_info', array($this,'paid_services_info_shortcode'));
        add_shortcode('financial_activity_info', array($this,'financial_activity_info_shortcode'));
        add_shortcode('vacancies_info', array($this,'vacancies_info_shortcode'));
        add_shortcode('grants_support_info', array($this,'grants_support_info_shortcode'));
        add_shortcode('employees_info', array($this,'employees_info_shortcode'));
        add_shortcode('staff_info', array($this,'staff_info_shortcode'));
        add_shortcode('education_info', array($this,'education_info_shortcode'));
        add_shortcode('management_structure_info', array($this,'management_structure_info_shortcode'));
        add_shortcode('resources_info', array($this,'resources_info_shortcode'));
        add_shortcode('international_cooperation_info', array($this,'international_cooperation_info_shortcode'));
        add_shortcode('special_conditions_info', array($this,'special_conditions_info_shortcode'));
        add_shortcode('education_standarts_info', array($this,'education_standarts_info_shortcode'));
    }
    
    //-------------------------------Шорткоды-------------------------------

    public function process_shortcode($atts, $subsection_name, $style_type, $shortcode) {
        if(!$atts || !$subsection_name || !$style_type || !$shortcode)
        {
            return;
        }
        global $wpdb;

        // Извлекаем ID из атрибутов шорткода
        $id = $atts['id'];
        
        $subsection_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE name = '$subsection_name'");
        
        $xml = $this->generate_xml($subsection_id, $shortcode, $id);
        
        if (!$xml) {
            return null;
        }

        $xslt_code = $wpdb->get_var("SELECT xslt FROM {$wpdb->prefix}reginfoeduorg_site_subsection_styles WHERE subsection_id = '$subsection_id' and style_type = '$style_type'");

        // Преобразуем XML контент в HTML с использованием вашего XSLT-преобразования
        $html_content =  $this->convert_xml_xslt_to_html($xml, $xslt_code,$subsection_id);

        // Возвращаем HTML-контент, который заменит шорткод на странице
        return $html_content;
    }
    


    public function general_info_shortcode($atts) {
        return $this->process_shortcode($atts, 'Основные сведения', 'overview', 'general_info');
    }

    public function documents_info_shortcode($atts) {
        return $this->process_shortcode($atts, 'Документы', 'overview', 'documents_info');
    }

    public function paid_services_info_shortcode($atts) {
        return $this->process_shortcode($atts, 'Платные образовательные услуги', 'overview', 'paid_services_info');
    }
    
    public function financial_activity_info_shortcode($atts) {
        return $this->process_shortcode($atts, 'Финансово-хозяйственная деятельность', 'overview', 'financial_activity_info');
    }
    
    public function vacancies_info_shortcode($atts) {
        return $this->process_shortcode($atts, 'Вакантные места для приема (перевода) обучающихся', 'overview', 'vacancies_info');
    }
    
    public function grants_support_info_shortcode($atts) {
        return $this->process_shortcode($atts, 'Стипендии и иные виды материальной поддержки', 'overview', 'grants_support_info');
    } 

    public function employees_info_shortcode($atts) {
        return $this->process_shortcode($atts, 'Руководство. Педагогический (научно-педагогический) состав', 'overview', 'employees_info');
    }  
    
    public function staff_info_shortcode($atts) {
        return $this->process_shortcode($atts, 'Руководство. Педагогический (научно-педагогический) состав', 'detail', 'staff_info');
    }
    
    public function education_info_shortcode($atts) {
        return $this->process_shortcode($atts, 'Образование', 'overview', 'education_info');
    } 
    
    public function management_structure_info_shortcode($atts) {
        return $this->process_shortcode($atts, 'Структура и органы управления образовательной организацией', 'overview', 'management_structure_info');
    }  
    
    public function resources_info_shortcode($atts) {
        return $this->process_shortcode($atts, 'Материально-техническое обеспечение и оснащенность образовательного процесса', 'overview', 'resources_info');
    } 
    
    public function international_cooperation_info_shortcode($atts) {
        return $this->process_shortcode($atts, 'Международное сотрудничество', 'overview', 'international_cooperation_info');
    } 
    
    public function special_conditions_info_shortcode($atts) {
        return $this->process_shortcode($atts, 'Доступная среда', 'overview', 'special_conditions_info');
    } 

    public function education_standarts_info_shortcode($atts) {
        return $this->process_shortcode($atts, 'Образовательные стандарты и требования', 'overview', 'education_standarts_info');
    } 

    

    //-------------------------------Шорткоды-------------------------------

    //-------------------------------Обработка вывода html документов при активации шорткодов-------------------------------
    function convert_xml_xslt_to_html($xml, $xslt_code, $subsection_id) {
        // Создаем экземпляр XSLTProcessor и загружаем XSLT-код
        if(!$xslt_code || !$xml || !$subsection_id)
        {
            return;
        }
        $xslt_processor = new XSLTProcessor();
        $xslt = new DOMDocument();
        $xslt->loadXML($xslt_code);
        $xslt_processor->importStylesheet($xslt);
        
        // Применяем XSLT-код к XML данным
        $transformed_data = $xslt_processor->transformToXml($xml); // Применяем XSLT-преобразование
        return $transformed_data;
    }

    function generate_xml($subsection_id, $shortcode, $id) {
        if(!$subsection_id)
        {
            return;
        }
        global $wpdb;
        switch ($subsection_id)
        {
            case 1:
                // Выбираем данные из таблицы reginfoeduorg_general_information
                $general_information = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}reginfoeduorg_general_information", ARRAY_A);
                if (!$general_information) {
                    return null;
                }

                // Выбираем пустую структуру XML для подраздела "Основные сведения" из базы данных
                $subsection_xml = $wpdb->get_var("SELECT xml FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE name = 'Основные сведения'");

                // Загружаем пустую структуру XML и дополняем ее данными
                $xml = new DOMDocument('1.0', 'UTF-8');
                $xml->formatOutput = true;
                $xml->loadXML($subsection_xml);

                // Находим элемент general_information для подраздела "Основные сведения"
                $general_information_node = $xml->getElementsByTagName('general_information')->item(0);

                // Добавляем атрибут id к элементу general_information
                $general_information_node->setAttribute('id', $general_information['id']);

                // Создаем элементы для каждого поля из таблицы и обновляем их в general_information
                foreach ($general_information as $key => $value) {
                    $element = $general_information_node->getElementsByTagName($key)->item(0);
                    if ($element) {
                        $element->nodeValue = htmlspecialchars($value);
                    } else {
                        $element = $xml->createElement($key, htmlspecialchars($value));
                        $general_information_node->appendChild($element);
                    }
                }
                break;
            case 2:
                // Выбираем данные из таблицы управления и персонала
                $management_data = $wpdb->get_results("
        SELECT ms.id, s.full_name, s.position, ms.start_date, ms.basis_document, ms.document_date, ms.document_number, ms.structure_image_url 
        FROM {$wpdb->prefix}reginfoeduorg_management_structure ms 
        INNER JOIN {$wpdb->prefix}reginfoeduorg_staff s 
        ON ms.staff_id = s.id", 
                ARRAY_A);
                
                if (!$management_data) {
                    return null;
                }
                $subsection_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE id = %d", $subsection_id));

                // Выбираем пустую структуру XML для подраздела "Документы" из базы данных
                $subsection_xml = $wpdb->get_var("SELECT xml FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE name = '$subsection_name'");
                // Загружаем пустую структуру XML и дополняем ее данными
                $xml = new DOMDocument('1.0', 'UTF-8');
                $xml->formatOutput = true;
                $xml->loadXML($subsection_xml);
                // Находим элемент section_content для подраздела "Управление структурой"
                $section_content = $xml->getElementsByTagName('section_content')->item(0);

                // Удаляем имеющиеся элементы с данными
                while ($section_content->hasChildNodes()) {
                    $section_content->removeChild($section_content->firstChild);
                }

                

                // Проходимся по всему управлению из таблицы и добавляем их в management_structure
                foreach ($management_data as $management) {
                    // Создаем элемент management_structure
                    $management_node = $xml->createElement('management_structure');
                    $section_content->appendChild($management_node);

                    // Создаем элементы full_name, position, start_date, basis_document, document_date, document_number, structure_image_url для каждого manager
                    $full_name = $xml->createElement('full_name', htmlspecialchars($management['full_name']));
                    $position = $xml->createElement('position', htmlspecialchars($management['position']));
                    $start_date = $xml->createElement('start_date', htmlspecialchars($management['start_date']));
                    $basis_document = $xml->createElement('basis_document', htmlspecialchars($management['basis_document']));
                    $document_date = $xml->createElement('document_date', htmlspecialchars($management['document_date']));
                    $document_number = $xml->createElement('document_number', htmlspecialchars($management['document_number']));
                    $structure_image_url = $xml->createElement('structure_image_url', htmlspecialchars($management['structure_image_url']));
                    $management_node->appendChild($full_name);
                    $management_node->appendChild($position);
                    $management_node->appendChild($start_date);
                    $management_node->appendChild($basis_document);
                    $management_node->appendChild($document_date);
                    $management_node->appendChild($document_number);
                    $management_node->appendChild($structure_image_url);
                }
                break;

            case 3:
            case 4:
            case 8:
            case 9:
            case 10:
            case 11:
                // Выбираем данные из таблицы reginfoeduorg_documents
                $documents_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_documents WHERE subsection_id = '$subsection_id'", ARRAY_A);
                if (!$documents_data) {
                    return null;
                }
                $subsection_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE id = %d", $subsection_id));

                // Выбираем пустую структуру XML для подраздела "Документы" из базы данных
                $subsection_xml = $wpdb->get_var("SELECT xml FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE name = '$subsection_name'");
                
                // Загружаем пустую структуру XML и дополняем ее данными
                $xml = new DOMDocument('1.0', 'UTF-8');
                $xml->formatOutput = true;
                $xml->loadXML($subsection_xml);
                // Находим элемент section_content для подраздела "Документы"
                $section_content = $xml->getElementsByTagName('section_content')->item(0);

                // Удаляем имеющиеся элементы с данными
                while ($section_content->hasChildNodes()) {
                    $section_content->removeChild($section_content->firstChild);
                }

                // Создаем элемент documents
                $documents_node = $xml->createElement('documents');
                $section_content->appendChild($documents_node);

                // Проходимся по всем документам из таблицы и добавляем их в documents
                foreach ($documents_data as $document) {
                    // Создаем элемент document
                    $document_node = $xml->createElement('document');
                    $documents_node->appendChild($document_node);

                    // Получаем тип документа
                    $document_type_id = $document['document_type'];
                    $document_type = $wpdb->get_var("SELECT document_type FROM {$wpdb->prefix}reginfoeduorg_documents_types  WHERE id = $document_type_id");

                    // Создаем элементы type, name и link для каждого документа
                    $type = $xml->createElement('type', htmlspecialchars($document_type));
                    $name = $xml->createElement('name', htmlspecialchars($document['document_name']));
                    $link = $xml->createElement('link', htmlspecialchars($document['document_link']));
                    $document_node->appendChild($type);
                    $document_node->appendChild($name);
                    $document_node->appendChild($link);

                }
                break;
            case 5:
                // Выбираем данные из различных таблиц
                $education_programs_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_education_programs", ARRAY_A);
                $accreditation_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_accreditation", ARRAY_A);
                $scientific_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_directions_results_scientific", ARRAY_A);
                $contingent_data = $wpdb->get_results("SELECT contingent.*, te.type_education FROM {$wpdb->prefix}reginfoeduorg_contingent contingent JOIN {$wpdb->prefix}reginfoeduorg_type_education te ON contingent.type_education = te.id", ARRAY_A);

                // Загружаем пустую структуру XML и дополняем ее данными
                $xml = new DOMDocument('1.0', 'UTF-8');
                $xml->formatOutput = true;

                // Создаем корневой элемент
                $root = $xml->createElement('section_content');
                $xml->appendChild($root);

                // Создаем подразделы для каждого типа данных
                $education_programs_node = $xml->createElement('educational_programs');
                $accreditation_node = $xml->createElement('accreditation');
                $scientific_node = $xml->createElement('scientific_activity');
                $contingent_node = $xml->createElement('contingent');
                
                // Присоединяем подразделы к корневому элементу
                $root->appendChild($education_programs_node);
                $root->appendChild($accreditation_node);
                $root->appendChild($scientific_node);
                $root->appendChild($contingent_node);

                // Проходимся по всем программам из таблицы и добавляем их в education_programs
                foreach ($education_programs_data as $program) {
                    // Создаем элемент program
                    $program_node = $xml->createElement('program');
                    $education_programs_node->appendChild($program_node);

                    // Создаем элементы major_group, training_program, level_of_training и другие для каждой программы
                    foreach ($program as $key => $value) {
                        $program_item = $xml->createElement($key, htmlspecialchars($value));
                        $program_node->appendChild($program_item);
                    }
                }

                // Массивы данных и узлов
                $data_array = [$accreditation_data, $scientific_data, $contingent_data];
                $node_array = [$accreditation_node, $scientific_node, $contingent_node];

                // Перебираем данные и узлы
                for ($i = 0; $i < count($data_array); $i++) {
                    foreach ($data_array[$i] as $item) {
                        $item_node = $xml->createElement('program');
                        $node_array[$i]->appendChild($item_node);

                        foreach ($item as $key => $value) {
                            $item_element = $xml->createElement($key, htmlspecialchars($value));
                            $item_node->appendChild($item_element);
                        }
                    }
                }

                break;

            case 6:  // номер подраздела для страницы сотрудников
                // Выбираем данные из таблицы wp_reginfoeduorg_staff
                if ($shortcode == 'employees_info') {
                    $staff_information = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_staff", ARRAY_A);
                }  
                if ($shortcode == 'staff_info') {
                    $staff_information = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_staff WHERE id = '$id'", ARRAY_A);
                }

                if (!$staff_information) {
                    return null;
                }
                
                // Выбираем пустую структуру XML для подраздела "Сотрудники" из базы данных
                $subsection_xml = $wpdb->get_var("SELECT xml FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE id = '$subsection_id'");
                // Загружаем пустую структуру XML и дополняем ее данными
                $xml = new DOMDocument('1.0', 'UTF-8');
                $xml->formatOutput = true;
                $xml->loadXML($subsection_xml);
                
                // Находим элемент section_content для подраздела "Сотрудники"
                $section_content_node = $xml->getElementsByTagName('section_content')->item(0);
                
                // Удаляем шаблонный элемент staff
                $old_staff_node = $xml->getElementsByTagName('staff')->item(0);
                $section_content_node->removeChild($old_staff_node);

                // Добавляем элементы для каждого сотрудника из таблицы
                foreach ($staff_information as $staff) {
                    // Создаем новый элемент staff и добавляем его в section_content
                    $staff_node = $xml->createElement('staff');
                    $section_content_node->appendChild($staff_node);

                    // Добавляем атрибут id к элементу staff
                    $staff_node->setAttribute('id', $staff['id']);

                    // Создаем элементы для каждого поля сотрудника и обновляем их в staff
                    foreach ($staff as $key => $value) {
                        $element = $xml->createElement($key, htmlspecialchars($value));
                        $staff_node->appendChild($element);
                    }
                }
                break;
            case 7:
                // Выбираем данные из таблицы reginfoeduorg_resources
                $resources_data = $wpdb->get_results("SELECT resources.resource_name, resource_types.type_name, resources.details FROM {$wpdb->prefix}reginfoeduorg_resources resources LEFT JOIN {$wpdb->prefix}reginfoeduorg_resource_types resource_types ON resources.resource_type = resource_types.id", ARRAY_A);
                // Выбираем данные из таблицы reginfoeduorg_documents
                $documents_data = $wpdb->get_results("SELECT d.document_name, dt.document_type, d.document_link FROM {$wpdb->prefix}reginfoeduorg_documents d JOIN {$wpdb->prefix}reginfoeduorg_document_types dt ON d.document_type = dt.id WHERE subsection_id = '$subsection_id'", ARRAY_A);

                // Загружаем пустую структуру XML и дополняем ее данными
                $xml = new DOMDocument('1.0', 'UTF-8');
                $xml->formatOutput = true;

                // Создаем корневой элемент
                $root = $xml->createElement('section');
                $xml->appendChild($root);

                // Создаем элементы section_title и section_content
                $section_title = $xml->createElement('section_title', 'Материально-техническое обеспечение и оснащенность образовательного процесса');
                $section_content = $xml->createElement('section_content');
                $root->appendChild($section_title);
                $root->appendChild($section_content);

                // Создаем элемент technical_equipment и добавляем его в section_content
                $technical_equipment = $xml->createElement('technical_equipment');
                $section_content->appendChild($technical_equipment);

                // Проходимся по всем ресурсам из таблицы и добавляем их в technical_equipment
                foreach ($resources_data as $resource) {
                    $equipment_node = $xml->createElement('equipment');
                    $technical_equipment->appendChild($equipment_node);

                    $resource_type = $xml->createElement('resource_type', htmlspecialchars($resource['type_name']));
                    $name = $xml->createElement('name', htmlspecialchars($resource['resource_name']));
                    $details = $xml->createElement('details', htmlspecialchars($resource['details']));

                    $equipment_node->appendChild($resource_type);
                    $equipment_node->appendChild($name);
                    $equipment_node->appendChild($details);
                }

                // Создаем элемент documents и добавляем его в section_content
                $documents = $xml->createElement('documents');
                $section_content->appendChild($documents);

                // Проходимся по всем документам из таблицы и добавляем их в documents
                foreach ($documents_data as $document) {
                    $document_node = $xml->createElement('document');
                    $documents->appendChild($document_node);

                    $name = $xml->createElement('name', htmlspecialchars($document['document_name']));
                    $document_type = $xml->createElement('document_type', htmlspecialchars($document['document_type']));
                    $link = $xml->createElement('link', htmlspecialchars($document['document_link']));

                    $document_node->appendChild($name);
                    $document_node->appendChild($document_type);
                    $document_node->appendChild($link);
                }
                break;
            case 12:
                // Выбираем данные из таблицы reginfoeduorg_documents
                $documents_data = $wpdb->get_results("SELECT d.document_name, dt.document_type, d.document_link FROM {$wpdb->prefix}reginfoeduorg_documents d JOIN {$wpdb->prefix}reginfoeduorg_document_types dt ON d.document_type = dt.id WHERE subsection_id = '$subsection_id'", ARRAY_A);

                // Выбираем данные из таблицы reginfoeduorg_special_conditions
                $conditions_data = $wpdb->get_results("SELECT conditions.info, conditions.value FROM {$wpdb->prefix}reginfoeduorg_special_conditions conditions", ARRAY_A);

                // Загружаем пустую структуру XML и дополняем ее данными
                $xml = new DOMDocument('1.0', 'UTF-8');
                $xml->formatOutput = true;

                // Создаем корневой элемент
                $root = $xml->createElement('section');
                $xml->appendChild($root);

                // Создаем элементы section_title и section_content
                $section_title = $xml->createElement('section_title', 'Доступная среда');
                $section_content = $xml->createElement('section_content');
                $root->appendChild($section_title);
                $root->appendChild($section_content);

                $accessible_environment= $xml->createElement('accessible_environment');
                $section_content->appendChild($accessible_environment);

                // Создаем элемент documents и добавляем его в section_content
                $documents = $xml->createElement('documents');
                $accessible_environment->appendChild($documents);

                // Проходимся по всем документам из таблицы и добавляем их в documents
                foreach ($documents_data as $document) {
                    $document_node = $xml->createElement('document');
                    $documents->appendChild($document_node);

                    $name = $xml->createElement('name', htmlspecialchars($document['document_name']));
                    $document_type = $xml->createElement('document_type', htmlspecialchars($document['document_type']));
                    $link = $xml->createElement('link', htmlspecialchars($document['document_link']));

                    $document_node->appendChild($name);
                    $document_node->appendChild($document_type);
                    $document_node->appendChild($link);
                }
                
                // Создаем элемент special_conditions и добавляем его в accessible_environment
                $special_conditions = $xml->createElement('special_conditions');
                $accessible_environment->appendChild($special_conditions);

                // Проходимся по всем условиям из таблицы и добавляем их в special_conditions
                foreach ($conditions_data as $condition) {
                    $condition_node = $xml->createElement('option_condition'); // Изменили 'condition' на 'option_condition'
                    $special_conditions->appendChild($condition_node);

                    $info = $xml->createElement('info', htmlspecialchars($condition['info']));
                    $value = $xml->createElement('value', htmlspecialchars($condition['value']));

                    $condition_node->appendChild($info);
                    $condition_node->appendChild($value);
                }


                
                break;

            case 13: // Измените номер дела по своему усмотрению
                // Выбираем данные из таблицы reginfoeduorg_international_cooperation
                $cooperation_data = $wpdb->get_results("SELECT info, value FROM {$wpdb->prefix}reginfoeduorg_international_cooperation", ARRAY_A);

                // Загружаем пустую структуру XML и дополняем ее данными
                $xml = new DOMDocument('1.0', 'UTF-8');
                $xml->formatOutput = true;

                // Создаем корневой элемент
                $root = $xml->createElement('section');
                $xml->appendChild($root);

                // Создаем элементы section_title и section_content
                $section_title = $xml->createElement('section_title', 'Международное сотрудничество');
                $section_content = $xml->createElement('section_content');
                $root->appendChild($section_title);
                $root->appendChild($section_content);

                // Создаем элемент international_cooperation и добавляем его в section_content
                $international_cooperation = $xml->createElement('international_cooperation');
                $section_content->appendChild($international_cooperation);

                // Проходимся по всем элементам из таблицы и добавляем их в international_cooperation
                foreach ($cooperation_data as $cooperation) {
                    $option_cooperation_node = $xml->createElement('option_cooperation');
                    $international_cooperation->appendChild($option_cooperation_node);

                    $info = $xml->createElement('info', htmlspecialchars($cooperation['info']));
                    $value = $xml->createElement('value', htmlspecialchars($cooperation['value']));

                    $option_cooperation_node->appendChild($info);
                    $option_cooperation_node->appendChild($value);
                }
                break;



            default:
                break;
        }
        return $xml;
    }
    //-------------------------------Обработка вывода html документов при активации шорткодов-------------------------------
    
    //Создание post_type для вывода страниц сотрудников 
    function create_staff_post_type() {
        register_post_type('staff',
            array(
                'labels' => array(
                    'name' => 'Сотрудники', // Название типа записи во множественном числе
                    'singular_name' => 'Сотрудник', // Название типа записи в единственном числе
                ),
                'public' => true,
                'has_archive' => true,
                'rewrite' => array('slug' => 'staff'),
            )
        );
    }

    //Создание таблиц для базы данных
    function create_custom_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $table_general_information = $wpdb->prefix . 'reginfoeduorg_general_information';
        $table_addresses = $wpdb->prefix . 'reginfoeduorg_addresses';
        $table_roles = $wpdb->prefix . 'reginfoeduorg_roles';
        $table_role_subsection_access = $wpdb->prefix . 'reginfoeduorg_role_subsection_access';
        $table_role_menu_item_access = $wpdb->prefix . 'reginfoeduorg_role_menu_item_access';
        $table_subsections = $wpdb->prefix . 'reginfoeduorg_subsections';
        $table_users_roles = $wpdb->prefix . 'reginfoeduorg_users_roles';
        $table_site_subsections = $wpdb->prefix . 'reginfoeduorg_site_subsections';
        $table_menu_items = $wpdb->prefix . 'reginfoeduorg_menu_items';
        $table_staff = $wpdb->prefix.'reginfoeduorg_staff';
        $table_documents = $wpdb->prefix . 'reginfoeduorg_documents';
        $table_document_types = $wpdb->prefix . 'reginfoeduorg_document_types';
        $table_education_programs = $wpdb->prefix . 'reginfoeduorg_education_programs';
        $table_styles = $wpdb->prefix.'reginfoeduorg_site_subsection_styles';
        $table_management_structure = $wpdb->prefix.'reginfoeduorg_management_structure';
        $table_resource_types = $wpdb->prefix.'reginfoeduorg_resource_types';
        $table_resources = $wpdb->prefix.'reginfoeduorg_resources'; 
        $table_international_cooperation = $wpdb->prefix.'reginfoeduorg_international_cooperation';
        $table_special_conditions = $wpdb->prefix.'reginfoeduorg_special_conditions';
        $table_contingent = $wpdb->prefix.'reginfoeduorg_contingent';
        $table_type_education = $wpdb->prefix.'reginfoeduorg_type_education';
        $table_accreditation = $wpdb->prefix.'reginfoeduorg_accreditation';
        $table_directions_results_scientific = $wpdb->prefix.'reginfoeduorg_directions_results_scientific';

        $sql = "CREATE TABLE $table_general_information (
                id INT(11) NOT NULL AUTO_INCREMENT,
                full_name TEXT NOT NULL,
                short_name TEXT NOT NULL,
                creation_date DATE NOT NULL,
                founder TEXT NOT NULL,
                location TEXT NOT NULL,
                branches TEXT NOT NULL,
                working_hours TEXT NOT NULL,
                contact_phones TEXT NOT NULL,
                email_addresses TEXT NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;

           CREATE TABLE $table_staff (
                id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(255) NOT NULL,
                position VARCHAR(255) NOT NULL,
                email VARCHAR(255) DEFAULT '',
                phone VARCHAR(255) DEFAULT '',
                disciplines TEXT DEFAULT '',
                education TEXT DEFAULT '',
                specialization TEXT DEFAULT '',
                qualification_improvement TEXT DEFAULT '',
                career TEXT DEFAULT '',
                overall_experience INT NOT NULL,
                specialization_experience INT NOT NULL,
                small_image_url VARCHAR(255),
                big_image_url VARCHAR(255)
            ) $charset_collate;

            
            CREATE TABLE $table_management_structure(
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT,
                start_date DATE,
                basis_document VARCHAR(255),
                document_date DATE,
                document_number VARCHAR(50),
                structure_image_url VARCHAR(255),
                FOREIGN KEY (staff_id) REFERENCES $table_staff(id) 
            );


            CREATE TABLE $table_site_subsections (
                id INT(11) NOT NULL AUTO_INCREMENT,
                name TEXT NOT NULL,
                content LONGTEXT NOT NULL,
                xml LONGTEXT NOT NULL,
                visible BOOLEAN NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;

            CREATE TABLE $table_styles (
                id INT(11) NOT NULL AUTO_INCREMENT,
                subsection_id INT(11) NOT NULL,
                style_type VARCHAR(255) NOT NULL,
                xslt TEXT NOT NULL,
                PRIMARY KEY (id),
                FOREIGN KEY (subsection_id) REFERENCES $table_site_subsections(id)
            );

            CREATE TABLE $table_menu_items (
                id INT(11) NOT NULL AUTO_INCREMENT,
                name TEXT NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;

            CREATE TABLE $table_addresses (
                id INT(11) NOT NULL AUTO_INCREMENT,
                general_information_id INT(11) NOT NULL,
                address_type ENUM('educational_activities', 'structural_subdivisions') NOT NULL,
                address TEXT NOT NULL,
                PRIMARY KEY (id),
                FOREIGN KEY (general_information_id) REFERENCES $table_general_information(id) ON DELETE CASCADE
            ) $charset_collate;

            CREATE TABLE $table_roles (
                id INT(11) NOT NULL AUTO_INCREMENT,
                role_name TEXT NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;

            CREATE TABLE $table_role_subsection_access (
                id INT AUTO_INCREMENT PRIMARY KEY,
                role_id INT,
                subsection_id INT,
                read_permission BOOLEAN,
                write_permission BOOLEAN,
                FOREIGN KEY (role_id) REFERENCES $table_roles(id) ON DELETE CASCADE,
                FOREIGN KEY (subsection_id) REFERENCES $table_site_subsections(id) ON DELETE CASCADE
            ) $charset_collate;

            CREATE TABLE $table_role_menu_item_access (
                id INT AUTO_INCREMENT PRIMARY KEY,
                role_id INT,
                menu_item_id INT,
                read_permission BOOLEAN,
                write_permission BOOLEAN,
                FOREIGN KEY (role_id) REFERENCES $table_roles(id) ON DELETE CASCADE,
                FOREIGN KEY (menu_item_id) REFERENCES $table_menu_items(id) ON DELETE CASCADE
            ) $charset_collate;

            CREATE TABLE $table_users_roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                role_id INT(11) NOT NULL,
                FOREIGN KEY (role_id) REFERENCES $table_roles(id) ON DELETE CASCADE
            ) $charset_collate;

            CREATE TABLE $table_document_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                document_type VARCHAR(255)
            )$charset_collate;

            CREATE TABLE $table_documents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                document_name VARCHAR(255),
                document_type INT(11) NOT NULL,
                FOREIGN KEY (document_type) REFERENCES $table_document_types(id) ON DELETE CASCADE,
                document_link VARCHAR(255),
                subsection_id INT(11) NOT NULL,
                FOREIGN KEY (subsection_id) REFERENCES $table_site_subsections(id) ON DELETE CASCADE
            )$charset_collate;

            CREATE TABLE $table_resource_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type_name VARCHAR(255)
            )$charset_collate;

            CREATE TABLE $table_resources (
                id INT AUTO_INCREMENT PRIMARY KEY,
                resource_name VARCHAR(255),
                resource_type INT,
                details TEXT,
                FOREIGN KEY (resource_type) REFERENCES $table_resource_types(id) ON DELETE CASCADE
            )$charset_collate;

            CREATE TABLE $table_international_cooperation (
              id INT AUTO_INCREMENT PRIMARY KEY,
              info TEXT,
              value TEXT
            )$charset_collate;

            CREATE TABLE $table_special_conditions(
              id INT AUTO_INCREMENT PRIMARY KEY,
              info TEXT,
              value TEXT
            )$charset_collate;

            CREATE TABLE $table_type_education (
                id INT(11) NOT NULL AUTO_INCREMENT,
                type_education TEXT NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;

            CREATE TABLE $table_contingent (
                id INT(11) NOT NULL AUTO_INCREMENT,
                type_education INT(11) NOT NULL,
                name TEXT NOT NULL,
                budget INT(11) NOT NULL,
                contract INT(11) NOT NULL,
                foreigners INT(11) NOT NULL,
                PRIMARY KEY (id),
                FOREIGN KEY (type_education) REFERENCES $table_type_education(id) ON DELETE CASCADE
            ) $charset_collate;

            CREATE TABLE $table_accreditation (
                id INT(11) NOT NULL AUTO_INCREMENT,
                date_end DATE NOT NULL,
                detail TEXT NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;

            CREATE TABLE $table_directions_results_scientific (
                id INT(11) NOT NULL AUTO_INCREMENT,
                detail TEXT NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;

            CREATE TABLE $table_education_programs (
               id INT AUTO_INCREMENT PRIMARY KEY,
               major_group varchar(255) NOT NULL,
               training_program varchar(255) NOT NULL,
               level_of_training varchar(255) NOT NULL,
               qualification varchar(255) NOT NULL,
               form_of_education varchar(255) NOT NULL,
               term_based_on_9_class varchar(255),
               term_based_on_11_class varchar(255),
               study_group_prefix varchar(255)
            )$charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $existing_admin_role = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_roles WHERE role_name = %s", 'Администратор'));

        if ($existing_admin_role == 0) {
            $wpdb->insert($table_roles, array('role_name' => 'Администратор'));
        }

        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $wpdb->insert($table_users_roles, array(
            'user_id' => $user_id,
            'role_id' => 1
            ));
        $site_subsections_data = [
            [ 
            'name' =>'Основные сведения',
            'xml' => '	<section>
    <section_title>Основные сведения</section_title>
    <section_content>
        <general_information>
            <full_name></full_name>
            <short_name></short_name>
            <creation_date></creation_date>
            <founder></founder>
            <location></location>
            <address_educational_activities></address_educational_activities>
            <address_structural_subdivisions></address_structural_subdivisions>
            <branches></branches>
            <working_hours></working_hours>
            <contact_phones></contact_phones>
            <email_addresses></email_addresses>
        </general_information>
    </section_content>
</section>
',
            'xslt' => '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="html" encoding="UTF-8" indent="yes" />
  <xsl:template match="/">
    <html>
      <body>
        <xsl:apply-templates select="//general_information" />
      </body>
    </html>
  </xsl:template>
  <xsl:template match="general_information">
  <general_information id="{@id}">
    <p><strong>Полное название образовательной организации:</strong> <xsl:value-of select="full_name" /></p>
    <p><strong>Краткое название образовательной организации:</strong> <xsl:value-of select="short_name" /></p>
    <p><strong>Дата создания образовательной организации:</strong> <xsl:value-of select="creation_date" /></p>
    <p><strong>Учредитель:</strong> <xsl:value-of select="founder" /></p>
    <p><strong>Место нахождения образовательной организации:</strong> <xsl:value-of select="location" /></p>
    <p><strong>Филиалы образовательной организации:</strong> <xsl:value-of select="branches" /></p>
    <p><strong>График работы:</strong> <xsl:value-of select="working_hours" /></p>
    <p><strong>Контактные телефоны:</strong> <xsl:value-of select="contact_phones" /></p>
    <p><strong>Адреса электронной почты:</strong> <xsl:value-of select="email_addresses" /></p>
</general_information>
  </xsl:template>
</xsl:stylesheet>
',
            'xslt_detail' => ''
            ],
            [ 
            'name' =>'Структура и органы управления образовательной организацией',
            'xml' => '<section>
            <section_title>Структура и органы управления образовательной организацией</section_title>
            <section_content>
                    <management_structure>
                        <full_name></full_name>
                        <position></position>
                        <start_date></start_date>
                        <basis_document></basis_document>
                        <document_date></document_date>
                        <document_number></document_number>
                        <structure_image_url></structure_image_url>
                    </management_structure>
                </section_content>
            </section>',
            'xslt' => '<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="/">
  <html>
  <body>
    <xsl:for-each select="section/section_content/management_structure">
      <p>
        <xsl:text>С </xsl:text>
        <xsl:value-of select="start_date"/>
        <xsl:text> директором учебного заведения является </xsl:text>
        <xsl:value-of select="full_name"/>
        <xsl:text> (</xsl:text>
        <xsl:value-of select="position"/>
        <xsl:text>, на основании </xsl:text>
        <xsl:value-of select="basis_document"/>
        <xsl:text> от </xsl:text>
        <xsl:value-of select="document_date"/>
        <xsl:text> № </xsl:text>
        <xsl:value-of select="document_number"/>
        <xsl:text>).</xsl:text>
      </p>
      <p>
        <img src="{structure_image_url}" alt="structure_image"/>
      </p>
    </xsl:for-each>
  </body>
  </html>
</xsl:template>

</xsl:stylesheet>
',
            'xslt_detail' => ''
            ],
            [ 
            'name' =>'Документы',
            'xml' => '<section>
		<section_title>Документы</section_title>
		<section_content>
			<documents>
				<document>
					<name></name>
					<type></type>
					<link></link>
				</document>
			</documents>
		</section_content>
	</section>',
            'xslt' => '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="html" encoding="UTF-8" indent="yes" />
  
  <xsl:key name="documents-by-type" match="document" use="type" />
  
  <xsl:template match="/">
    <html>
      <body>
        <xsl:for-each select="//documents/document[generate-id() = generate-id(key(\'documents-by-type\', type)[1])]">
          <xsl:sort select="type" />
          <p><strong><xsl:value-of select="type" /></strong></p>
          <xsl:for-each select="key(\'documents-by-type\', type)">
            <p><a href="{link}"><xsl:value-of select="name" /></a></p>
          </xsl:for-each>
        </xsl:for-each>
      </body>
    </html>
  </xsl:template>
  
</xsl:stylesheet>

',
            'xslt_detail' => ''
            ],
            [ 
            'name' =>'Образовательные стандарты и требования',
            'xml' => '<section>
		<section_title>Образовательные стандарты и требования</section_title>
		<section_content>
			<documents>
				<document>
					<name></name>
					<type></type>
					<link></link>
				</document>
			</documents>
		</section_content>
	</section>',
            'xslt' => '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="html" encoding="UTF-8" indent="yes" />
  
  <xsl:key name="documents-by-type" match="document" use="type" />
  
  <xsl:template match="/">
    <html>
      <body>
        <xsl:for-each select="//documents/document[generate-id() = generate-id(key(\'documents-by-type\', type)[1])]">
          <xsl:sort select="type" />
          <p><strong><xsl:value-of select="type" /></strong></p>
          <xsl:for-each select="key(\'documents-by-type\', type)">
            <p><a href="{link}"><xsl:value-of select="name" /></a></p>
          </xsl:for-each>
        </xsl:for-each>
      </body>
    </html>
  </xsl:template>
  
</xsl:stylesheet>',
            'xslt_detail' => ''
            ],
            [ 
            'name' =>'Образование',
            'xml' => '<section>
    <section_title>Образование</section_title>
    <section_content>
        <educational_programs>
            <program>
                <major_group></major_group>
                <training_program></training_program>
                <level_of_training></level_of_training>
                <qualification></qualification>
                <form_of_education></form_of_education>
                <term_based_on_9_class></term_based_on_9_class>
                <term_based_on_11_class></term_based_on_11_class>
                <study_group_prefix></study_group_prefix>
            </program>
        </educational_programs>
        <accreditation>
            <program>
                <date_end></date_end>
                <detail></detail>
            </program>
        </accreditation>
        <scientific_activity>
            <direction>
                <detail></detail>
            </direction>
        </scientific_activity>
        <contingent>
            <program_type>                
                <type_education></type_education>
                <name></name><budget></budget><contract></contract><foreigners></foreigners>
            </program_type>
        </contingent>
    </section_content>
</section>',
            'xslt' => '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" />
<xsl:key name="groups" match="program" use="major_group" />
<xsl:key name="education-groups" match="program" use="type_education" />

<xsl:template match="/section_content">
<h3>Образовательные программы</h3>    
<p>Срок действия государственной аккредитации образовательных программ: до 
        <xsl:value-of select="substring(accreditation/program/date_end, 9, 2)" />.<xsl:value-of select="substring(accreditation/program/date_end, 6, 2)" />.<xsl:value-of select="substring(accreditation/program/date_end, 1, 4)" />.
    </p>
    <p><xsl:value-of select="accreditation/program/detail" /></p>
    
    <xsl:apply-templates select="educational_programs"/>
<p><strong>О направлениях и результатах научной (научно-исследовательской) деятельности (при осуществлении научной (научно-исследовательской) деятельности):</strong></p>
    <p><xsl:value-of select="scientific_activity/program/detail" /></p>
   <xsl:apply-templates select="contingent"/>
</xsl:template>

<xsl:template match="contingent">
    <h3>Контингент</h3>
<p>Численность обучающихся по реализуемым образовательным программам (федерального бюджета, бюджетов субъектов Российской Федерации, местных бюджетов и по договорам об образовании за счет средств физических и (или) юридических лиц):</p>
<p>Всего: <xsl:value-of select="sum(program/budget) + sum(program/contract)"/></p>
    <xsl:for-each select="program[generate-id() = generate-id(key(\'education-groups\', type_education)[1])]">
        <xsl:variable name="current-group" select="type_education" />
        <p>
            <strong><xsl:value-of select="$current-group"/> 
            (<xsl:value-of select="sum(key(\'education-groups\', $current-group)/budget) + sum(key(\'education-groups\', $current-group)/contract)"/>)</strong>
            <xsl:for-each select="key(\'education-groups\', $current-group)">
                <p>
                    <xsl:value-of select="name"/> 
                    (<xsl:value-of select="number(budget) + number(contract)"/>): 
                    <xsl:value-of select="total_students"/><br/>
                    - Бюджет: <xsl:value-of select="budget"/><br/>
                    - На договорной основе: <xsl:value-of select="contract"/>
                </p>
            </xsl:for-each>
        </p>
    </xsl:for-each>
<p>Численность обучающихся, являющихся иностранными гражданами: <xsl:value-of select="sum(program/foreigners)"/></p>
</xsl:template>
<xsl:template match="educational_programs">
    <table>
        <style>
           table {
                border-collapse: collapse;
                width: 100%;
            }
            td {
                padding: 8px;
                border: 1px solid silver;
            }
            tr:first-child td, tr:nth-child(2) td {
                font-weight: bold;
            }
        </style>
        <tbody>
            <tr>
                <td rowspan="2">Укрупненная группа специальностей</td>
                <td rowspan="2">Программа обучения</td>
                <td rowspan="2">Уровень обучения</td>
                <td rowspan="2">Квалификация</td>
                <td rowspan="2">Форма обучения</td>
                <td colspan="2">Срок получения среднего профессионального образования</td>
                <td rowspan="2">Префикс учебной группы</td>
            </tr>
            <tr>
                <td>на базе 9 кл.</td>
                <td>на базе 11 кл.</td>
            </tr>
            <xsl:for-each select="program[generate-id() = generate-id(key(\'groups\', major_group)[1])]">
                <xsl:variable name="current-group" select="major_group" />
                <xsl:variable name="rowspan" select="count(key(\'groups\', $current-group))" />
                <xsl:apply-templates select="key(\'groups\', $current-group)[1]" />
                <xsl:apply-templates select="key(\'groups\', $current-group)[position() > 1]" />
            </xsl:for-each>
        </tbody>
    </table>
</xsl:template>

<xsl:template match="program">
    <tr>
        <xsl:if test="generate-id() = generate-id(key(\'groups\', major_group)[1])">
            <td rowspan="{count(key(\'groups\', major_group))}">
                <xsl:value-of select="major_group" />
            </td>
        </xsl:if>
        <td><xsl:value-of select="training_program" /></td>
        <td><xsl:value-of select="level_of_training" /></td>
        <td><xsl:value-of select="qualification" /></td>
        <td><xsl:value-of select="form_of_education" /></td>
        <td><xsl:value-of select="term_based_on_9_class" /></td>
        <td><xsl:value-of select="term_based_on_11_class" /></td>
        <td><xsl:value-of select="study_group_prefix" /></td>
    </tr>
</xsl:template>

</xsl:stylesheet>
',
            'xslt_detail' => ''
            ],
            [ 
            'name' =>'Руководство. Педагогический (научно-педагогический) состав',
            'xml' => '<section>
	<section_title>Руководство. Педагогический (научно-педагогический) состав</section_title>
	<section_content>
		<staff >
			<full_name></full_name>
			<position></position>
			<email></email>
			<phone></phone>
			<disciplines></disciplines>
			<education></education>
			<specialization></specialization>
			<qualification_improvement></qualification_improvement>
			<career></career>
			<overall_experience></overall_experience>
			<specialization_experience></specialization_experience>
		</staff>
	</section_content>
</section>',
            'xslt' => '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" />
<xsl:template match="/">

  <style type="text/css">
    .employee-cards {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      text-align: center
    }
    .employee-card {
      flex: 0 0 calc(33% - 20px);
      width: 205px;
      height: 330px;
      margin: 10px;
      padding: 10px;
      box-sizing: border-box;
    }
    .employee-card img {
      width: 151px;
      height: 200px;
      box-shadow: 0px 0px 23px -6px rgba(0,0,0,0.75);
    }
    .employee-card .name {
      font-size: 16px;
      font-weight: bold;
      margin: 10px 0;
    }
    .employee-card .position {
      font-size: 14px;
      color: #666;
      font-style: italic;
    }
  </style>

  <div class="employee-cards">
    <xsl:for-each select="//staff">
      <div class="employee-card">
        <!-- Используем id сотрудника для создания ссылки -->
        <a href="http://example/?staff={id}">
          <img src="{small_image_url}" alt="{full_name}" />
          <div class="name">
            <xsl:value-of select="full_name" />
          </div>
          <div class="position">
            <xsl:value-of select="position" />
          </div>
        </a>
      </div>
    </xsl:for-each>
  </div>

</xsl:template>
</xsl:stylesheet>
',
            'xslt_detail' => '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>
<xsl:template match="/">
    <xsl:for-each select="section/section_content/staff">
<style type="text/css">
 .employee img {
margin: 5px 10px 5px 0;
      width: 344px;
      height: 400px;
      box-shadow: 0px 0px 23px -6px rgba(0,0,0,0.75);
}
.employee {
      display: flex;
}
 </style>
        <div class="employee">
          <img src="{big_image_url}" alt="{full_name}" />
          <div calss="detail">
            <p><strong>Должность:</strong> <xsl:value-of select="position"/></p>
            <p><strong>Дисциплины:</strong> <xsl:value-of select="disciplines"/></p>
            <p><strong>Образование:</strong> <xsl:value-of select="education"/></p>
            <p><strong>Карьера:</strong> <xsl:value-of select="career"/></p>
            <p><strong>Специальность:</strong> <xsl:value-of select="specialization"/></p>
            <p><strong>Повышение квалификации:</strong> <xsl:value-of select="qualification_improvement"/></p>
            <p><strong>Общий стаж (лет):</strong> <xsl:value-of select="overall_experience"/></p>
            <p><strong>Стаж по специальности (лет):</strong> <xsl:value-of select="specialization_experience"/></p>
        </div> 
</div>
    </xsl:for-each>

</xsl:template>
</xsl:stylesheet>
'
            ],
            [ 
            'name' =>'Материально-техническое обеспечение и оснащенность образовательного процесса',
            'xml' => '<section>
    <section_title>Материально-техническое обеспечение и оснащенность образовательного процесса</section_title>
    <section_content>
        <technical_equipment>
		<equipment>
            <resource_type></resource_type>
            <name></name>
            <details></details>
        </equipment>
		</technical_equipment>
        <documents>
            <document>
                <name></name>
                <document_type></document_type>
                <link></link>
            </document>
        </documents>
    </section_content>
</section>',
            'xslt' => '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="html"/>
  <xsl:template match="/section">
    <html>
      <body>
        <xsl:for-each select="section_content/technical_equipment/equipment">
          <h2><xsl:value-of select="name"/></h2>
          <p><xsl:value-of select="details"/></p>
        </xsl:for-each>
<p></p>
        <xsl:for-each select="section_content/documents/document">
          <a href="{link}">
            <xsl:value-of select="name"/>
          </a>
        </xsl:for-each>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>
',
            'xslt_detail' => ''
            ],
            [ 
            'name' =>'Стипендии и иные виды материальной поддержки',
            'xml' => '<section>
		<section_title>Стипендии и иные виды материальной поддержки</section_title>
		<section_content>
			<documents>
				<document>
					<name></name>
					<type></type>
					<link></link>
				</document>
			</documents>
		</section_content>
	</section>',
            'xslt' => '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="html" encoding="UTF-8" indent="yes" />
  <xsl:template match="/">
    <html>
      <body>
        <xsl:apply-templates select="//documents/document" />
      </body>
    </html>
  </xsl:template>
  <xsl:template match="document">
    <p>
      <a href="{link}"><xsl:value-of select="name" /></a>
    </p>
  </xsl:template>
</xsl:stylesheet>',
            'xslt_detail' => ''
            ],
            [ 
            'name' =>'Платные образовательные услуги',
            'xml' => '<section>
		<section_title>Платные образовательные услуги</section_title>
		<section_content>
			<documents>
				<document>
					<name></name>
					<type></type>
					<link></link>
				</document>
			</documents>
		</section_content>
	</section>',
            'xslt' => '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="html" encoding="UTF-8" indent="yes" />
  <xsl:template match="/">
    <html>
      <body>
        <xsl:apply-templates select="//documents/document" />
      </body>
    </html>
  </xsl:template>
  <xsl:template match="document">
    <p>
      <a href="{link}"><xsl:value-of select="name" /></a>
    </p>
  </xsl:template>
</xsl:stylesheet>
',
            'xslt_detail' => ''
            ],
            [ 
            'name' =>'Финансово-хозяйственная деятельность',
            'xml' => '<section>
		<section_title>Финансово-хозяйственная деятельность</section_title>
		<section_content>
			<documents>
				<document>
					<name></name>
					<type></type>
					<link></link>
				</document>
			</documents>
		</section_content>
	</section>',
            'xslt' => '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="html" encoding="UTF-8" indent="yes" />
  <xsl:template match="/">
    <html>
      <body>
        <xsl:apply-templates select="//documents/document" />
      </body>
    </html>
  </xsl:template>
  <xsl:template match="document">
    <p>
      <a href="{link}"><xsl:value-of select="name" /></a>
    </p>
  </xsl:template>
</xsl:stylesheet>',
            'xslt_detail' => ''
            ],
            [ 
            'name' =>'Вакантные места для приема (перевода) обучающихся',
            'xml' => '<section>
		<section_title>Вакантные места для приема (перевода) обучающихся</section_title>
		<section_content>
			<documents>
				<document>
					<name></name>
					<type></type>
					<link></link>
				</document>
			</documents>
		</section_content>
	</section>',
            'xslt' => '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="html" encoding="UTF-8" indent="yes" />
  <xsl:template match="/">
    <html>
      <body>
        <xsl:apply-templates select="//documents/document" />
      </body>
    </html>
  </xsl:template>
  <xsl:template match="document">
    <p>
      <a href="{link}"><xsl:value-of select="name" /></a>
    </p>
  </xsl:template>
</xsl:stylesheet>',
            'xslt_detail' => ''
            ],
            [ 
            'name' =>'Доступная среда',
            'xml' => '<section>
    <section_title>Доступная среда</section_title>
    <section_content>
        <accessible_environment>
            <documents>
                <document>
                    <name></name>
                    <type></type>
                    <link></link>
                </document>
            </documents>
            <special_conditions>
                <option_condition>
                    <info></info>
                    <value></value>
                </option_condition>
            </special_conditions>
        </accessible_environment>
    </section_content>
</section>
',
            'xslt' => '<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="/">
  <html> 
  <body>
    
      <xsl:for-each select="section/section_content/accessible_environment/documents/document">
         <p><a href="{link}"><xsl:value-of select="name"/></a> </p>
      </xsl:for-each>
    
    <p><strong>Информация о специальных условиях для обучения инвалидов и лиц с ограниченными возможностями здоровья</strong></p>
    <table>
<style>
           table {
                border-collapse: collapse;
                width: 100%;
            }
            td {
                padding: 8px;
                border: 1px solid silver;
            }
        </style>
      <tr>
        <td><strong>Специальные условия для обучения инвалидов и лиц с ограниченными возможностями здоровья</strong></td>
        <td><strong>Наличие/отсутствие</strong></td>
      </tr>
      <xsl:for-each select="section/section_content/accessible_environment/special_conditions/option_condition">
        <tr>
          <td><xsl:value-of select="info"/></td>
          <td style="font-weight: bold;"><xsl:value-of select="value"/></td>
        </tr>
      </xsl:for-each>
    </table>
  </body>
  </html>
</xsl:template>

</xsl:stylesheet>
',
            'xslt_detail' => ''
            ],
            [ 
            'name' =>'Международное сотрудничество',
            'xml' => '<section>
		<section_title>Международное сотрудничество</section_title>
		<section_content>
			<international_cooperation>
				<option_cooperation>
					<info></info>
					<value></value>
				</option_cooperation>
			</international_cooperation>
		</section_content>
	</section>',
            'xslt' => '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="/">
  <html>
  <body>
    <xsl:for-each select="section/section_content/international_cooperation/option_cooperation">
      <p>
        <xsl:value-of select="info"/>: <b><xsl:value-of select="value"/></b>.
      </p>
    </xsl:for-each>
  </body>
  </html>
</xsl:template>

</xsl:stylesheet>
',
            'xslt_detail' => ''
            ] 
        ];

        $menu_items_data = [
            'Начальная страница плагина',
            'Настройка присвоения ролей пользователям', 
            'Настройка ролей'
        ];
        
        
        

        foreach ($site_subsections_data as $subsection) {
            $existing_subsection = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_site_subsections WHERE name = %s", $subsection['name']));
            if ($existing_subsection == 0) {
                $wpdb->insert($table_site_subsections, array(
                    'name' => $subsection['name'],
                    'xml' => $subsection['xml'],
                    'visible' => true
                ));
                // Get the ID of the subsection we just inserted
                $subsection_id = $wpdb->insert_id;
                $wpdb->insert("{$wpdb->prefix}reginfoeduorg_site_subsection_styles", array(
                    'subsection_id' => $subsection_id,
                    'style_type' => 'overview',
                    'xslt' => $subsection['xslt']
                    ));  
                if($subsection['xslt_detail'])
                {
                    $wpdb->insert("{$wpdb->prefix}reginfoeduorg_site_subsection_styles", array(
                    'subsection_id' => $subsection_id,
                    'style_type' => 'detail',
                    'xslt' => $subsection['xslt_detail']
                    ));  
                }
            }
        }
        $i = 1;
        foreach ($menu_items_data as $menu_item) {
            $existing_menu_item = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_menu_items WHERE name = %s", $menu_item));
            if ($existing_menu_item == 0) {
                $wpdb->insert($table_menu_items, ['name' => $menu_item]);
                $wpdb->insert($table_role_menu_item_access, array(
                    'role_id' => 1,
                    'menu_item_id' => $i,
                    'read_permission' => 1,
                    'write_permission' => 1,
                    ));
                $i++;
            }
        }
    }

    //Обработка отображения в панели администратора
    function register_settings() 
    {
        register_setting( 'reginfoeduorg', 'reginfoeduorg_options' );
        add_settings_section( 'my-section-id', false, false, 'reginfoeduorg' );
        add_action( 'admin_init', 'my_plugin_users_page_handle' );
    }

    //Подпункты меню
    function add_menu_pages() {
        global $wpdb;

        $subsections = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_site_subsections");

        // Получаем текущего пользователя
        $current_user = wp_get_current_user();

        // Получаем ID роли текущего пользователя
        $user_role_id = $wpdb->get_var($wpdb->prepare("SELECT role_id FROM {$wpdb->prefix}reginfoeduorg_users_roles WHERE user_id = %d", $current_user->ID));

        // Если роль не найдена, вы можете установить значение по умолчанию (например, 1)
        if ($user_role_id === null) {
            $user_role_id = 1;
        }

        // Получаем доступ к подпунктам меню для роли текущего пользователя
        $menu_item_access_data = $wpdb->get_results($wpdb->prepare("SELECT mi.name, mia.read_permission FROM {$wpdb->prefix}reginfoeduorg_menu_items AS mi JOIN {$wpdb->prefix}reginfoeduorg_role_menu_item_access AS mia ON mi.id = mia.menu_item_id WHERE mia.role_id = %d", $user_role_id), OBJECT_K);
        if (isset($menu_item_access_data["Начальная страница плагина"]) && $menu_item_access_data["Начальная страница плагина"]->read_permission == 1) {
            add_menu_page(
                'RegInfoEduOrg',
                'RegInfoEduOrg',
                'manage_options',
                'reginfoeduorg',
                array($this, 'my_plugin_settings_page')
            );
        }
        $submenus = array(
            array(
                'title' => 'Настройка присвоения ролей пользователям',
                'menu_title' => 'Пользователи',
                'slug' => 'my_plugin_users',
                'callback' => array($this, 'my_plugin_users_page')
            ),
            array(
                'title' => 'Настройка ролей',
                'menu_title' => 'Роли',
                'slug' => 'my_plugin_roles',
                'callback' => array($this, 'my_plugin_roles_page')
            )
        );

        foreach ($submenus as $submenu) {
            if (isset($menu_item_access_data[$submenu['title']]) && $menu_item_access_data[$submenu['title']]->read_permission == 1) {
                add_submenu_page(
                    'reginfoeduorg',
                    $submenu['title'],
                    $submenu['menu_title'],
                    'manage_options',
                    $submenu['slug'],
                    $submenu['callback']
                );
            }
        }

        // Получаем доступ к подпунктам меню для роли текущего пользователя
        $menu_item_access_data = $wpdb->get_results($wpdb->prepare("SELECT s.id, rsa.read_permission FROM {$wpdb->prefix}reginfoeduorg_site_subsections AS s JOIN {$wpdb->prefix}reginfoeduorg_role_subsection_access AS rsa ON s.id = rsa.subsection_id WHERE rsa.role_id = %d", $user_role_id), OBJECT_K);


        if (is_array($subsections)) {
            foreach ($subsections as $subsection) {
                $subsection_id = $subsection->id;
                if (isset($menu_item_access_data[$subsection_id]) && $menu_item_access_data[$subsection_id]->read_permission == 1) {
                    $menu_slug = 'reginfoeduorg_subsection_' . $subsection->id;
                    add_submenu_page(
                        'reginfoeduorg',
                        $subsection->name,
                        '- ' . $subsection->name,
                        'read',
                        $menu_slug,
                        array($this, 'reginfoeduorg_display_section_data')
                    );

                }
            }
        }



    }    

    //Проверка наличия подразделов сайта
    function my_plugin_add_sections() 
    {
        global $wpdb;
        $table_site_subsections = $wpdb->prefix . 'reginfoeduorg_site_subsections';

        $page_title = 'Сведения об образовательной организации';
        $page_content = '';
        $parent_id = 0;
        $parent_page = get_page_by_title($page_title);

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

        $visible_subsections = $wpdb->get_results("SELECT name FROM {$table_site_subsections} WHERE visible = 1");

        $checked_subsections = array();
        foreach ($visible_subsections as $subsection) {
            $checked_subsections[] = $subsection->name;
        }

        $all_subsections = $wpdb->get_results("SELECT name FROM {$table_site_subsections}");
        
        foreach ($all_subsections as $child_section) {
            $child_page_title = $child_section->name;
            $child_page_args = array(
                'post_title' => $child_page_title,
                'post_status' => in_array($child_section->name, $checked_subsections) ? 'publish' : 'draft',
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
        }
    }

    //Настройка ролей
    function my_plugin_roles_page() {
        // получаем текущего пользователя
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        global $wpdb;

        // получаем ID роли текущего пользователя из таблицы wp_reginfoeduorg_users_roles
        $user_role_id = $wpdb->get_var($wpdb->prepare("SELECT role_id FROM {$wpdb->prefix}reginfoeduorg_users_roles WHERE user_id = %d", $user_id));


        // проверяем, принадлежит ли текущий пользователь какой-то роли
        if ($user_role_id) {
            // Задаем название подпункта меню, для которого хотим проверить доступ
            $menu_item_name = 'Настройка ролей';

            // Получаем ID подпункта меню по его названию из таблицы wp_reginfoeduorg_menu_items
            $menu_item_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}reginfoeduorg_menu_items WHERE name = %s", $menu_item_name));
            $table_subsection_access = $wpdb->prefix . 'reginfoeduorg_role_subsection_access';

            // получаем список разрешений доступа для текущей роли из таблицы wp_reginfoeduorg_role_menu_item_access
            $access_menu = $wpdb->get_row($wpdb->prepare("SELECT read_permission, write_permission FROM {$wpdb->prefix}reginfoeduorg_role_menu_item_access WHERE role_id = %d AND menu_item_id = %d", $user_role_id, $menu_item_id), ARRAY_A);
            // Получаем список подразделов из базы данных
            global $wpdb;
            $table_site_subsections = $wpdb->prefix . 'reginfoeduorg_site_subsections';
            $sections = $wpdb->get_results("SELECT * FROM $table_site_subsections");
            $subsection_access_data = $wpdb->get_results($wpdb->prepare("SELECT $table_site_subsections.id, $table_site_subsections.name, $table_subsection_access.read_permission, $table_subsection_access.write_permission FROM $table_subsection_access JOIN $table_site_subsections ON $table_site_subsections.id = $table_subsection_access.subsection_id WHERE role_id = %d", $user_role_id));
            foreach ($subsection_access_data as $row) {
                $access_settings[$row->name] = array(
                    'read' => intval($row->read_permission),
                    'edit' => intval($row->write_permission)
                );
            }
            // проверяем, разрешен ли доступ к нужному подразделу меню
            if ($access_menu['read_permission'] == 0) {
                wp_die('У вас нет доступа');
            } 
            elseif ($access_menu['write_permission'] == 0) 
            {
                // Получаем роли из таблицы reginfoeduorg_roles
                global $wpdb;
                $table_roles = $wpdb->prefix . 'reginfoeduorg_roles';
                $roles = $wpdb->get_results("SELECT * FROM {$table_roles}");

                $selected_role = '';

                if (isset($_POST['submit_role'])) {
                    $selected_role = sanitize_text_field($_POST['role']);            
                }
                // Обработка отправленной формы добавления роли
                if (isset($_POST['submit_add_role'])) {
                    $new_role = sanitize_text_field($_POST['add_role']);
                    if (!empty($new_role)) {
                        global $wpdb;
                        $table_roles = $wpdb->prefix . 'reginfoeduorg_roles';

                        // Добавляем новую роль в таблицу reginfoeduorg_roles
                        $result = $wpdb->insert(
                            $table_roles,
                            array(
                                'role_name' => $new_role
                            ),
                            array('%s')
                        );

                        if ($result) {
                            echo '<div class="updated"><p>Роль ' . $new_role . ' успешно добавлена.</p></div>';
                        } else {
                            echo '<div class="error"><p>Не удалось добавить роль ' . $new_role . '. Пожалуйста, попробуйте еще раз.</p></div>';
                        }
                    }
                    echo '<script>window.location.reload();</script>';
                }


                // Обработка отправленной формы настройки доступа к подразделам
                global $wpdb;

                if (isset($_POST['submit_access_settings'])) {
                    $selected_role = sanitize_text_field($_POST['role']);
                    if (!empty($selected_role)) {
                        $table_subsection_access = $wpdb->prefix . 'reginfoeduorg_role_subsection_access';
                        $table_menu_item_access = $wpdb->prefix . 'reginfoeduorg_role_menu_item_access';
                        $table_roles = $wpdb->prefix . 'reginfoeduorg_roles';

                        // Получаем информацию о роли из базы данных
                        $role_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_roles WHERE id = %d", $selected_role));

                        // Удаляем существующие настройки доступа для выбранной роли
                        $wpdb->delete($table_subsection_access, array('role_id' => $selected_role));
                        $wpdb->delete($table_menu_item_access, array('role_id' => $selected_role));

                        $table_subsections = $wpdb->prefix . 'reginfoeduorg_site_subsections';
                        $table_menu_items = $wpdb->prefix . 'reginfoeduorg_menu_items';
                        $subsections = $wpdb->get_results("SELECT * FROM $table_subsections");
                        $menu_items = $wpdb->get_results("SELECT * FROM $table_menu_items");


                        // Сохраняем настройки доступа для подразделов и подпунктов меню
                        foreach ($subsections as $subsection) {
                            $read = isset($_POST['subsection_' . $subsection->id . '_read']) ? 1 : 0;
                            $edit = isset($_POST['subsection_' . $subsection->id . '_edit']) ? 1 : 0;

                            
                            $wpdb->insert($table_subsection_access, array(
                                'role_id' => $selected_role,
                                'subsection_id' => $subsection->id,
                                'read_permission' => $read,
                                'write_permission' => $edit
                            ));
                            
                        }


                        foreach ($menu_items as $menu_item) {
                            $read = isset($_POST['menu_item_' . $menu_item->id . '_read']) ? 1 : 0;
                            $edit = isset($_POST['menu_item_' . $menu_item->id . '_edit']) ? 1 : 0;

                            $wpdb->insert($table_menu_item_access, array(
                                'role_id' => $selected_role,
                                'menu_item_id' => $menu_item->id,
                                'read_permission' => $read,
                                'write_permission' => $edit
                            ));
                        }

                        echo '<div class="updated"><p>Настройки доступа для роли ' . $role_info->role_name . ' успешно сохранены.</p></div>';
                    }
                }


                // Обработка отправленной формы удаления роли
                if (isset($_POST['submit_delete_role'])) {
                    $role_to_delete = intval($_POST['delete_role']);
                    if (!empty($role_to_delete)) {
                        global $wpdb;
                        $table_roles = $wpdb->prefix . 'reginfoeduorg_roles';
                        $role_name = $wpdb->get_var($wpdb->prepare("SELECT role_name FROM $table_roles WHERE id = %d", $role_to_delete));
                        
                        $wpdb->delete($table_roles, array('id' => $role_to_delete));
                        delete_option('my_plugin_access_settings_' . $role_to_delete);
                        
                        echo '<div class="updated"><p>Роль ' . $role_name . ' успешно удалена.</p></div>';
                    }
                    echo '<script>window.location.reload();</script>';
                }


                // Обработка отправленной формы переименования роли
                if (isset($_POST['submit_rename_role'])) {
                    $role_to_rename = intval($_POST['rename_role']);
                    $new_name = sanitize_text_field($_POST['new_name']);
                    if (!empty($role_to_rename) && !empty($new_name)) {
                        global $wpdb;
                        $table_roles = $wpdb->prefix . 'reginfoeduorg_roles';

                        $old_role_name = $wpdb->get_var($wpdb->prepare("SELECT role_name FROM $table_roles WHERE id = %d", $role_to_rename));
                        $result = $wpdb->update($table_roles, array('role_name' => $new_name), array('id' => $role_to_rename));
                        
                        if ($result !== false) {
                            echo '<div class="updated"><p>Роль ' . $old_role_name . ' успешно переименована в ' . $new_name . '.</p></div>';
                        }
                    }
                    echo '<script>window.location.reload();</script>';
                }

                global $wpdb;

                $table_subsections = $wpdb->prefix . 'reginfoeduorg_site_subsections';
                $table_menu_items = $wpdb->prefix . 'reginfoeduorg_menu_items';
                $subsections = $wpdb->get_results("SELECT * FROM $table_subsections");
                $subitems = $wpdb->get_results("SELECT * FROM $table_menu_items");

                $access_settings = array_merge($subsections, $subitems);

                // JavaScript для динамического изменения checkbox
?>
<?php
                // Получаем информацию о настройках доступа
                $access_settings = array();
                $selected_role = isset($_POST['role']) ? $_POST['role'] : '';


                $table_subsection_access = $wpdb->prefix . 'reginfoeduorg_role_subsection_access';
                $table_menu_item_access = $wpdb->prefix . 'reginfoeduorg_role_menu_item_access';
                $table_site_subsections = $wpdb->prefix . 'reginfoeduorg_site_subsections';
                $table_menu_items = $wpdb->prefix . 'reginfoeduorg_menu_items';

                $subsection_access_data = $wpdb->get_results($wpdb->prepare("SELECT $table_site_subsections.id, $table_site_subsections.name, $table_subsection_access.read_permission, $table_subsection_access.write_permission FROM $table_subsection_access JOIN $table_site_subsections ON $table_site_subsections.id = $table_subsection_access.subsection_id WHERE role_id = %d", $selected_role));
                $menu_item_access_data = $wpdb->get_results($wpdb->prepare("SELECT $table_menu_items.id, $table_menu_items.name, $table_menu_item_access.read_permission, $table_menu_item_access.write_permission FROM $table_menu_item_access JOIN $table_menu_items ON $table_menu_items.id = $table_menu_item_access.menu_item_id WHERE role_id = %d", $selected_role));
                
                foreach ($subsection_access_data as $row) {
                    $access_settings['subsection_' . $row->id] = array(
                        'read' => intval($row->read_permission),
                        'edit' => intval($row->write_permission)
                    );
                }

                foreach ($menu_item_access_data as $row) {
                    $access_settings['menu_item_' . $row->id] = array(
                        'read' => intval($row->read_permission),
                        'edit' => intval($row->write_permission)
                    );
                }

?>
<script>
    jQuery(document).ready(function () {
        jQuery('#role').change(function () {  
            var selectedRole = jQuery(this).val();
            jQuery('#submit_role').click();            
            var access_settings = <?php echo json_encode($access_settings); ?>;
            console.log(selectedRole);
            console.log(access_settings);
            for (var section in access_settings) {
                if (access_settings.hasOwnProperty(section)) {
                    var read_checkbox = jQuery('input[name="subsection_' + section + '_read"], input[name="menu_item_' + section + '_read"]');
                    var edit_checkbox = jQuery('input[name="subsection_' + section + '_edit"], input[name="menu_item_' + section + '_edit"]');
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
            <?php
                $selected_role = isset($_POST['role']) ? $_POST['role'] : '';
            ?>
           <h2>Настройка доступа роли: </h2>

<select name="role" id="role">
    <option value="">Выберите роль</option>
    <?php foreach ($roles as $role) : ?>
        <option value="<?php echo $role->id; ?>"<?php echo ($selected_role == $role->id) ? 'selected' : ''; ?>><?php echo $role->role_name; ?></option>
    <?php endforeach; ?>
</select>

<table class="form-table">
    <input type="submit" name="submit_role" id="submit_role" class="button-secondary" value="Показать" />
    <tr>
        <th><h3>Подразделы сайта:</h3></th>
    </tr>
    <?php foreach ($subsections as $subsection) : ?>
        <tr>
            <th><?php echo $subsection->name; ?></th>
            <td>
                <label>Чтение</label>
                <input type="checkbox" name="subsection_<?php echo $subsection->id; ?>_read" id="subsection_<?php echo $subsection->id; ?>_read" value="1" <?php echo (isset($access_settings['subsection_' . $subsection->id]['read']) && $access_settings['subsection_' . $subsection->id]['read'] == 1) ? 'checked' : ''; ?>>
                <label>Изменение</label>
                <input type="checkbox" name="subsection_<?php echo $subsection->id; ?>_edit" id="subsection_<?php echo $subsection->id; ?>_edit" value="1" <?php echo (isset($access_settings['subsection_' . $subsection->id]['edit']) && $access_settings['subsection_' . $subsection->id]['edit'] == 1) ? 'checked' : ''; ?>>
            </td>
        </tr>
    <?php endforeach; ?>
    <tr>
        <th><h3>Подпункты меню:</h3></th>
    </tr>
    <?php foreach ($subitems as $menu_item) : ?>
        <tr>
            <th><?php echo $menu_item->name; ?></th>
            <td>
                <label>Чтение</label>
                <input type="checkbox" name="menu_item_<?php echo $menu_item->id; ?>_read" id="menu_item_<?php echo $menu_item->id; ?>_read" value="1" <?php echo (isset($access_settings['menu_item_' . $menu_item->id]['read']) && $access_settings['menu_item_' . $menu_item->id]['read'] == 1) ? 'checked' : ''; ?>>
                <label>Изменение</label>
                <input type="checkbox" name="menu_item_<?php echo $menu_item->id; ?>_edit" id="menu_item_<?php echo $menu_item->id; ?>_edit" value="1" <?php echo (isset($access_settings['menu_item_' . $menu_item->id]['edit']) && $access_settings['menu_item_' . $menu_item->id]['edit'] == 1) ? 'checked' : ''; ?>>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php
            }
            else{
                // Получаем роли из таблицы reginfoeduorg_roles
                global $wpdb;
                $table_roles = $wpdb->prefix . 'reginfoeduorg_roles';
                $roles = $wpdb->get_results("SELECT * FROM {$table_roles}");

                $selected_role = '';

                if (isset($_POST['submit_role'])) {
                    $selected_role = sanitize_text_field($_POST['role']);            
                }
                // Обработка отправленной формы добавления роли
                if (isset($_POST['submit_add_role'])) {
                    $new_role = sanitize_text_field($_POST['add_role']);
                    if (!empty($new_role)) {
                        global $wpdb;
                        $table_roles = $wpdb->prefix . 'reginfoeduorg_roles';

                        // Добавляем новую роль в таблицу reginfoeduorg_roles
                        $result = $wpdb->insert(
                            $table_roles,
                            array(
                                'role_name' => $new_role
                            ),
                            array('%s')
                        );

                        if ($result) {
                            echo '<div class="updated"><p>Роль ' . $new_role . ' успешно добавлена.</p></div>';
                        } else {
                            echo '<div class="error"><p>Не удалось добавить роль ' . $new_role . '. Пожалуйста, попробуйте еще раз.</p></div>';
                        }
                    }
                    echo '<script>window.location.reload();</script>';
                }


                // Обработка отправленной формы настройки доступа к подразделам
                global $wpdb;

                if (isset($_POST['submit_access_settings'])) {
                    $selected_role = sanitize_text_field($_POST['role']);
                    if (!empty($selected_role)) {
                        $table_subsection_access = $wpdb->prefix . 'reginfoeduorg_role_subsection_access';
                        $table_menu_item_access = $wpdb->prefix . 'reginfoeduorg_role_menu_item_access';
                        $table_roles = $wpdb->prefix . 'reginfoeduorg_roles';

                        // Получаем информацию о роли из базы данных
                        $role_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_roles WHERE id = %d", $selected_role));

                        // Удаляем существующие настройки доступа для выбранной роли
                        $wpdb->delete($table_subsection_access, array('role_id' => $selected_role));
                        $wpdb->delete($table_menu_item_access, array('role_id' => $selected_role));

                        $table_subsections = $wpdb->prefix . 'reginfoeduorg_site_subsections';
                        $table_menu_items = $wpdb->prefix . 'reginfoeduorg_menu_items';
                        $subsections = $wpdb->get_results("SELECT * FROM $table_subsections");
                        $menu_items = $wpdb->get_results("SELECT * FROM $table_menu_items");


                        // Сохраняем настройки доступа для подразделов и подпунктов меню
                        foreach ($subsections as $subsection) {
                            $read = isset($_POST['subsection_' . $subsection->id . '_read']) ? 1 : 0;
                            $edit = isset($_POST['subsection_' . $subsection->id . '_edit']) ? 1 : 0;

                            
                            $wpdb->insert($table_subsection_access, array(
                                'role_id' => $selected_role,
                                'subsection_id' => $subsection->id,
                                'read_permission' => $read,
                                'write_permission' => $edit
                            ));
                            
                        }


                        foreach ($menu_items as $menu_item) {
                            $read = isset($_POST['menu_item_' . $menu_item->id . '_read']) ? 1 : 0;
                            $edit = isset($_POST['menu_item_' . $menu_item->id . '_edit']) ? 1 : 0;

                            $wpdb->insert($table_menu_item_access, array(
                                'role_id' => $selected_role,
                                'menu_item_id' => $menu_item->id,
                                'read_permission' => $read,
                                'write_permission' => $edit
                            ));
                        }

                        echo '<div class="updated"><p>Настройки доступа для роли ' . $role_info->role_name . ' успешно сохранены.</p></div>';
                    }
                }


                // Обработка отправленной формы удаления роли
                if (isset($_POST['submit_delete_role'])) {
                    $role_to_delete = intval($_POST['delete_role']);
                    if (!empty($role_to_delete)) {
                        global $wpdb;
                        $table_roles = $wpdb->prefix . 'reginfoeduorg_roles';
                        $role_name = $wpdb->get_var($wpdb->prepare("SELECT role_name FROM $table_roles WHERE id = %d", $role_to_delete));
                        
                        $wpdb->delete($table_roles, array('id' => $role_to_delete));
                        delete_option('my_plugin_access_settings_' . $role_to_delete);
                        
                        echo '<div class="updated"><p>Роль ' . $role_name . ' успешно удалена.</p></div>';
                    }
                    echo '<script>window.location.reload();</script>';
                }


                // Обработка отправленной формы переименования роли
                if (isset($_POST['submit_rename_role'])) {
                    $role_to_rename = intval($_POST['rename_role']);
                    $new_name = sanitize_text_field($_POST['new_name']);
                    if (!empty($role_to_rename) && !empty($new_name)) {
                        global $wpdb;
                        $table_roles = $wpdb->prefix . 'reginfoeduorg_roles';

                        $old_role_name = $wpdb->get_var($wpdb->prepare("SELECT role_name FROM $table_roles WHERE id = %d", $role_to_rename));
                        $result = $wpdb->update($table_roles, array('role_name' => $new_name), array('id' => $role_to_rename));
                        
                        if ($result !== false) {
                            echo '<div class="updated"><p>Роль ' . $old_role_name . ' успешно переименована в ' . $new_name . '.</p></div>';
                        }
                    }
                    echo '<script>window.location.reload();</script>';
                }

                global $wpdb;

                $table_subsections = $wpdb->prefix . 'reginfoeduorg_site_subsections';
                $table_menu_items = $wpdb->prefix . 'reginfoeduorg_menu_items';
                $subsections = $wpdb->get_results("SELECT * FROM $table_subsections");
                $subitems = $wpdb->get_results("SELECT * FROM $table_menu_items");

                $access_settings = array_merge($subsections, $subitems);

                // JavaScript для динамического изменения checkbox
?>
<?php
                // Получаем информацию о настройках доступа
                $access_settings = array();
                $selected_role = isset($_POST['role']) ? $_POST['role'] : '';


                $table_subsection_access = $wpdb->prefix . 'reginfoeduorg_role_subsection_access';
                $table_menu_item_access = $wpdb->prefix . 'reginfoeduorg_role_menu_item_access';
                $table_site_subsections = $wpdb->prefix . 'reginfoeduorg_site_subsections';
                $table_menu_items = $wpdb->prefix . 'reginfoeduorg_menu_items';

                $subsection_access_data = $wpdb->get_results($wpdb->prepare("SELECT $table_site_subsections.id, $table_site_subsections.name, $table_subsection_access.read_permission, $table_subsection_access.write_permission FROM $table_subsection_access JOIN $table_site_subsections ON $table_site_subsections.id = $table_subsection_access.subsection_id WHERE role_id = %d", $selected_role));
                $menu_item_access_data = $wpdb->get_results($wpdb->prepare("SELECT $table_menu_items.id, $table_menu_items.name, $table_menu_item_access.read_permission, $table_menu_item_access.write_permission FROM $table_menu_item_access JOIN $table_menu_items ON $table_menu_items.id = $table_menu_item_access.menu_item_id WHERE role_id = %d", $selected_role));
                
                foreach ($subsection_access_data as $row) {
                    $access_settings['subsection_' . $row->id] = array(
                        'read' => intval($row->read_permission),
                        'edit' => intval($row->write_permission)
                    );
                }

                foreach ($menu_item_access_data as $row) {
                    $access_settings['menu_item_' . $row->id] = array(
                        'read' => intval($row->read_permission),
                        'edit' => intval($row->write_permission)
                    );
                }

?>
<script>
    jQuery(document).ready(function () {
        jQuery('#role').change(function () {  
            var selectedRole = jQuery(this).val();
            jQuery('#submit_role').click();            
            var access_settings = <?php echo json_encode($access_settings); ?>;
            console.log(selectedRole);
            console.log(access_settings);
            for (var section in access_settings) {
                if (access_settings.hasOwnProperty(section)) {
                    var read_checkbox = jQuery('input[name="subsection_' + section + '_read"], input[name="menu_item_' + section + '_read"]');
                    var edit_checkbox = jQuery('input[name="subsection_' + section + '_edit"], input[name="menu_item_' + section + '_edit"]');
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
                            <?php foreach ($roles as $role) : ?>
                                <option value="<?php echo $role->id; ?>"><?php echo $role->role_name; ?></option>
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
                            <?php foreach ($roles as $role) : ?>
                                <option value="<?php echo $role->id; ?>"><?php echo $role->role_name; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="new_name" />
                        <input  type="submit" name="submit_rename_role" class="button-secondary" value="Переименовать" />
                    </td>
                </tr>
            </table>
            <?php
                $selected_role = isset($_POST['role']) ? $_POST['role'] : '';
            ?>
           <h2>Настройка доступа роли: </h2>

<select name="role" id="role">
    <option value="">Выберите роль</option>
    <?php foreach ($roles as $role) : ?>
        <option value="<?php echo $role->id; ?>"<?php echo ($selected_role == $role->id) ? 'selected' : ''; ?>><?php echo $role->role_name; ?></option>
    <?php endforeach; ?>
</select>

<table class="form-table">
    <input type="submit" name="submit_role" id="submit_role" class="button-secondary" value="Показать" />
    <tr>
        <th><h3>Подразделы сайта:</h3></th>
    </tr>
    <?php foreach ($subsections as $subsection) : ?>
        <tr>
            <th><?php echo $subsection->name; ?></th>
            <td>
                <label>Чтение</label>
                <input type="checkbox" name="subsection_<?php echo $subsection->id; ?>_read" id="subsection_<?php echo $subsection->id; ?>_read" value="1" <?php echo (isset($access_settings['subsection_' . $subsection->id]['read']) && $access_settings['subsection_' . $subsection->id]['read'] == 1) ? 'checked' : ''; ?>>
                <label>Изменение</label>
                <input type="checkbox" name="subsection_<?php echo $subsection->id; ?>_edit" id="subsection_<?php echo $subsection->id; ?>_edit" value="1" <?php echo (isset($access_settings['subsection_' . $subsection->id]['edit']) && $access_settings['subsection_' . $subsection->id]['edit'] == 1) ? 'checked' : ''; ?>>
            </td>
        </tr>
    <?php endforeach; ?>
    <tr>
        <th><h3>Подпункты меню:</h3></th>
    </tr>
    <?php foreach ($subitems as $menu_item) : ?>
        <tr>
            <th><?php echo $menu_item->name; ?></th>
            <td>
                <label>Чтение</label>
                <input type="checkbox" name="menu_item_<?php echo $menu_item->id; ?>_read" id="menu_item_<?php echo $menu_item->id; ?>_read" value="1" <?php echo (isset($access_settings['menu_item_' . $menu_item->id]['read']) && $access_settings['menu_item_' . $menu_item->id]['read'] == 1) ? 'checked' : ''; ?>>
                <label>Изменение</label>
                <input type="checkbox" name="menu_item_<?php echo $menu_item->id; ?>_edit" id="menu_item_<?php echo $menu_item->id; ?>_edit" value="1" <?php echo (isset($access_settings['menu_item_' . $menu_item->id]['edit']) && $access_settings['menu_item_' . $menu_item->id]['edit'] == 1) ? 'checked' : ''; ?>>
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

    //Начальная страница плагина
    function my_plugin_settings_page() 
    {               
?>
        <div style="display: flex; align-items: center; justify-content: center; height: 100vh;">
          <div style="background-color: #f5f5f5; padding: 20px; border-radius: 5px; max-width: 800px;">
            <h2 style="font-size: 24px; margin-bottom: 10px;">RegInfoEduOrg</h2>
            <p style="font-size: 18px; margin-bottom: 20px;">Данный плагин предоставляет следующие возможности:</p>
            <ol style="font-size: 18px; margin-bottom: 20px;">
              <li>Создание и управление разделами информации об образовательной организации</li>
              <li>Настройка доступа к разделам для различных ролей пользователей</li>
              <li>Возможность редактирования содержимого разделов с помощью встроенного редактора</li>
              <li>Импорт данных из XML файла, с выгруженной информацией из сторонних информационных систем, таких как 1С. <a href="<?php echo plugins_url( 'structure.xml?v='.time(), __FILE__ ); ?>" target="_blank" style="text-decoration: none; color: #007bff;">Структура XML файла</a></li>
              <li>Внешняя обработка для экспорта данных из информационной системы 1С. <a href="<?php echo plugins_url( 'export.epf', __FILE__ ); ?>" download> Пример внешней обработки</a></li>
            </ol>
            <p style="font-size: 18px;">Данный плагин очень полезен для любой образовательной организации, которая хочет эффективно управлять своей информацией, а также упрощает работу сотрудникам, которые занимаются администрированием сайта.</p>
          </div>
        </div>
    <?php
    }
    
    //Настройка присвоения ролей пользователям
    function my_plugin_users_page() {
        // получаем текущего пользователя
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        global $wpdb;

        // получаем ID роли текущего пользователя из таблицы wp_reginfoeduorg_users_roles
        $user_role_id = $wpdb->get_var($wpdb->prepare("SELECT role_id FROM {$wpdb->prefix}reginfoeduorg_users_roles WHERE user_id = %d", $user_id));


        // проверяем, принадлежит ли текущий пользователь какой-то роли
        if ($user_role_id) {
            // Задаем название подпункта меню, для которого хотим проверить доступ
            $menu_item_name = 'Настройка присвоения ролей пользователям';

            // Получаем ID подпункта меню по его названию из таблицы wp_reginfoeduorg_menu_items
            $menu_item_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}reginfoeduorg_menu_items WHERE name = %s", $menu_item_name));
            $table_subsection_access = $wpdb->prefix . 'reginfoeduorg_role_subsection_access';

            // получаем список разрешений доступа для текущей роли из таблицы wp_reginfoeduorg_role_menu_item_access
            $access_menu = $wpdb->get_row($wpdb->prepare("SELECT read_permission, write_permission FROM {$wpdb->prefix}reginfoeduorg_role_menu_item_access WHERE role_id = %d AND menu_item_id = %d", $user_role_id, $menu_item_id), ARRAY_A);
            // Получаем список подразделов из базы данных
            global $wpdb;
            $table_site_subsections = $wpdb->prefix . 'reginfoeduorg_site_subsections';
            $sections = $wpdb->get_results("SELECT * FROM $table_site_subsections");
            $subsection_access_data = $wpdb->get_results($wpdb->prepare("SELECT $table_site_subsections.id, $table_site_subsections.name, $table_subsection_access.read_permission, $table_subsection_access.write_permission FROM $table_subsection_access JOIN $table_site_subsections ON $table_site_subsections.id = $table_subsection_access.subsection_id WHERE role_id = %d", $user_role_id));
            foreach ($subsection_access_data as $row) {
                $access_settings[$row->name] = array(
                    'read' => intval($row->read_permission),
                    'edit' => intval($row->write_permission)
                );
            }
            // проверяем, разрешен ли доступ к нужному подразделу меню
            if ($access_menu['read_permission'] == 0) {
                wp_die('У вас нет доступа');
            } 
            elseif ($access_menu['write_permission'] == 0) 
            {
                global $wpdb;

                // получаем список пользователей
                $users = $wpdb->get_results("SELECT u.*, r.role_name FROM {$wpdb->users} u LEFT JOIN {$wpdb->prefix}reginfoeduorg_users_roles ur ON u.ID = ur.user_id LEFT JOIN {$wpdb->prefix}reginfoeduorg_roles r ON ur.role_id = r.id");



                // Получаем список пользователей и их ролей из таблицы wp_reginfoeduorg_users_roles
                $users_roles = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_users_roles", OBJECT_K);


                // выводим таблицу с пользователями
                echo '<div class="wrap">';
                echo '<h1>Настройка прав доступа пользователей</h1>';

                if ( ! empty( $users ) ) {
                    echo '<table class="wp-list-table widefat striped">';
                    echo '<thead><tr><th>ID</th><th>Логин</th><th>Email</th><th>Роль</th></tr></thead>';
                    echo '<tbody>';

                    foreach ( $users as $user ) {
                        $user_id = $user->ID;
                        $username = $user->user_login;
                        $email = $user->user_email;
                        $role_name = $user->role_name ? $user->role_name : '';

                        echo '<tr>';
                        echo '<td>' . $user_id . '</td>';
                        echo '<td>' . $username . '</td>';
                        echo '<td>' . $email . '</td>';
                        echo '<td>' . $role_name . '</td>';

                        
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
                global $wpdb;

                // получаем список пользователей
                $users = $wpdb->get_results("SELECT u.*, r.role_name FROM {$wpdb->users} u LEFT JOIN {$wpdb->prefix}reginfoeduorg_users_roles ur ON u.ID = ur.user_id LEFT JOIN {$wpdb->prefix}reginfoeduorg_roles r ON ur.role_id = r.id");



                if ( isset( $_POST['my_plugin_change_user_role'] ) ) {
                    $user_id = intval( $_POST['my_plugin_user_id'] );
                    $new_role_id = intval( $_POST['my_plugin_user_role'] );

                    // Проверяем существование записи с указанным user_id
                    $existing_user_role = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}reginfoeduorg_users_roles WHERE user_id = %d", $user_id));

                    if (null !== $existing_user_role) {
                        // Обновляем запись с указанным user_id
                        $wpdb->update(
                            $wpdb->prefix . 'reginfoeduorg_users_roles',
                            array('role_id' => $new_role_id), // новые значения
                            array('user_id' => $user_id) // условие для обновления
                        );
                    } else {
                        // Добавляем новую запись, если запись с указанным user_id еще не существует
                        $wpdb->insert($wpdb->prefix . 'reginfoeduorg_users_roles', array(
                            'user_id' => $user_id,
                            'role_id' => $new_role_id
                        ));
                    }
                    echo '<script>window.location.reload();</script>';
                }


                // Получаем список пользователей и их ролей из таблицы wp_reginfoeduorg_users_roles
                $users_roles = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_users_roles", OBJECT_K);


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
                        $role_name = $user->role_name ? $user->role_name : '';

                        echo '<tr>';
                        echo '<td>' . $user_id . '</td>';
                        echo '<td>' . $username . '</td>';
                        echo '<td>' . $email . '</td>';
                        echo '<td>' . $role_name . '</td>';

                        
                        echo '</td>';
                        echo '<td>';
                        echo '<form method="post">';
                        echo '<input type="hidden" name="my_plugin_user_id" value="' . $user_id . '">';
                        echo '<input type="hidden" name="my_plugin_user_username" value="' . $username . '">';
                        echo '<select name="my_plugin_user_role">';

                        // добавляем элемент option с надписью "Выберите роль" и пустым значением
                        echo '<option value="">Выберите роль</option>';

                        // получаем список ролей из таблицы wp_reginfoeduorg_roles
                        $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_roles");
                        foreach ($results as $role) {
                            $selected = selected($role->id, $user_role_id, false);
                            echo '<option value="' . $role->id . '" ' . $selected . '>' . $role->role_name . '</option>';
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
    
    // Подпункты меню с настройкой подразделов
    function reginfoeduorg_display_section_data() {
        
        global $wpdb;
        $url = $_SERVER['REQUEST_URI'];
        $matches = array();
        preg_match('/reginfoeduorg_subsection_(\d+)/', $url, $matches);
        $subsection_id = isset($matches[1]) ? intval($matches[1]) : 0;
        
        if (!$subsection_id) {
            // handle the error here
            return;
        }

        $subsection_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE id = %d", $subsection_id));


        //Обработчик импорта
        if ( isset( $_POST['import_file_submit'] ) && isset( $_FILES['import_file'] ) ) {
            if ($_FILES['import_file']['error'] === UPLOAD_ERR_OK) {                
                $xml = simplexml_load_file($_FILES['import_file']['tmp_name']);                
                $this->import_data($subsection_id, $xml);                
            }
            else {
                // Выводим сообщение об ошибке при загрузке файла
                echo "<div class='notice notice-error is-dismissible'><p>Ошибка при загрузке файла: " . $_FILES['import_file']['error'] . "</p></div>";
            }
        }

        //Обработчик применения стилей
        if (isset($_POST['apply_styles'])) {
            $this->apply_styles($subsection_id);
        }

        if (isset($_POST['save_visibility'])) {
            if($this->check_write($subsection_id))
            {
                // обрабатываем чекбокс видимости подраздела
                $visible = isset($_POST['reginfoeduorg_subsection_visibility']) ? 1 : 0;
                $wpdb->update(
                    $wpdb->prefix . 'reginfoeduorg_site_subsections',
                    array('visible' => $visible),
                    array('id' => $subsection_id),
                    array('%d'),
                    array('%d')
                );
                echo '<div id="message" class="updated notice is-dismissible"><p>Настройки сохранены</p></div>';
            }
            else{
                echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
            }
            
        }


         
        echo '<div class="wrap">';
        //Заголовок - название подраздела
        echo '<h1>' . $subsection_name . '</h1>';
        // Данные в таблице        
        $this->table_data($subsection_id);   
        echo '<form method="post" action="" enctype="multipart/form-data">';
        //Импорт
        echo '<h2>Импорт данных</h2>';
        
        echo  '<input type="file" name="import_file" accept=".xml">';
        echo '<input type="submit" name="import_file_submit" value="Импортировать" class="button-primary">';
        echo '<br>';
        //XSLT стили
        echo '<h2>Настройка отображения таблицы</h2>';

        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th><label for="xslt-styles">XSLT стиль страницы:</label></th>';
        echo '<td>';
        $subsection_data = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE id = {$subsection_id}", ARRAY_A);
        $xslt_code = $wpdb->get_var("SELECT xslt FROM {$wpdb->prefix}reginfoeduorg_site_subsection_styles WHERE subsection_id = '$subsection_id' and style_type = 'overview'");
        $saved_xslt_code = isset($xslt_code) ? $xslt_code : '';
        echo '<textarea name="reginfoeduorg_xslt_code" id="reginfoeduorg_xslt_code" rows="10" style="width: 100%;">' . esc_textarea($saved_xslt_code) . '</textarea>';
        echo '</td>';
        echo '</tr>';
        echo '</table>';

        if($subsection_id == 6)
        {
            echo '<table class="form-table">';
            echo '<tr>';
            echo '<th><label for="xslt-styles">XSLT стиль для элемента:</label></th>';
            echo '<td>';
            $xslt_code_detail = $wpdb->get_var("SELECT xslt FROM {$wpdb->prefix}reginfoeduorg_site_subsection_styles WHERE subsection_id = '$subsection_id' and style_type = 'detail'");
            $saved_xslt_code_detail = isset($xslt_code_detail) ? $xslt_code_detail : '';
            echo '<textarea name="reginfoeduorg_xslt_code_detail" id="reginfoeduorg_xslt_code_detail" rows="10" style="width: 100%;">' . esc_textarea($saved_xslt_code_detail) . '</textarea>';
            echo '</td>';
            echo '</tr>';
            echo '</table>';
        }
        echo '<p>';
        echo '<input type="submit" name="apply_styles" value="Применить стиль" class="button">';
        echo '</p>';

        //Новый раздел для настройки видимости подраздела
        echo '<h2>Настройка видимости подраздела</h2>';
        echo '<label>';
        echo '<input type="checkbox" name="reginfoeduorg_subsection_visibility" value="1"' . checked($subsection_data['visible'], 1, false) . '>Отображать подраздел на сайте';
        echo '<br><br><input type="submit" name="save_visibility" value="Сохранить" class="button">';

        echo '</label>';

        echo '</form>';
        echo '</div>';
    }


    //-----------------------Процесс обработки и вывода данных в подпунктах меню с настройкой подразделов-------------------------------
    //Импорт
    function import_data($subsection_id, $xml)
    {
        if(!$subsection_id)
        {
            return;
        }
        if(!$this->check_write($subsection_id))
        {
            echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
            return;
        }
        if(!$xml)
        {
            return;
        }

        global $wpdb;
        $subsection_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE id = %d",$subsection_id));
        
        switch ($subsection_id)
        {
            case 1:
                // Находим секцию "Основные сведения"
                $section_content = $xml->xpath('//section[section_title="Основные сведения"]/section_content/general_information')[0];

                // Преобразуем дату создания в формат 'Y-m-d', игнорируя время
                $creation_date = DateTime::createFromFormat('d.m.Y H:i:s', (string)$section_content->creation_date);
                $creation_date_formatted = $creation_date ? $creation_date->format('Y-m-d') : '';

                // Создаем массив с данными для таблицы reginfoeduorg_general_information
                $data = array(
                    'full_name' => (string)$section_content->full_name,
                    'short_name' => (string)$section_content->short_name,
                    'creation_date' => $creation_date_formatted,
                    'founder' => (string)$section_content->founder,
                    'location' => (string)$section_content->location,
                    'branches' => (string)$section_content->branches,
                    'working_hours' => (string)$section_content->working_hours,
                    'contact_phones' => (string)$section_content->contact_phones,
                    'email_addresses' => (string)$section_content->email_addresses,
                );

                // Вставляем или обновляем данные в таблице reginfoeduorg_general_information
                global $wpdb;
                $table_name = "{$wpdb->prefix}reginfoeduorg_general_information";
                $row_exists = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                if ($row_exists) {
                    $wpdb->update($table_name, $data, ['id' => 1]);
                } else {
                    $wpdb->insert($table_name, $data);
                }

                break;
            case 2:
                // Находим нужную секцию в XML
                $sections = $xml->xpath('//reginfoeduorg/section[section_title="'.$subsection_name.'"]/section_content');

                // Очищаем таблицу перед импортом
                global $wpdb;
                $table_staff = "{$wpdb->prefix}reginfoeduorg_staff";
                $table_management_structure = "{$wpdb->prefix}reginfoeduorg_management_structure";

                $wpdb->query("DELETE FROM $table_management_structure");

                // Проходимся по всем секциям
                foreach ($sections as $section) {
                    // Получаем данные
                    $full_name = (string)$section->management_structure->full_name;
                    $position = (string)$section->management_structure->position;
                    $start_date = (string)$section->management_structure->start_date;
                    $basis_document = (string)$section->management_structure->basis_document;
                    $document_date = (string)$section->management_structure->document_date;
                    $document_number = (string)$section->management_structure->document_number;
                    $structure_image_url = (string)$section->management_structure->structure_image_url;

                    // Проверяем, существует ли такой сотрудник в базе данных
                    $existing_staff_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_staff WHERE full_name = %s AND position = %s",$full_name, $position));

                    // Если сотрудник не существует, добавляем его
                    if ($existing_staff_id === null) {
                        // Выводим сообщение об ошибке при вставке данных
                        echo "<div class='notice notice-error is-dismissible'><p>Данный сотрудник отсутствует в базе. Сначала сделайте импорт сотрудников.</p></div>";
                        break;                       
                    }

                    // Создаем массив с данными для таблицы management_structure
                    $data_management = array(
                        'staff_id' => $existing_staff_id,
                        'start_date' => $start_date,
                        'basis_document' => $basis_document,
                        'document_date' => $document_date,
                        'document_number' => $document_number,
                        'structure_image_url' => $structure_image_url
                    );

                    // Вставляем данные в таблицу management_structure
                    if ($wpdb->insert($table_management_structure, $data_management) === false) {
                        // Выводим сообщение об ошибке при вставке данных
                        echo "<div class='notice notice-error is-dismissible'><p>Ошибка при вставке данных в таблицу управления: " . $wpdb->last_error . "</p></div>";
                        break;
                    }
                }
                break;
            case 3:
            case 4:
            case 8:
            case 9:
            case 10:
            case 11:
                // Находим нужную секцию в XML
                $section = $xml->xpath('//reginfoeduorg/section[section_title="'.$subsection_name.'"]/section_content/documents')[0];
                
                // Очищаем таблицы перед импортом
                global $wpdb;
                $table_documents_types = "{$wpdb->prefix}reginfoeduorg_document_types";
                $table_documents = "{$wpdb->prefix}reginfoeduorg_documents";

                $wpdb->query("DELETE FROM $table_documents WHERE subsection_id = $subsection_id");

                // Массив для хранения идентификаторов типов документов
                $document_types_ids = array();

                // Проходимся по всем документам
                foreach ($section->document as $document) {
                    // Получаем данные
                    $document_name = (string)$document->name;
                    $document_link = (string)$document->link;
                    $document_type = (string)$document->type;

                    // Если тип документа еще не импортирован, проверяем его наличие в базе данных
                    if (!array_key_exists($document_type, $document_types_ids)) {
                        // Проверяем, существует ли тип документа в базе данных
                        $existing_type_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_documents_types WHERE document_type = %s",$document_type));

                        // Если тип документа уже существует, используем его id, иначе добавляем новый тип
                        if ($existing_type_id !== null) {
                            $document_types_ids[$document_type] = $existing_type_id;
                        } else {
                            $data_type = array(
                                'document_type' => $document_type
                            );
                            
                            // Вставляем тип документа в таблицу и сохраняем ID
                            if ($wpdb->insert($table_documents_types, $data_type) !== false) {
                                $document_types_ids[$document_type] = $wpdb->insert_id;
                            } else {
                                // Выводим сообщение об ошибке при вставке данных
                                echo "<div class='notice notice-error is-dismissible'><p>Ошибка при вставке типа документа в таблицу: " . $wpdb->last_error . "</p></div>";
                                break;
                            }
                        }
                    }

                    // Если есть ссылка на документ
                    if (!empty($document_link)) {
                        // Создаем массив с данными для таблицы documents
                        $data = array(
                            'document_name' => $document_name,
                            'document_link' => $document_link,
                            'document_type' => $document_types_ids[$document_type],
                            'subsection_id' => $subsection_id
                        );

                        // Вставляем данные в таблицу documents
                        if ($wpdb->insert($table_documents, $data) === false) {
                            // Выводим сообщение об ошибке при вставке данных
                            echo "<div class='notice notice-error is-dismissible'><p>Ошибка при вставке документа в таблицу: " . $wpdb->last_error . "</p></div>";
                            break;
                        }
                    }
                }
                break;
            case 5:
                // Находим нужную секцию в XML
                $section = $xml->xpath('//reginfoeduorg/section[section_title="'.$subsection_name.'"]/section_content')[0];

                // Очищаем таблицы перед импортом
                global $wpdb;
                $table_programs = "{$wpdb->prefix}reginfoeduorg_education_programs";
                $table_accreditation = "{$wpdb->prefix}reginfoeduorg_accreditation";
                $table_scientific = "{$wpdb->prefix}reginfoeduorg_directions_results_scientific";
                $table_program_type = "{$wpdb->prefix}reginfoeduorg_contingent";
                $table_type_education = "{$wpdb->prefix}reginfoeduorg_type_education";

                $wpdb->query("DELETE FROM $table_programs");
                $wpdb->query("DELETE FROM $table_accreditation");
                $wpdb->query("DELETE FROM $table_scientific");
                $wpdb->query("DELETE FROM $table_program_type");
                $wpdb->query("DELETE FROM $table_type_education");

                // Создаем массив type_education
                $type_education = array();
                foreach ($section->contingent->program_type as $item) {
                    $type_education[] = (string)$item->type_education;
                }

                // Удаляем повторяющиеся значения
                $type_education = array_unique($type_education);

                // Заполняем таблицу type_education
                foreach ($type_education as $item) {
                    if ($wpdb->insert($table_type_education, array('type_education' => $item)) === false) {
                        // Выводим сообщение об ошибке при вставке данных
                        echo "<div class='notice notice-error is-dismissible'><p>Ошибка при вставке информации о type_education в таблицу: " . $wpdb->last_error . "</p></div>";
                        break;
                    }
                }

                // Проходимся по всем программам, аккредитациям, направлениям и результатам научной деятельности и контингенту
                foreach (['educational_programs' => $table_programs, 'accreditation' => $table_accreditation, 'scientific_activity' => $table_scientific, 'contingent' => $table_program_type] as $xml_part => $table) {
                    foreach ($section->$xml_part->children() as $item) {
                        // Получаем данные
                        $data = [];
                        foreach ($item->children() as $key => $value) {
                            // Если мы импортируем данные в таблицу contingent, заменяем значение type_education на его id из таблицы type_education
                            if ($xml_part == 'contingent' && $key == 'type_education') {
                                $type_id = $wpdb->get_var("SELECT id FROM $table_type_education WHERE type_education = '" . ((string)$value) . "'");
                                $data['type_education'] = $type_id;
                            } else {
                                $data[$key] = (string)$value;
                            }
                        }

                        // Вставляем данные в соответствующую таблицу
                        if ($wpdb->insert($table, $data) === false) {
                            // Выводим сообщение об ошибке при вставке данных
                            echo "<div class='notice notice-error is-dismissible'><p>Ошибка при вставке информации о $xml_part в таблицу: " . $wpdb->last_error . "</p></div>";
                            break;
                        }
                    }
                }
                break;


            case 6:
                // Находим нужную секцию в XML
                $section = $xml->xpath('//reginfoeduorg/section[section_title="'.$subsection_name.'"]/section_content')[0];
                
                // Очищаем таблицу перед импортом
                global $wpdb;
                $table_staff = "{$wpdb->prefix}reginfoeduorg_staff";

                $wpdb->query("DELETE FROM $table_staff");
                // Проходимся по всем сотрудникам
                foreach ($section->staff as $staff_member) {
                    // Получаем данные
                    $full_name = (string)$staff_member->full_name;
                    $email = (string)$staff_member->email;
                    $phone = (string)$staff_member->phone;
                    $position = (string)$staff_member->position;
                    $disciplines = (string)$staff_member->disciplines;
                    $education = (string)$staff_member->education;
                    $specialization = (string)$staff_member->specialization;
                    $qualification_improvement = (string)$staff_member->qualification_improvement;
                    $career = (string)$staff_member->career;
                    $overall_experience = (int)$staff_member->overall_experience;
                    $specialization_experience = (int)$staff_member->specialization_experience;
                    $small_image_url = (string)$staff_member->small_image_url;
                    $big_image_url = (string)$staff_member->big_image_url;
                    
                    // Проверяем, существуют ли уже данные для этого сотрудника в таблице
                    $existing_staff = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT * FROM $table_staff WHERE full_name = %s AND email = %s",
                            $full_name,
                            $email
                        ),
                        ARRAY_A
                    );

                    if ($existing_staff) {
                        // Если данные уже существуют, пропускаем вставку
                        continue;
                    }
                    
                    // Создаем массив с данными для таблицы staff
                    $data_staff = array(
                        'full_name' => $full_name,
                        'email' => $email,
                        'phone' => $phone,
                        'position' => $position,
                        'disciplines' => $disciplines,
                        'education' => $education,
                        'specialization' => $specialization,
                        'qualification_improvement' => $qualification_improvement,
                        'career' => $career,
                        'overall_experience' => $overall_experience,
                        'specialization_experience' => $specialization_experience,
                        'small_image_url' => $small_image_url,
                        'big_image_url' => $big_image_url,
                    );

                    // Вставляем данные в таблицу staff
                    if ($wpdb->insert($table_staff, $data_staff) === false) {
                        // Выводим сообщение об ошибке при вставке данных
                        echo "<div class='notice notice-error is-dismissible'><p>Ошибка при вставке информации о сотруднике в таблицу: " . $wpdb->last_error . "</p></div>";
                        break;
                    }
                }
                break;
            case 7:
                // Находим нужную секцию в XML
                $section = $xml->xpath('//reginfoeduorg/section[section_title="'.$subsection_name.'"]/section_content')[0];
                
                // Очищаем таблицы перед импортом
                global $wpdb;
                $table_resource_types = "{$wpdb->prefix}reginfoeduorg_resource_types";
                $table_resources = "{$wpdb->prefix}reginfoeduorg_resources";
                $table_document_types = "{$wpdb->prefix}reginfoeduorg_document_types";
                $table_documents = "{$wpdb->prefix}reginfoeduorg_documents";

                $wpdb->query("DELETE FROM $table_resources");
                $wpdb->query("DELETE FROM $table_documents WHERE subsection_id = $subsection_id");

                // Массивы для хранения идентификаторов типов ресурсов и документов
                $resource_types_ids = array();
                $document_types_ids = array();
                
                // Проходимся по всем ресурсам
                foreach ($section->technical_equipment->equipment as $resource) {
                    
                    $resource_type = (string)$resource->resource_type;
                    $resource_name = (string)$resource->name;
                    $resource_details = (string)$resource->details;

                    if (!array_key_exists($resource_type, $resource_types_ids)) {
                        $existing_type_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_resource_types WHERE type_name = %s", $resource_type));
                        
                        if ($existing_type_id !== null) {
                            $resource_types_ids[$resource_type] = $existing_type_id;
                        } else {
                            $data_type = array(
                                'type_name' => $resource_type
                            );

                            if ($wpdb->insert($table_resource_types, $data_type) !== false) {
                                $resource_types_ids[$resource_type] = $wpdb->insert_id;
                            } else {
                                echo "<div class='notice notice-error is-dismissible'><p>Ошибка при вставке типа ресурса в таблицу: " . $wpdb->last_error . "</p></div>";
                                break;
                            }
                        }
                    }

                    $data = array(
                        'resource_name' => $resource_name,
                        'resource_type' => $resource_types_ids[$resource_type],
                        'details' => $resource_details
                    );

                    if ($wpdb->insert($table_resources, $data) === false) {
                        echo "<div class='notice notice-error is-dismissible'><p>Ошибка при вставке ресурса в таблицу: " . $wpdb->last_error . "</p></div>";
                        break;
                    }
                }

                // Проходимся по всем документам
                foreach ($section->documents->document as $document) {
                    $document_name = (string)$document->name;
                    $document_link = (string)$document->link;
                    $document_type = (string)$document->document_type;

                    if (!array_key_exists($document_type, $document_types_ids)) {
                        $existing_type_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_document_types WHERE document_type = %s", $document_type));

                        if ($existing_type_id !== null) {
                            $document_types_ids[$document_type] = $existing_type_id;
                        } else {
                            $data_type = array(
                                'document_type' => $document_type
                            );

                            if ($wpdb->insert($table_document_types, $data_type) !== false) {
                                $document_types_ids[$document_type] = $wpdb->insert_id;
                            } else {
                                echo "<div class='notice notice-error is-dismissible'><p>Ошибка при вставке типа документа в таблицу: " . $wpdb->last_error . "</p></div>";
                                break;
                            }
                        }
                    }

                    if (!empty($document_link)) {
                        $data = array(
                            'document_name' => $document_name,
                            'document_link' => $document_link,
                            'document_type' => $document_types_ids[$document_type],
                            'subsection_id' => $subsection_id
                        );

                        if ($wpdb->insert($table_documents, $data) === false) {
                            echo "<div class='notice notice-error is-dismissible'><p>Ошибка при вставке документа в таблицу: " . $wpdb->last_error . "</p></div>";
                            break;
                        }
                    }
                }
                break;
            case 12:
                // Находим нужную секцию в XML
                $section = $xml->xpath('//reginfoeduorg/section[section_title="'.$subsection_name.'"]/section_content/accessible_environment')[0];
                
                // Очищаем таблицы перед импортом
                global $wpdb;
                $table_documents = "{$wpdb->prefix}reginfoeduorg_documents";
                $table_special_conditions = "{$wpdb->prefix}reginfoeduorg_special_conditions";
                $table_document_types = "{$wpdb->prefix}reginfoeduorg_document_types";

                $wpdb->query("DELETE FROM $table_documents WHERE subsection_id = $subsection_id");
                $wpdb->query("DELETE FROM $table_special_conditions");

                $document_types_ids = array();
                // Проходимся по всем документам
                foreach ($section->documents->document as $document) {
                    $document_name = (string)$document->name;
                    $document_type = (string)$document->type;
                    $document_link = (string)$document->link;

                    $data = array(
                        'document_name' => $document_name,
                        'document_type' => $document_type,
                        'document_link' => $document_link,
                        'subsection_id' => $subsection_id
                    );

                    if (!array_key_exists($document_type, $document_types_ids)) {
                        $existing_type_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_document_types WHERE document_type = %s", $document_type));

                        if ($existing_type_id !== null) {
                            $document_types_ids[$document_type] = $existing_type_id;
                        } else {
                            $data_type = array(
                                'document_type' => $document_type
                            );

                            if ($wpdb->insert($table_document_types, $data_type) !== false) {
                                $document_types_ids[$document_type] = $wpdb->insert_id;
                            } else {
                                echo "<div class='notice notice-error is-dismissible'><p>Ошибка при вставке типа документа в таблицу: " . $wpdb->last_error . "</p></div>";
                                break;
                            }
                        }
                    }

                    if (!empty($document_link)) {
                        $data = array(
                            'document_name' => $document_name,
                            'document_link' => $document_link,
                            'document_type' => $document_types_ids[$document_type],
                            'subsection_id' => $subsection_id
                        );

                        if ($wpdb->insert($table_documents, $data) === false) {
                            echo "<div class='notice notice-error is-dismissible'><p>Ошибка при вставке документа в таблицу: " . $wpdb->last_error . "</p></div>";
                            break;
                        }
                    }
                }

                // Проходимся по всем специальным условиям
                foreach ($section->special_conditions->option_condition as $condition) {
                    $condition_info = (string)$condition->info;
                    $condition_value = (string)$condition->value;

                    $data = array(
                        'info' => $condition_info,
                        'value' => $condition_value
                    );

                    if ($wpdb->insert($table_special_conditions, $data) === false) {
                        echo "<div class='notice notice-error is-dismissible'><p>Ошибка при вставке специального условия в таблицу: " . $wpdb->last_error . "</p></div>";
                        break;
                    }
                }
                break;

            case 13:
                // Находим нужную секцию в XML
                $section = $xml->xpath('//reginfoeduorg/section[section_title="'.$subsection_name.'"]/section_content')[0];
                
                // Очищаем таблицу перед импортом
                global $wpdb;
                $table_international_cooperation = "{$wpdb->prefix}reginfoeduorg_international_cooperation";

                $wpdb->query("DELETE FROM $table_international_cooperation");
                // Проходимся по всем элементам международного сотрудничества
                foreach ($section->international_cooperation->option_cooperation as $coop_element) {
                    // Получаем данные
                    $info = (string)$coop_element->info;
                    $value = (string)$coop_element->value;
                    
                    // Создаем массив с данными для таблицы international_cooperation
                    $data_coop = array(
                        'info' => $info,
                        'value' => $value,
                    );

                    // Вставляем данные в таблицу international_cooperation
                    if ($wpdb->insert($table_international_cooperation, $data_coop) === false) {
                        // Выводим сообщение об ошибке при вставке данных
                        echo "<div class='notice notice-error is-dismissible'><p>Ошибка при вставке информации о международном сотрудничестве в таблицу: " . $wpdb->last_error . "</p></div>";
                        break;
                    }
                }
                break;

        }  
        echo "<div class='notice notice-success is-dismissible'><p>Данные успешно импортированы из файла.</p></div>";
        if($_POST['reginfoeduorg_xslt_code'])
        {
            $this->apply_styles($subsection_id);
        }
        else
        {
            echo "<div class='notice notice-error is-dismissible'><p>Данные импортированы, но пока не отображаются на сайте, так как не введен XSLT стиль</p></div>";
            
        }
    }
    //Применение стиля
    function apply_styles($subsection_id)
    {
        if(!$subsection_id)
        {
            return;
        }
        if(!$this->check_write($subsection_id))
        {
            echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
            return;
        }
        global $wpdb;
        
        $xslt_code = isset($_POST['reginfoeduorg_xslt_code']) ? stripslashes($_POST['reginfoeduorg_xslt_code']) : '';
        $xslt_code_detail = isset($_POST['reginfoeduorg_xslt_code_detail']) ? stripslashes($_POST['reginfoeduorg_xslt_code_detail']) : '';
        if(!$xslt_code)
        {
            return;
        }
        // Проверяем наличие обзорного стиля в базе данных
        $existing_overview_style = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}reginfoeduorg_site_subsection_styles WHERE subsection_id = %d AND style_type = %s",
            $subsection_id, 'overview'
        ));

        // Если обзорный стиль существует, обновляем его. В противном случае, вставляем новую запись.
        if ($existing_overview_style > 0) {
            $wpdb->update(
                "{$wpdb->prefix}reginfoeduorg_site_subsection_styles",
                array('xslt' => $xslt_code),
                array(
                    'subsection_id' => $subsection_id,
                    'style_type' => 'overview'
                ),
                array('%s'),
                array('%d', '%s')
            );
        } else {
            $wpdb->insert(
                "{$wpdb->prefix}reginfoeduorg_site_subsection_styles",
                array(
                    'subsection_id' => $subsection_id,
                    'style_type' => 'overview',
                    'xslt' => $xslt_code
                ),
                array('%d', '%s', '%s')
            );
        }

        // Проверяем наличие детального стиля в базе данных
        $existing_detail_style = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}reginfoeduorg_site_subsection_styles WHERE subsection_id = %d AND style_type = %s",
            $subsection_id, 'detail'
        ));

        // Если детальный стиль существует, обновляем его. В противном случае, вставляем новую запись.
        if ($existing_detail_style > 0) {
            $wpdb->update(
                "{$wpdb->prefix}reginfoeduorg_site_subsection_styles",
                array('xslt' => $xslt_code_detail),
                array(
                    'subsection_id' => $subsection_id,
                    'style_type' => 'detail'
                ),
                array('%s'),
                array('%d', '%s')
            );
        } else {
            $wpdb->insert(
                "{$wpdb->prefix}reginfoeduorg_site_subsection_styles",
                array(
                    'subsection_id' => $subsection_id,
                    'style_type' => 'detail',
                    'xslt' => $xslt_code_detail
                ),
                array('%d', '%s', '%s')
            );
        }


        $xml = new DOMDocument();
        $xml = $this->generate_shortcode($subsection_id);
        echo '<div id="message" class="updated notice notice-success is-dismissible"><p>XSLT стиль применен.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';

    }
    //Вывод данных в таблицу
    function table_data($subsection_id)
    {
        global $wpdb;
        if(!$subsection_id)
        {
            return;
        }

        echo '<style>
            .wp-list-table input:not([type=checkbox]), .wp-list-table textarea {
            width: 100%;
            box-sizing: border-box;
                }
            </style>';
       

        switch ($subsection_id) {     
            case 1:
                // Обработка формы добавления нового элемента
                if (isset($_POST['add_item'])) {
                    $this->add_new_item($subsection_id, 'general_information', $_POST['new_item']);
                }
                // Ваша форма для добавления нового элемента
                echo '<h2>Добавить новую организацию</h2>
                <form method="post">
                    <div><input style="width: 50%" type="text" name="new_item[full_name]" placeholder="Полное название образовательной организации" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[short_name]" placeholder="Краткое название образовательной организации" required /></div>
                    <div><input style="width: 50%" type="date" name="new_item[creation_date]" placeholder="Дата создания образовательной организации" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[founder]" placeholder="Учредитель" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[location]" placeholder="Место нахождения образовательной организации" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[branches]" placeholder="Филиалы образовательной организации" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[working_hours]" placeholder="График работы" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[contact_phones]" placeholder="Контактные телефоны" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[email_addresses]" placeholder="Адреса электронной почты" required /></div>
                    <div><input type="submit" name="add_item" class="button" value="Добавить новую организацию" /></div>
                </form>';
                // Обработка формы после отправки
                if (isset($_POST['save'])) {                    
                    $this->update_existing_items($subsection_id, 'general_information', $_POST['data'], $_POST['item']);
                }
                
                echo '<h2>Таблица с основными сведениями</h2>
                <form method="post">';
                $s = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
                $search_field = isset($_REQUEST['search_field']) ? $_REQUEST['search_field'] : 'full_name';

                echo '<div style="text-align: right;">';
                echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
                echo '<select name="search_field" id="search_field">';
                echo '<option value="full_name"' . ($search_field == 'full_name' ? ' selected' : '') . '>Полное название образовательной организации</option>';
                echo '<option value="short_name"' . ($search_field == 'short_name' ? ' selected' : '') . '>Краткое название образовательной организации</option>';
                echo '<option value="creation_date"' . ($search_field == 'creation_date' ? ' selected' : '') . '>Дата создания образовательной организации</option>';
                echo '<option value="founder"' . ($search_field == 'founder' ? ' selected' : '') . '>Учредитель</option>';
                echo '<option value="location"' . ($search_field == 'location' ? ' selected' : '') . '>Место нахождения образовательной организации</option>';
                echo '<option value="branches"' . ($search_field == 'branches' ? ' selected' : '') . '>Филиалы образовательной организации</option>';
                echo '<option value="working_hours"' . ($search_field == 'working_hours' ? ' selected' : '') . '>График работы</option>';
                echo '<option value="contact_phones"' . ($search_field == 'contact_phones' ? ' selected' : '') . '>Контактные телефоны</option>';
                echo '<option value="email_addresses"' . ($search_field == 'email_addresses' ? ' selected' : '') . '>Адреса электронной почты</option>';
                echo '</select>';

                echo '<input type="search" id="search" name="s" value="' . $s . '" style="margin-left: 10px;" />';
                echo '<input type="submit" name="" id="search-submit" class="button" value="Поиск" />';
                echo '</div>';
                
                $table = $this->display_table('General_Information_Table', 'general_information', 'Данные о образовательном учреждении отсутствуют.', $subsection_id, $search_field);

           
                if ('edit' === $table->current_action()) {
                    
                    if(!$this->check_write($subsection_id))
                    {
                        echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
                        return;
                    }
                    echo '<div><input type="submit" name="save" class="button-primary" value="Сохранить изменения" /></div>';
                }
                echo '</form>';
                break;
                

            case 2:
                // Обработка формы добавления нового элемента
                if (isset($_POST['add_item'])) {
                    $this->add_new_item($subsection_id, 'management_structure', $_POST['new_item']);
                }

                // Ваша форма для добавления нового элемента
                echo '
                <h2>Добавить новую структуру организации</h2>
                <form method="post">
                    <div><input style="width: 50%" type="text" name="new_item[full_name]" placeholder="ФИО" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[position]" placeholder="Должность" required /></div>
                    <div><input style="width: 50%" type="date" name="new_item[start_date]" placeholder="Дата начала" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[basis_document]" placeholder="Основание (документ)" required /></div>
                    <div><input style="width: 50%" type="date" name="new_item[document_date]" placeholder="Дата документа" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[document_number]" placeholder="Номер документа" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[structure_image_url]" placeholder="URL изображения структуры" required /></div>
                    <div><input type="submit" name="add_item" class="button" value="Добавить новую структуру организации" /></div>
                </form>';
                // Обработка формы после отправки
                if (isset($_POST['save'])) {                    
                    $this->update_existing_items($subsection_id, 'management_structure', $_POST['data'], $_POST['item']);
                }

                
                echo '<h2>Таблица со структурой и органами управления образовательной организацией</h2>
<form method="post">';
                $s = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
                $search_field = isset($_REQUEST['search_field']) ? $_REQUEST['search_field'] : 'full_name';

                echo '<div style="text-align: right;">';
                echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
                echo '<select name="search_field" id="search_field">';
                echo '<option value="full_name"' . ($search_field == 'full_name' ? ' selected' : '') . '>ФИО</option>';
                echo '<option value="position"' . ($search_field == 'position' ? ' selected' : '') . '>Должность</option>';
                echo '<option value="start_date"' . ($search_field == 'start_date' ? ' selected' : '') . '>Дата начала</option>';
                echo '<option value="basis_document"' . ($search_field == 'basis_document' ? ' selected' : '') . '>Основание (документ)</option>';
                echo '<option value="document_date"' . ($search_field == 'document_date' ? ' selected' : '') . '>Дата документа</option>';
                echo '<option value="document_number"' . ($search_field == 'document_number' ? ' selected' : '') . '>Номер документа</option>';
                echo '<option value="structure_image_url"' . ($search_field == 'structure_image_url' ? ' selected' : '') . '>URL изображения структуры</option>';
                echo '</select>';

                echo '<input type="search" id="search" name="s" value="' . $s . '" style="margin-left: 10px;" />';
                echo '<input type="submit" name="" id="search-submit" class="button" value="Поиск" />';
                echo '</div>';

                $table = $this->display_table('Management_Structure_Table', 'management_structure', 'Данные о структуре и органах управления образовательной организации отсутствуют.', $subsection_id, $search_field);

                if ('edit' === $table->current_action()) {
                    
                    if(!$this->check_write($subsection_id))
                    {
                        echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
                        return;
                    }
                    echo '<div><input type="submit" name="save" class="button-primary" value="Сохранить изменения" /></div>';
                }
                echo '</form>';
                break;

            case 5:
                //Группы
                // Обработка формы добавления нового элемента
                if (isset($_POST['add_item_education_programs'])) {
                    $this->add_new_item($subsection_id, 'education_programs', $_POST['new_item_education_programs']);
                }

                // Ваша форма для добавления нового элемента
                echo '
                <h2>Добавить новую группу</h2>
                <form method="post">
                    <div><input style="width: 50%" type="text" name="new_item_education_programs[major_group]" placeholder="Группа направлений" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_education_programs[training_program]" placeholder="Программа обучения" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_education_programs[level_of_training]" placeholder="Уровень обучения" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_education_programs[qualification]" placeholder="Квалификация" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_education_programs[form_of_education]" placeholder="Форма обучения" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_education_programs[term_based_on_9_class]" placeholder="Срок обучения на основе 9 классов" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_education_programs[term_based_on_11_class]" placeholder="Срок обучения на основе 11 классов" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_education_programs[study_group_prefix]" placeholder="Префикс учебной группы" required /></div>
                    <div><input type="submit" name="add_item_education_programs" class="button" value="Добавить новую группу" /></div>
                </form>';
                // Обработка формы после отправки
                if (isset($_POST['save_education_programs'])) {

                    if(!$this->check_write($subsection_id))
                    {
                        echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
                        return;
                    }
                    // Валидируйте и очистите данные перед их добавлением в базу данных
                    $data = array_map('sanitize_text_field', $_POST['data_education_programs']);
                    if($_POST['data_education_programs']){
                        foreach ($_POST['data_education_programs'] as $item_id => $item_data) {

                            $item_data_sanitized = array_map('sanitize_text_field', $item_data);
                            $wpdb->update($wpdb->prefix.'reginfoeduorg_education_programs', $item_data_sanitized, array('id' => $item_id));
                        }
                        echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Данные таблицы обновлены.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';

                    }
                    else{
                        echo "<div class='notice notice-error is-dismissible'><p>Вы не выбрали ни одного поля для сохранения изменений.</p></div>";
                    }
                }

                
                echo '<h2>Таблица с группами</h2>
<form method="post">';
                $s = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
                $search_field = isset($_REQUEST['search_field']) ? $_REQUEST['search_field'] : 'full_name';

                echo '<div style="text-align: right;">';
                echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
                echo '<select name="search_field" id="search_field">';
                echo '<option value="major_group"' . ($search_field == 'major_group' ? ' selected' : '') . '>Группа направлений</option>';
                echo '<option value="training_program"' . ($search_field == 'training_program' ? ' selected' : '') . '>Программа обучения</option>';
                echo '<option value="level_of_training"' . ($search_field == 'level_of_training' ? ' selected' : '') . '>Уровень обучения</option>';
                echo '<option value="qualification"' . ($search_field == 'qualification' ? ' selected' : '') . '>Квалификация</option>';
                echo '<option value="form_of_education"' . ($search_field == 'form_of_education' ? ' selected' : '') . '>Форма обучения</option>';
                echo '<option value="term_based_on_9_class"' . ($search_field == 'term_based_on_9_class' ? ' selected' : '') . '>Срок обучения на основе 9 классов</option>';
                echo '<option value="term_based_on_11_class"' . ($search_field == 'term_based_on_11_class' ? ' selected' : '') . '>Срок обучения на основе 11 классов</option>';
                echo '<option value="study_group_prefix"' . ($search_field == 'study_group_prefix' ? ' selected' : '') . '>Префикс учебной группы</option>';
                echo '</select>';

                echo '<input type="search" id="search" name="s" value="' . $s . '" style="margin-left: 10px;" />';
                echo '<input type="submit" name="" id="search-submit" class="button" value="Поиск" />';
                echo '</div>';

                $table = $this->display_table('Education_Programs_Table', 'education_programs', 'Данные о группах отсутствуют.', $subsection_id, $search_field);

                if ('edit_education_programs' === $table->current_action()) {
                    
                    if(!$this->check_write($subsection_id))
                    {
                        echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
                        return;
                    }
                    echo '<div><input type="submit" name="save_education_programs" class="button-primary" value="Сохранить изменения" /></div>';
                }
                echo '</form>';

                //Аккредитация

                // Обработка формы добавления нового элемента
                if (isset($_POST['add_item_accreditation'])) {
                    $this->add_new_item($subsection_id, 'accreditation', $_POST['new_item_accreditation']);
                }

                // Ваша форма для добавления нового элемента
                echo '
                <h2>Добавить новую информацию об аккредитации</h2>
                <form method="post">
                    <div><input style="width: 50%" type="date" name="new_item_accreditation[date_end]" placeholder="Дата окончания аккредитации" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_accreditation[detail]" placeholder="Дополнительное описание" required /></div>
                    <div><input type="submit" name="add_item_accreditation" class="button" value="Добавить новую информацию об аккредитации" /></div>
                </form>';
                // Обработка формы после отправки
                if (isset($_POST['save_accreditation'])) {

                    if(!$this->check_write($subsection_id))
                    {
                        echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
                        return;
                    }
                    // Валидируйте и очистите данные перед их добавлением в базу данных
                    $data = array_map('sanitize_text_field', $_POST['data_accreditation']);
                    if($_POST['data_accreditation']){
                        foreach ($_POST['data_accreditation'] as $item_id => $item_data) {
                            $item_data_sanitized = array_map('sanitize_text_field', $item_data);
                            $wpdb->update($wpdb->prefix.'reginfoeduorg_accreditation', $item_data_sanitized, array('id' => $item_id));
                        }
                        echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Данные таблицы обновлены.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';

                    }
                    else{
                        echo "<div class='notice notice-error is-dismissible'><p>Вы не выбрали ни одного поля для сохранения изменений.</p></div>";
                    }
                }

                
                echo '<h2>Таблица с информацией об аккредитации</h2>
<form method="post">';
                $s = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
                $search_field = isset($_REQUEST['search_field']) ? $_REQUEST['search_field'] : 'full_name';

                echo '<div style="text-align: right;">';
                echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
                echo '<select name="search_field" id="search_field">';
                echo '<option value="date_end"' . ($search_field == 'date_end' ? ' selected' : '') . '>Дата окончания аккредитации</option>';
                echo '<option value="detail"' . ($search_field == 'detail' ? ' selected' : '') . '>Дополнительное описание</option>';
                echo '</select>';
                


                echo '<input type="search" id="search" name="s" value="' . $s . '" style="margin-left: 10px;" />';
                echo '<input type="submit" name="" id="search-submit" class="button" value="Поиск" />';
                echo '</div>';

                $table = $this->display_table('Accreditation_Table', 'accreditation', 'Данные об аккредитации отсутствуют.', $subsection_id, $search_field);

                if ('edit_accreditation' === $table->current_action()) {
                    
                    if(!$this->check_write($subsection_id))
                    {
                        echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
                        return;
                    }
                    echo '<div><input type="submit" name="save_accreditation" class="button-primary" value="Сохранить изменения" /></div>';
                }
                echo '</form>';

                //Информация о результатах

                // Обработка формы добавления нового элемента
                if (isset($_POST['add_item_directions_results_scientific'])) {
                    $this->add_new_item($subsection_id, 'directions_results_scientific', $_POST['new_item_directions_results_scientific']);
                }

                // Ваша форма для добавления нового элемента
                echo '
                <h2>Добавить новую информацию о направлениях и результатах научной (научно-исследовательской) деятельности</h2>
                <form method="post">
                    <div><input style="width: 50%" type="text" name="new_item_directions_results_scientific[detail]" placeholder="Информация" required /></div>
                    <div><input type="submit" name="add_item_directions_results_scientific" class="button" value="Добавить новую информацию" /></div>
                </form>';
                // Обработка формы после отправки
                if (isset($_POST['save_directions_results_scientific'])) {

                    if(!$this->check_write($subsection_id))
                    {
                        echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
                        return;
                    }
                    // Валидируйте и очистите данные перед их добавлением в базу данных
                    $data = array_map('sanitize_text_field', $_POST['data_directions_results_scientific']);
                    if($_POST['data_directions_results_scientific']){
                        foreach ($_POST['data_directions_results_scientific'] as $item_id => $item_data) {
                            $item_data_sanitized = array_map('sanitize_text_field', $item_data);
                            $wpdb->update($wpdb->prefix.'reginfoeduorg_directions_results_scientific', $item_data_sanitized, array('id' => $item_id));
                        }
                        echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Данные таблицы обновлены.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';

                    }
                    else{
                        echo "<div class='notice notice-error is-dismissible'><p>Вы не выбрали ни одного поля для сохранения изменений.</p></div>";
                    }
                }

                
                echo '<h2>Таблица с информацией о направлениях и результатах научной (научно-исследовательской) деятельности</h2>
<form method="post">';
                $s = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
                $search_field = isset($_REQUEST['search_field']) ? $_REQUEST['search_field'] : 'full_name';

                echo '<div style="text-align: right;">';
                echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
                echo '<select name="search_field" id="search_field">';
                echo '<option value="detail"' . ($search_field == 'detail' ? ' selected' : '') . '>Информация</option>';
                echo '</select>';
                


                echo '<input type="search" id="search" name="s" value="' . $s . '" style="margin-left: 10px;" />';
                echo '<input type="submit" name="" id="search-submit" class="button" value="Поиск" />';
                echo '</div>';

                $table = $this->display_table('Directions_Results_Scientific_Table', 'directions_results_scientific', 'Данные о направлениях и результатах научной (научно-исследовательской) деятельности отсутствуют.', $subsection_id, $search_field);

                if ('edit_directions_results_scientific' === $table->current_action()) {
                    
                    if(!$this->check_write($subsection_id))
                    {
                        echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
                        return;
                    }
                    echo '<div><input type="submit" name="save_directions_results_scientific" class="button-primary" value="Сохранить изменения" /></div>';
                }
                echo '</form>';

                //Контингент

                // Обработка формы добавления нового элемента
                if (isset($_POST['add_item_contingent'])) {
                    $this->add_new_item($subsection_id, 'contingent', $_POST['new_item_contingent']);
                }

                // Ваша форма для добавления нового элемента
                echo '
                <h2>Добавить новую информацию о контингенте</h2>
                <form method="post">
                    <div><input style="width: 50%" type="text" name="new_item_contingent[type_education]" placeholder="Тип обучения" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_contingent[name]" placeholder="Наименование" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_contingent[budget]" placeholder="Количество мест на бюджете" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_contingent[contract]" placeholder="Количество мест на договорной основе" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_contingent[foreigners]" placeholder="Количество иностранных граждан" required /></div>
                    <div><input type="submit" name="add_item_contingent" class="button" value="Добавить новую информацию" /></div>
                </form>';
                // Обработка формы после отправки
                if (isset($_POST['save_contingent'])) {

                    if(!$this->check_write($subsection_id))
                    {
                        echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
                        return;
                    }

                    // Валидируйте и очистите данные перед их добавлением в базу данных
                    $data = array_map('sanitize_text_field', $_POST['data_contingent']);

                    if($_POST['data_contingent']){
                        foreach ($_POST['data_contingent'] as $item_id => $item_data) {
                            // Сначала валидируем и очищаем данные
                            $item_data_sanitized = array_map('sanitize_text_field', $item_data);

                            // Проверяем, существует ли такой тип образования в таблице type_education
                            $type_education = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}reginfoeduorg_type_education WHERE type_education = %s", $item_data_sanitized['type_education'] ) );

                            // Если нет, то добавляем его
                            if ( $type_education === null ) {
                                $wpdb->insert($wpdb->prefix.'reginfoeduorg_type_education', array( 'type_education' => $item_data_sanitized['type_education'] ) );
                                $type_education = $wpdb->insert_id;
                            }

                            // Обновляем запись в таблице contingent, используя ID type_education
                            unset($item_data_sanitized['type_education']);
                            $item_data_sanitized['type_education'] = $type_education;

                            $wpdb->update($wpdb->prefix.'reginfoeduorg_contingent', $item_data_sanitized, array('id' => $item_id));
                        }
                        echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Данные таблицы обновлены.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
                    }
                    else{
                        echo "<div class='notice notice-error is-dismissible'><p>Вы не выбрали ни одного поля для сохранения изменений.</p></div>";
                    }
                }


                
                echo '<h2>Таблица с информацией о контингенте</h2>
<form method="post">';
                $s = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
                $search_field = isset($_REQUEST['search_field']) ? $_REQUEST['search_field'] : 'full_name';

                echo '<div style="text-align: right;">';
                echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
                echo '<select name="search_field" id="search_field">';
                echo '<option value="type_education"' . ($search_field == 'type_education' ? ' selected' : '') . '>Тип обучения</option>';
                echo '<option value="name"' . ($search_field == 'name' ? ' selected' : '') . '>Наименование</option>';
                echo '<option value="budget"' . ($search_field == 'budget' ? ' selected' : '') . '>Количество мест на бюджете</option>';
                echo '<option value="contract"' . ($search_field == 'contract' ? ' selected' : '') . '>Количество мест на договорной основе</option>';
                echo '<option value="foreigners"' . ($search_field == 'foreigners' ? ' selected' : '') . '>Количество иностранных граждан</option>';
                echo '</select>';
                


                echo '<input type="search" id="search" name="s" value="' . $s . '" style="margin-left: 10px;" />';
                echo '<input type="submit" name="" id="search-submit" class="button" value="Поиск" />';
                echo '</div>';

                $table = $this->display_table('contingent_Table', 'contingent', 'Данные о контингенте отсутствуют.', $subsection_id, $search_field);

                if ('edit_contingent' === $table->current_action()) {
                    
                    if(!$this->check_write($subsection_id))
                    {
                        echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
                        return;
                    }
                    echo '<div><input type="submit" name="save_contingent" class="button-primary" value="Сохранить изменения" /></div>';
                }
                echo '</form>';

                break;

            case 6:               
                
                // Обработка формы добавления нового элемента
                if (isset($_POST['add_item'])) {
                    $this->add_new_item($subsection_id, 'staff', $_POST['new_item']);
                }
                // Ваша форма для добавления нового элемента
                echo '
                <h2>Добавить нового сотрудника</h2>
                <form method="post">
                    <div><input style="width: 50%" type="text" name="new_item[full_name]" placeholder="ФИО" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[position]" placeholder="Должность" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[email]" placeholder="Email" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[phone]" placeholder="Телефон" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[specialization]" placeholder="Специальность" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[education]" placeholder="Информация об образовании" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[career]" placeholder="Информация о карьере" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[disciplines]" placeholder="Дисциплины" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[qualification_improvement]" placeholder="Повышение квалификации" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[overall_experience]" placeholder="Общий стаж" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[specialization_experience]" placeholder="Стаж по специализации" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[small_image_url]" placeholder="Ссылка на мелкое фото сотрудника" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[big_image_url]" placeholder="Ссылка на крупное фото сотрудника" required /></div>
                    <div><input type="submit" name="add_item" class="button" value="Добавить нового сотрудника" /></div>
                </form>';
                // Обработка формы после отправки
                if (isset($_POST['save'])) {

                    if(!$this->check_write($subsection_id))
                    {
                        echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
                        return;
                    }
                    // Валидируйте и очистите данные перед их добавлением в базу данных
                    $data = array_map('sanitize_text_field', $_POST['data']);
                    if($_POST['data']){
                        foreach ($_POST['data'] as $item_id => $item_data) {
                            $item_data_sanitized = array_map('sanitize_text_field', $item_data);
                            $wpdb->update($wpdb->prefix.'reginfoeduorg_staff', $item_data_sanitized, array('id' => $item_id));
                        }
                        echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Данные таблицы обновлены.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';

                    }
                    else{
                        echo "<div class='notice notice-error is-dismissible'><p>Вы не выбрали ни одного поля для сохранения изменений.</p></div>";
                    }
                }
                
                echo '<h2>Таблица с сотрудниками</h2><form method="post">';
                $s = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
                $search_field = isset($_REQUEST['search_field']) ? $_REQUEST['search_field'] : 'full_name';


                echo '<div style="text-align: right;">';
                echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
                echo '<select name="search_field" id="search_field">';
                echo '<option value="full_name"' . ($search_field == 'full_name' ? ' selected' : '') . '>ФИО</option>';
                echo '<option value="position"' . ($search_field == 'position' ? ' selected' : '') . '>Должность</option>';
                echo '<option value="email"' . ($search_field == 'email' ? ' selected' : '') . '>Email</option>';
                echo '<option value="phone"' . ($search_field == 'phone' ? ' selected' : '') . '>Телефон</option>';
                echo '<option value="specialization"' . ($search_field == 'specialization' ? ' selected' : '') . '>Специальность</option>';
                echo '<option value="education"' . ($search_field == 'education' ? ' selected' : '') . '>Информация об образовании</option>';
                echo '<option value="career"' . ($search_field == 'career' ? ' selected' : '') . '>Информация о карьере</option>';
                echo '<option value="disciplines"' . ($search_field == 'disciplines' ? ' selected' : '') . '>Дисциплины</option>';
                echo '<option value="qualification_improvement"' . ($search_field == 'qualification_improvement' ? ' selected' : '') . '>Повышение квалификации</option>';
                echo '<option value="overall_experience"' . ($search_field == 'overall_experience' ? ' selected' : '') . '>Общий стаж</option>';
                echo '<option value="specialization_experience"' . ($search_field == 'specialization_experience' ? ' selected' : '') . '>Стаж по специализации</option>';
                echo '<option value="small_image_url"' . ($search_field == 'small_image_url' ? ' selected' : '') . '>Ссылка на мелкое фото сотрудника</option>';
                echo '<option value="big_image_url"' . ($search_field == 'big_image_url' ? ' selected' : '') . '>Ссылка на крупное фото сотрудника</option>';
                echo '</select>';

                echo '<input type="search" id="search" name="s" value="' . $s . '" style="margin-left: 10px;" />';
                echo '<input type="submit" name="" id="search-submit" class="button" value="Поиск" />';
                echo '</div>';

                $table =  $this->display_table('Staff_Table', 'staff', 'Данные о сотрудниках отсутствуют.', $subsection_id, $search_field);
                
                if ('edit' === $table->current_action()) {
                    
                    if(!$this->check_write($subsection_id))
                    {
                        echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
                        return;
                    }
                    echo '<div><input type="submit" name="save" class="button-primary" value="Сохранить изменения" /></div>';
                }
                echo '</form>';
                break;
            case 7:       
                // Обработка формы добавления нового элемента для документов
                if (isset($_POST['add_item_documents'])) {
                    $this->add_new_item($subsection_id, 'documents', $_POST['new_item_documents']);
                }

                // Обработка формы добавления нового элемента для специальных условий
                if (isset($_POST['add_item_resources'])) {
                    $this->add_new_item($subsection_id, 'resources', $_POST['new_item_resources']);
                }


                echo '<h2>Добавить новое специальное условие</h2>
                <form method="post">
                    <div><input style="width: 50%" type="text" name="new_item_resources[resource_name]" placeholder="Название ресурса" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_resources[type_name]" placeholder="Тип ресурса" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_resources[details]" placeholder="Детали" required /></div>
                    <div><input type="submit" name="add_item_resources" class="button" value="Добавить новый пункт" /></div>
                </form>';

                // Обработка формы после отправки для специальных условий
                if (isset($_POST['save_resources'])) {
                    $this->update_existing_items($subsection_id, 'resources', $_POST['data_resources'], $_POST['item_resources']);
                }

                

                echo '<h2>Таблица с данными о ресурсах</h2><form method="post">';

                $sf = isset($_REQUEST['sf']) ? $_REQUEST['sf'] : '';
                $search_field_resources = isset($_REQUEST['search_field_resources']) ? $_REQUEST['search_field_resources'] : 'info';

                echo '<div style="text-align: right;">';
                echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
                echo '<select name="search_field_resources" id="search_field_resources">';
                echo '<option value="resource_name"' . ($search_field == 'resource_name' ? ' selected' : '') . '>Название ресурса</option>';
                echo '<option value="type_name"' . ($search_field == 'type_name' ? ' selected' : '') . '>Тип ресурса</option>';
                echo '<option value="details"' . ($search_field == 'details' ? ' selected' : '') . '>Детали</option>';
                echo '</select>';
                echo '<input type="search" id="search_resources" name="s_resources" value="' . $sf . '" style="margin-left: 10px;" />';
                echo '<input type="submit" name="" id="search-submit" class="button" value="Поиск" />';
                echo '</div>';
                $resources_table = $this->display_table('Resources_Table', 'resources', 'Данные о ресурсах отсутствуют.', $subsection_id, $search_field_resources);

                if ('edit_resources' === $resources_table->current_action()) {
                    echo '<div><input type="submit" name="save_resources" class="button-primary" value="Сохранить изменения" /></div>';
                }
                echo '</form>';

                // Ваши формы для добавления нового элемента
                echo '<h2>Добавить новый документ</h2>
                <form method="post">
                    <div><input style="width: 50%" type="text" name="new_item_documents[document_name]" placeholder="Название документа" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_documents[document_type]" placeholder="Тип документа" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_documents[document_link]" placeholder="Ссылка на документ" required /></div>
                    <div><input type="submit" name="add_item_documents" class="button" value="Добавить новый документ" /></div>
                </form>';
                
                
                // Обработка формы после отправки для документов
                if (isset($_POST['save_documents'])) {
                    $this->update_existing_items($subsection_id, 'documents', $_POST['data'], $_POST['item']);
                }

                echo '<h2>Таблица с данными о документах</h2><form method="post">';
                $s = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
                $search_field_documents = isset($_REQUEST['search_field_documents']) ? $_REQUEST['search_field_documents'] : 'document_name';

                echo '<div style="text-align: right;">';
                echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
                echo '<select name="search_field_documents" id="search_field_documents">';
                echo '<option value="document_name"' . ($search_field == 'document_name' ? ' selected' : '') . '>Название документа</option>';
                echo '<option value="dt.document_type"' . ($search_field == 'dt.document_type' ? ' selected' : '') . '>Тип документа</option>';
                echo '<option value="document_link"' . ($search_field == 'document_link' ? ' selected' : '') . '>Ссылка на документ</option>';
                echo '</select>';
                echo '<input type="search" id="search_documents" name="s_documents" value="' . $s . '" style="margin-left: 10px;" />';
                echo '<input type="submit" name="" id="search-submit" class="button" value="Поиск" />';
                echo '</div>';
                $documents_table = $this->display_table('Documents_Table', 'documents', 'Данные о документах отсутствуют.', $subsection_id,$search_field_documents);

                if ('edit' === $documents_table->current_action()) {
                    echo '<div><input type="submit" name="save_documents" class="button-primary" value="Сохранить изменения" /></div>';
                }
                echo '</form>';

                
                break;
            case 3:
            case 4:
            case 8:
            case 9:
            case 10:
            case 11:
                // Обработка формы добавления нового элемента
                if (isset($_POST['add_item'])) {
                    $this->add_new_item($subsection_id, 'documents', $_POST['new_item']);
                }

                // Ваша форма для добавления нового элемента
                echo '
                <h2>Добавить новый документ</h2>
                <form method="post">
                    <div><input style="width: 50%" type="text" name="new_item[document_name]" placeholder="Название документа" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[document_type]" placeholder="Тип документа" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[document_link]" placeholder="Ссылка на документ" required /></div>
                    <div><input type="submit" name="add_item" class="button" value="Добавить новый документ" /></div>
                </form>';
                // Обработка формы после отправки
                if (isset($_POST['save'])) {
                    $this->update_existing_items($subsection_id, 'documents', $_POST['data'], $_POST['item']);
                }

                
                echo '<h2>Таблица с документами</h2><form method="post">';
                $s = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
                $search_field = isset($_REQUEST['search_field']) ? $_REQUEST['search_field'] : 'full_name';

                echo '<div style="text-align: right;">';
                echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
                echo '<select name="search_field" id="search_field">';
                echo '<option value="document_name"' . ($search_field == 'document_name' ? ' selected' : '') . '>Название документа</option>';
                echo '<option value="dt.document_type"' . ($search_field == 'dt.document_type' ? ' selected' : '') . '>Тип документа</option>';
                echo '<option value="document_link"' . ($search_field == 'document_link' ? ' selected' : '') . '>Ссылка на документ</option>';
                echo '</select>';

                echo '<input type="search" id="search" name="s" value="' . $s . '" style="margin-left: 10px;" />';
                echo '<input type="submit" name="" id="search-submit" class="button" value="Поиск" />';
                echo '</div>';
                
                $table =  $this->display_table('Documents_Table', 'documents', 'Данные о документах отсутствуют.', $subsection_id, $search_field);
                
                if ('edit' === $table->current_action()) {
                    
                    if(!$this->check_write($subsection_id))
                    {
                        echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
                        return;
                    }
                    echo '<div><input type="submit" name="save" class="button-primary" value="Сохранить изменения" /></div>';
                }
                echo '</form>';
                
                break;
            case 12:
                // Обработка формы добавления нового элемента для документов
                if (isset($_POST['add_item_documents'])) {
                    $this->add_new_item($subsection_id, 'documents', $_POST['new_item_documents']);
                }

                // Обработка формы добавления нового элемента для специальных условий
                if (isset($_POST['add_item_special_conditions'])) {
                    $this->add_new_item($subsection_id, 'special_conditions', $_POST['new_item_special_conditions']);
                }

                // Ваши формы для добавления нового элемента
                echo '<h2>Добавить новый документ</h2>
                <form method="post">
                    <div><input style="width: 50%" type="text" name="new_item_documents[document_name]" placeholder="Название документа" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_documents[document_type]" placeholder="Тип документа" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_documents[document_link]" placeholder="Ссылка на документ" required /></div>
                    <div><input type="submit" name="add_item_documents" class="button" value="Добавить новый документ" /></div>
                </form>';
                
                
                // Обработка формы после отправки для документов
                if (isset($_POST['save_documents'])) {
                    $this->update_existing_items($subsection_id, 'documents', $_POST['data'], $_POST['item']);
                }

                echo '<h2>Таблица с данными о документах</h2><form method="post">';
                $s = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
                $search_field_documents = isset($_REQUEST['search_field_documents']) ? $_REQUEST['search_field_documents'] : 'document_name';

                echo '<div style="text-align: right;">';
                echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
                echo '<select name="search_field_documents" id="search_field_documents">';
                echo '<option value="document_name"' . ($search_field == 'document_name' ? ' selected' : '') . '>Название документа</option>';
                echo '<option value="dt.document_type"' . ($search_field == 'dt.document_type' ? ' selected' : '') . '>Тип документа</option>';
                echo '<option value="document_link"' . ($search_field == 'document_link' ? ' selected' : '') . '>Ссылка на документ</option>';
                echo '</select>';
                echo '<input type="search" id="search_documents" name="s_documents" value="' . $s . '" style="margin-left: 10px;" />';
                echo '<input type="submit" name="" id="search-submit" class="button" value="Поиск" />';
                echo '</div>';
                $documents_table = $this->display_table('Documents_Table', 'documents', 'Данные о документах отсутствуют.', $subsection_id,$search_field_documents);

                if ('edit' === $documents_table->current_action()) {
                    echo '<div><input type="submit" name="save_documents" class="button-primary" value="Сохранить изменения" /></div>';
                }
                echo '</form>';

                 echo '<h2>Добавить новое специальное условие</h2>
                <form method="post">
                    <div><input style="width: 50%" type="text" name="new_item_special_conditions[info]" placeholder="Пункт" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item_special_conditions[value]" placeholder="Значение" required /></div>
                    <div><input type="submit" name="add_item_special_conditions" class="button" value="Добавить новый пункт" /></div>
                </form>';

                // Обработка формы после отправки для специальных условий
                if (isset($_POST['save_special_conditions'])) {
                    $this->update_existing_items($subsection_id, 'special_conditions', $_POST['data_special_conditions'], $_POST['item_special_conditions']);
                }

                

                echo '<h2>Таблица с данными о специальных условиях</h2><form method="post">';

                $sf = isset($_REQUEST['sf']) ? $_REQUEST['sf'] : '';
                $search_field_special_conditions = isset($_REQUEST['search_field_special_conditions']) ? $_REQUEST['search_field_special_conditions'] : 'info';

                echo '<div style="text-align: right;">';
                echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
                echo '<select name="search_field_special_conditions" id="search_field_special_conditions">';
                echo '<option value="info"' . ($search_field == 'info' ? ' selected' : '') . '>Пункт</option>';
                echo '<option value="value"' . ($search_field == 'value' ? ' selected' : '') . '>Значение</option>';
                echo '</select>';
                echo '<input type="search" id="search_special_conditions" name="s_special_conditions" value="' . $sf . '" style="margin-left: 10px;" />';
                echo '<input type="submit" name="" id="search-submit" class="button" value="Поиск" />';
                echo '</div>';
                $special_conditions_table = $this->display_table('Special_Conditions_Table', 'special_conditions', 'Данные о специальных условиях отсутствуют.', $subsection_id, $search_field_special_conditions);

                if ('edit_special_conditions' === $special_conditions_table->current_action()) {
                    echo '<div><input type="submit" name="save_special_conditions" class="button-primary" value="Сохранить изменения" /></div>';
                }
                echo '</form>';
                break;

           case 13:
                // Обработка формы добавления нового элемента
                if (isset($_POST['add_item'])) {
                    $this->add_new_item($subsection_id, 'international_cooperation', $_POST['new_item']);
                }
                // Ваша форма для добавления нового элемента
                echo '
                <h2>Добавить новую организацию</h2>
                <form method="post">
                    <div><input style="width: 50%" type="text" name="new_item[info]" placeholder="Пункт" required /></div>
                    <div><input style="width: 50%" type="text" name="new_item[value]" placeholder="Значение" required /></div>
                    <div><input type="submit" name="add_item" class="button" value="Добавить новый пункт" /></div>
                </form>';
                // Обработка формы после отправки
                if (isset($_POST['save'])) {                    
                    $this->update_existing_items($subsection_id, 'international_cooperation', $_POST['data'], $_POST['item']);
                }
                
                echo '<h2>Таблица с данными о международном сотрудничестве</h2>
<form method="post">';
                $s = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
                $search_field = isset($_REQUEST['search_field']) ? $_REQUEST['search_field'] : 'full_name';

                echo '<div style="text-align: right;">';
                echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
                echo '<select name="search_field" id="search_field">';
                echo '<option value="info"' . ($search_field == 'info' ? ' selected' : '') . '>Пункт</option>';
                echo '<option value="value"' . ($search_field == 'value' ? ' selected' : '') . '>Значение</option>';
                echo '</select>';

                echo '<input type="search" id="search" name="s" value="' . $s . '" style="margin-left: 10px;" />';
                echo '<input type="submit" name="" id="search-submit" class="button" value="Поиск" />';
                echo '</div>';
                                
                $table =  $this->display_table('International_Cooperation_Table', 'international_cooperation', 'Данные о международном сотрудничестве отсутствуют.', $subsection_id, $search_field);
                
                if ('edit' === $table->current_action()) {
                    
                    if(!$this->check_write($subsection_id))
                    {
                        echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
                        return;
                    }
                    echo '<div><input type="submit" name="save" class="button-primary" value="Сохранить изменения" /></div>';
                }
                echo '</form>';
                break;

            default:
                echo '<p>Страница находится в разработке.</p>';
                break;
        }
    }
    
    //Вывод таблицы
    function display_table($table_class, $table_name, $empty_message, $subsection_id, $search_field) {
        global $wpdb;
        $table = new $table_class($subsection_id, $search_field);
        if('delete_special_conditions' === $table->current_action()) {
                
            foreach ($_POST["item_special_conditions"] as $item_id) {
                    $wpdb->delete($wpdb->prefix.'reginfoeduorg_'.$table_name, array('id' => $item_id));
                }
        } 
        if('delete_education_programs' === $table->current_action()) {
                
            foreach ($_POST["item_education_programs"] as $item_id) {
                    $wpdb->delete($wpdb->prefix.'reginfoeduorg_'.$table_name, array('id' => $item_id));
                }
        }
        if('delete_resources' === $table->current_action()) {
                
            foreach ($_POST["item_resources"] as $item_id) {
                    $wpdb->delete($wpdb->prefix.'reginfoeduorg_'.$table_name, array('id' => $item_id));
                }
        }
        if('delete_accreditation' === $table->current_action()) {
                
            foreach ($_POST["item_accreditation"] as $item_id) {
                    $wpdb->delete($wpdb->prefix.'reginfoeduorg_'.$table_name, array('id' => $item_id));
                }
        }
        if('delete_directions_results_scientific' === $table->current_action()) {
                
            foreach ($_POST["item_directions_results_scientific"] as $item_id) {
                    $wpdb->delete($wpdb->prefix.'reginfoeduorg_'.$table_name, array('id' => $item_id));
                }
        }
        if('delete_contingent' === $table->current_action()) {
                
            foreach ($_POST["item_contingent"] as $item_id) {
                    $wpdb->delete($wpdb->prefix.'reginfoeduorg_'.$table_name, array('id' => $item_id));
                }
        }
        if ('delete' === $table->current_action()) {           
            foreach ($_POST["item"] as $item_id) {
                $wpdb->delete($wpdb->prefix.'reginfoeduorg_'.$table_name, array('id' => $item_id));
            }
        }
        $table->prepare_items();
        
        if (count($table->items) > 0) {
            $table->prepare_items();
            $table->display();
        } else {
            echo '<p>' . $empty_message . '</p>';
        }
        return $table;
    }
    //Добавление элемента
    function add_new_item($subsection_id, $table_name, $post_data) {
        global $wpdb;

        if(!$this->check_write($subsection_id))
        {
            echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
            return;
        }
        switch ($subsection_id)
        {
            case 2:
                $full_name = sanitize_text_field($post_data['full_name']);
                $position = sanitize_text_field($post_data['position']);
                $staff = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}reginfoeduorg_staff WHERE full_name = %s AND position = %s",
                    $full_name,
                    $position
                ));
                if (is_null($staff)) {
                    echo "<div class='notice notice-error is-dismissible'><p>Сотрудника с такими ФИО и должностью не существует. Добавьте его в соответствующем разделе.</p></div>";
                    return;
                }

                $new_item_data = array(
                    'start_date' => $post_data['start_date'],
                    'basis_document' => $post_data['basis_document'],
                    'document_date' => $post_data['document_date'],
                    'document_number' => $post_data['document_number'],
                    'structure_image_url' => $post_data['structure_image_url'],
                    'staff_id' => $staff->id  // Обрабатываем staff_id отдельно
                );
                $new_item_data = array_map('sanitize_text_field', $new_item_data);
                
                $result = $wpdb->insert($wpdb->prefix.'reginfoeduorg_management_structure', $new_item_data);
                break;
            case 3:
            case 4:
            case 7:
            case 8:
            case 9:
            case 10:
            case 11:
            case 12:
                if($table_name == "special_conditions"){
                    // Валидируйте и очистите данные перед их добавлением в базу данных
                    $new_item = array_map('sanitize_text_field', $post_data);
                    $wpdb->insert($wpdb->prefix.'reginfoeduorg_'.$table_name, $new_item);
                    break;
                } 
                if($table_name == "resources"){
                    // Валидируйте и очистите данные перед их добавлением в базу данных
                    $new_item = array_map('sanitize_text_field', $post_data);
                    
                    // Проверяем, существует ли такой тип ресурса в таблице resource_types
                    $resource_type = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}reginfoeduorg_resource_types WHERE type_name = %s", $new_item['type_name'] ) );
                    // Если нет, то добавляем его
                    if ( $resource_type === null ) {
                        $wpdb->insert($wpdb->prefix.'reginfoeduorg_resource_types', array( 'type_name' => $new_item['type_name'] ) );
                        $resource_type = $wpdb->insert_id;
                    }

                    // Добавляем новый ресурс, используя ID resource_type
                    unset($new_item['type_name']);
                    $new_item['resource_type'] = $resource_type;
                    $wpdb->insert($wpdb->prefix.'reginfoeduorg_resources', $new_item);
                    break;
                }               


                    // Валидируйте и очистите данные перед их добавлением в базу данных
                    $new_item = array_map('sanitize_text_field', $post_data);
                    
                    // Проверяем, существует ли такой тип документа в таблице document_type
                    $document_type = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}reginfoeduorg_document_types WHERE document_type = %s", $new_item['document_type'] ) );
                    // Если нет, то добавляем его
                    if ( $document_type === null ) {
                        $wpdb->insert($wpdb->prefix.'reginfoeduorg_document_types', array( 'document_type' => $new_item['document_type'] ) );
                        $document_type = $wpdb->insert_id;
                    }

                    // Добавляем новый документ, используя ID document_type
                    unset($new_item['document_type']);
                    $new_item['document_type'] = $document_type;
                    $new_item['subsection_id'] = $subsection_id;
                    $wpdb->insert($wpdb->prefix.'reginfoeduorg_documents', $new_item);
                break;
          
        	default:
                if($table_name == "contingent"){
                    // Валидируйте и очистите данные перед их добавлением в базу данных
                    $new_item = array_map('sanitize_text_field', $post_data);
                    
                    // Проверяем, существует ли такой тип образования в таблице type_education
                    $type_education = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}reginfoeduorg_type_education WHERE type_education = %s", $new_item['type_education'] ) );
                    
                    // Если нет, то добавляем его
                    if ( $type_education === null ) {
                        $wpdb->insert($wpdb->prefix.'reginfoeduorg_type_education', array( 'type_education' => $new_item['type_education'] ) );
                        $type_education = $wpdb->insert_id;
                    }
                    // Добавляем новую запись в таблицу contingent, используя ID type_education
                    unset($new_item['type_education']);
                    $new_item['type_education'] = $type_education;
                    $wpdb->insert($wpdb->prefix.'reginfoeduorg_contingent', $new_item);
                    break;
                }
                // Валидируйте и очистите данные перед их добавлением в базу данных
                $new_item = array_map('sanitize_text_field', $post_data);
                $wpdb->insert($wpdb->prefix.'reginfoeduorg_'.$table_name, $new_item);
                break;
        }
        
       

        echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Данные успешно добавлены.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
    }
    //Обновление данных
    function update_existing_items($subsection_id, $table_name, $post_data, $post_items) {
        global $wpdb;
            if(!$this->check_write($subsection_id))
            {
                echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
                return;
            }
        
        // Валидируйте и очистите данные перед их добавлением в базу данных
        $data = array_map('sanitize_text_field', $post_data);
        if($post_items){
            
            switch ($subsection_id)
            {
                case 2:
                    foreach ($post_items as $item_id) {

                        // Проверяем, есть ли сотрудник с данным ФИО и должностью в базе данных
                        $staff_id = $wpdb->get_var($wpdb->prepare("
                SELECT id FROM {$wpdb->prefix}reginfoeduorg_staff
                WHERE full_name = %s AND position = %s", $data['full_name'], $data['position']
                        ));

                        // Если сотрудника не существует, выводим сообщение об ошибке
                        if (!$staff_id) {
                            echo "<div class='notice notice-error is-dismissible'><p>Сотрудника с таким ФИО и должностью не существует.</p></div>";
                            continue;
                        }

                        // Обновляем данные
                        $update_data = array(
                            'staff_id' => $staff_id,
                            'start_date' => $data['start_date'],
                            'basis_document' => $data['basis_document'],
                            'document_date' => $data['document_date'],
                            'document_number' => $data['document_number'],
                            'structure_image_url' => $data['structure_image_url']
                        );

                        $wpdb->update($wpdb->prefix.'reginfoeduorg_'.$table_name, $update_data, array('id' => $item_id));
                    }

                    break;
                case 3:
                case 4:
                case 7:
                case 8:
                case 9:
                case 10:
                case 11:
                case 12:
                    if($table_name == "special_conditions"){
                        foreach ($post_items as $item_id) {
                            $wpdb->update($wpdb->prefix.'reginfoeduorg_'.$table_name, $data, array('id' => $item_id));
                        }
                        break;
                    }
                    if($table_name == "resources"){
                        foreach ($post_items as $item_id) {
                            // Сначала валидируем и очищаем данные
                            $update_data = array_map('sanitize_text_field', $data);

                            // Проверяем, существует ли такой тип ресурса в таблице resource_types
                            $resource_type = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}reginfoeduorg_resource_types WHERE type_name = %s", $update_data['type_name'] ) );

                            // Если нет, то добавляем его
                            if ( $resource_type === null ) {
                                $wpdb->insert($wpdb->prefix.'reginfoeduorg_resource_types', array( 'type_name' => $update_data['type_name'] ) );
                                $resource_type = $wpdb->insert_id;
                            }

                            // Обновляем ресурс, используя ID resource_type
                            unset($update_data['type_name']);
                            $update_data['resource_type'] = $resource_type;

                            $wpdb->update($wpdb->prefix.'reginfoeduorg_'.$table_name, $update_data, array('id' => $item_id));
                        }
                        break;
                    }

                    foreach ($post_items as $item_id) {
                        // получаем данные для этого элемента
                        $item_data = array_map('sanitize_text_field', $post_data[$item_id]);
                        // Ищем или добавляем тип документа в базу данных для этого элемента
                        $document_type = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}reginfoeduorg_document_types WHERE document_type = %s", $item_data['document_type'] ) );
                        // Если нет, то добавляем его
                        if ( $document_type === null ) {
                            $wpdb->insert($wpdb->prefix.'reginfoeduorg_document_types', array( 'document_type' => $item_data['document_type'] ) );
                            $document_type = $wpdb->insert_id;
                        }
                        // получаем данные для этого элемента
                        $item_data = array_map('sanitize_text_field', $post_data[$item_id]);

                        // код проверки и обновления типа документа...
                        unset($item_data['subsection_id']);
                        $item_data['document_type'] = $document_type;
                        $item_data['subsection_id'] = $subsection_id;
                        
                        $wpdb->update($wpdb->prefix.'reginfoeduorg_documents', $item_data, array('id' => $item_id));
                    }
                    break;
                default: 

                    foreach ($post_items as $item_id) {
                        $wpdb->update($wpdb->prefix.'reginfoeduorg_'.$table_name, $data, array('id' => $item_id));
                    }
                    break;
            }

            echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Данные таблицы обновлены.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
        }
        else{
            echo "<div class='notice notice-error is-dismissible'><p>Вы не выбрали ни одного поля для сохранения изменений.</p></div>";
        }
    }
    



    //Генерация шорткодов
    function generate_shortcode($subsection_id) {
        if(!$subsection_id)
        {
            return;
        }
        if(!$this->check_write($subsection_id))
        {
            echo "<div class='notice notice-error is-dismissible'><p>У вас нет доступа к редактированию данного подраздела</p></div>";
            return;
        }
        global $wpdb;
        switch ($subsection_id)
        {
            case 1:
                // Выбираем данные из таблицы 
                $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_general_information");
                $shortcode = '[general_info id="' . $id . '"]';                 
                break;
            case 2:
                // Выбираем данные из таблицы 
                $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_management_structure");
                $shortcode = '[management_structure_info id="' . $id . '"]';   
                break;
            case 3:
                // Выбираем данные из таблицы 
                $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_documents WHERE subsection_id = '$subsection_id'");                
                $shortcode = '[documents_info id="' . $id . '"]';
                break; 
            case 4:
                // Выбираем данные из таблицы 
                $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_documents WHERE subsection_id = '$subsection_id'");                
                $shortcode = '[education_standarts_info id="' . $id . '"]';
                break;
            case 5:
                // Выбираем данные из таблицы 
                $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_education_programs");                
                $shortcode = '[education_info id="' . $id . '"]';
                break;
            case 6:
                // Выбираем данные из таблицы 
                $employees = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_staff", ARRAY_A);
                $this->delete_existing_staff();
                foreach ($employees as $employee) {
                    $employee_id = $employee['id'];
                    $shortcode = '[staff_info id="' . $employee_id . '"]';                    
                    // Используйте функцию create_staff_page для создания страницы для сотрудника
                    $employee_page_id = $this->create_staff_page($employee);
                    if ($employee_page_id && $shortcode) {
                        $post = array(
                            'ID' => $employee_page_id,
                            'post_content' => $shortcode,
                        );
                        wp_update_post($post);
                    }
                }
                // Выбираем данные из таблицы 
                $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_staff");                
                $shortcode = '[employees_info id="' . $id . '"]';
                break;
            case 7:
                $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_resources");                
                $shortcode = '[resources_info id="' . $id . '"]';
                break;
            case 8:
                // Выбираем данные из таблицы 
                $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_documents WHERE subsection_id = '$subsection_id'");                
                $shortcode = '[grants_support_info id="' . $id . '"]';
                break;
            case 9:
                // Выбираем данные из таблицы 
                $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_documents WHERE subsection_id = '$subsection_id'");                
                $shortcode = '[paid_services_info id="' . $id . '"]';
                break;
            case 10:
                // Выбираем данные из таблицы 
                $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_documents WHERE subsection_id = '$subsection_id'");                
                $shortcode = '[financial_activity_info id="' . $id . '"]';
                break;
            case 11:
                // Выбираем данные из таблицы 
                $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_documents WHERE subsection_id = '$subsection_id'");                
                $shortcode = '[vacancies_info id="' . $id . '"]';
                break;
            case 12:
                // Выбираем данные из таблицы 
                $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_special_conditions WHERE");                
                $shortcode = '[special_conditions_info id="' . $id . '"]';
                break;
            case 13:
                // Выбираем данные из таблицы 
                $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_international_cooperation WHERE");                
                $shortcode = '[international_cooperation_info id="' . $id . '"]';
                break;
        	default:
        }
        
        global $wpdb;
        $url = $_SERVER['REQUEST_URI'];
        $matches = array();
        preg_match('/reginfoeduorg_subsection_(\d+)/', $url, $matches);
        $subsection_id = isset($matches[1]) ? intval($matches[1]) : 0;
        $subsection_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE id = %d", $subsection_id));
        $post_id = get_page_by_title($subsection_name)->ID;
        $content = $shortcode;        

        // Обновляем контент в таблице reginfoeduorg_site_subsections
        $wpdb->update(
            "{$wpdb->prefix}reginfoeduorg_site_subsections",
            array('content' => $content),
            array('id' => $subsection_id),
            array('%s'),
            array('%d')
        );

        if ($post_id && $content) {
            $post = array(
                'ID' => $post_id,
                'post_content' => $content,
            );
            wp_update_post($post);
        }

        // Выводим сообщение об успешном сохранении изменений
        echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Шорткод создан.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';

    }
    //Создание новых записей в post_type
    function create_staff_page($staff_data) {
        
        // Создаем объект страницы
        $page = array(
               'post_type' => 'staff',
               'post_content' => '',
               'post_author' => 1,
               'post_status' => 'publish',
               'post_title' => $staff_data['full_name'],
               'post_name' => $staff_data['id']  // Это добавит id сотрудника в URL
           );

        // Создаем страницу и получаем ее id
        $page_id = wp_insert_post($page);
        return $page_id;
    }
    //Очистка post_type
    function delete_existing_staff() {
        // Получаем все записи типа 'staff'
        $staff_pages = get_posts(
            array(
                'post_type' => 'staff',
                'post_status' => 'publish',
                'numberposts' => -1
            )
        );
        if($staff_pages){
            // Удаляем каждую запись
            foreach($staff_pages as $page) {
                wp_delete_post($page->ID, true);
            }
        }
    }
    //Проверка на доступ к редактированию данных подраздела
    function check_write($subsection_id)
    {
        if(!$subsection_id)
        {
            return;
        }
        global $wpdb;
        // Получаем текущего пользователя
        $current_user = wp_get_current_user();

        // Получаем ID роли текущего пользователя
        $user_role_id = $wpdb->get_var($wpdb->prepare("SELECT role_id FROM {$wpdb->prefix}reginfoeduorg_users_roles WHERE user_id = %d", $current_user->ID));
        
        // Получаем доступ к подразделам для роли текущего пользователя
        $subsection_access_data = $wpdb->get_results($wpdb->prepare("SELECT ss.id, rsa.write_permission FROM {$wpdb->prefix}reginfoeduorg_site_subsections AS ss JOIN {$wpdb->prefix}reginfoeduorg_role_subsection_access AS rsa ON ss.id = rsa.subsection_id WHERE rsa.role_id = %d", $user_role_id), OBJECT_K);
        
        // Проверяем, есть ли доступ на запись для конкретного подраздела
        if (isset($subsection_access_data[$subsection_id]) && $subsection_access_data[$subsection_id]->write_permission == 1) {
            return true;
        } else {
            return false;
        }


    }
    //-----------------------Процесс обработки и вывода данных в подпунктах меню с настройкой подразделов-------------------------------

}

if(class_exists('RegInfoEduOrg'))
{
    new RegInfoEduOrg(__FILE__);
}

