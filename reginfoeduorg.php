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
        add_shortcode('education_programs_info', array($this,'education_programs_info_shortcode'));

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
    
    public function education_programs_info_shortcode($atts) {
        return $this->process_shortcode($atts, 'Образовательные программы', 'overview', 'education_programs_info');
    } 

    public function staff_info_shortcode($atts) {
        return $this->process_shortcode($atts, 'Руководство. Педагогический (научно-педагогический) состав', 'detail', 'staff_info');
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

            case 3:
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
                // Выбираем данные из таблицы reginfoeduorg_education_programs
                $education_programs_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_education_programs", ARRAY_A);
                
                if (!$education_programs_data) {
                    return null;
                }

                // Загружаем пустую структуру XML и дополняем ее данными
                $xml = new DOMDocument('1.0', 'UTF-8');
                $xml->formatOutput = true;

                // Создаем корневой элемент
                $root = $xml->createElement('education_programs');
                $xml->appendChild($root);

                // Проходимся по всем программам из таблицы и добавляем их в education_programs
                foreach ($education_programs_data as $program) {
                    
                    // Создаем элемент program
                    $program_node = $xml->createElement('program');
                    $root->appendChild($program_node);

                    // Создаем элементы major_group, training_program, level_of_training и другие для каждой программы
                    $major_group = $xml->createElement('major_group', htmlspecialchars($program['major_group']));
                    $training_program = $xml->createElement('training_program', htmlspecialchars($program['training_program']));
                    $level_of_training = $xml->createElement('level_of_training', htmlspecialchars($program['level_of_training']));
                    $qualification = $xml->createElement('qualification', htmlspecialchars($program['qualification']));
                    $form_of_education = $xml->createElement('form_of_education', htmlspecialchars($program['form_of_education']));
                    $term_based_on_9_class = $xml->createElement('term_based_on_9_class', htmlspecialchars($program['term_based_on_9_class']));
                    $term_based_on_11_class = $xml->createElement('term_based_on_11_class', htmlspecialchars($program['term_based_on_11_class']));
                    $study_group_prefix = $xml->createElement('study_group_prefix', htmlspecialchars($program['study_group_prefix']));
                    
                    $program_node->appendChild($major_group);
                    $program_node->appendChild($training_program);
                    $program_node->appendChild($level_of_training);
                    $program_node->appendChild($qualification);
                    $program_node->appendChild($form_of_education);
                    $program_node->appendChild($term_based_on_9_class);
                    $program_node->appendChild($term_based_on_11_class);
                    $program_node->appendChild($study_group_prefix);
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


            default:
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
                id INT(11) NOT NULL AUTO_INCREMENT,
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
                PRIMARY KEY (id)
            ) $charset_collate;


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
		</section_content>
	</section>',
            'xslt' => '',
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
            'name' =>'Образование',
            'xml' => '<section>
		<section_title>Образование</section_title>
		<section_content>			
		</section_content>
	</section>',
            'xslt' => '',
            'xslt_detail' => ''
            ],
            [ 
            'name' =>'Образовательные программы',
            'xml' => '<section>
		<section_title>Образовательные программы</section_title>
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
		</section_content>
	</section>',
            'xslt' => '',
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
    }
    .employee-card {
      flex: 0 0 calc(33% - 20px);
      width: 205px;
      height: 330px;
      border: 1px solid #ccc;
      margin: 10px;
      padding: 10px;
      box-sizing: border-box;
    }
    .employee-card img {
      width: 151px;
      height: 200px;
    }
    .employee-card .name {
      font-size: 16px;
      font-weight: bold;
      margin: 10px 0;
    }
    .employee-card .position {
      font-size: 14px;
      color: #666;
    }
  </style>

  <div class="employee-cards">
    <xsl:for-each select="//staff">
      <div class="employee-card">
        <!-- Используем id сотрудника для создания ссылки -->
        <a href="http://example/?staff={id}">
          <img src="{photo}" alt="{full_name}" />
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
        <div class="employee">
            <p><strong>Должность:</strong> <xsl:value-of select="position"/></p>
            <p><strong>Дисциплины:</strong> <xsl:value-of select="disciplines"/></p>
            <p><strong>Образование:</strong> <xsl:value-of select="education"/></p>
            <p><strong>Специальность:</strong> <xsl:value-of select="specialization"/></p>
            <p><strong>Повышение квалификации:</strong> <xsl:value-of select="qualification_improvement"/></p>
            <p><strong>Общий стаж (лет):</strong> <xsl:value-of select="overall_experience"/></p>
            <p><strong>Стаж по специальности (лет):</strong> <xsl:value-of select="specialization_experience"/></p>
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
				<classrooms>
					<classroom>
						<name>Наименование учебного кабинета</name>
						<type>Тип учебного кабинета</type>
						<equipment>Список оборудования в кабинете</equipment>
						<accessibility>Доступность для инвалидов и лиц с ограниченными возможностями здоровья</accessibility>
					</classroom>
				</classrooms>
				<training_objects>
					<training_object>
						<name>Наименование объекта для практических занятий</name>
						<type>Тип объекта</type>
						<equipment>Список оборудования на объекте</equipment>
						<accessibility>Доступность для инвалидов и лиц с ограниченными возможностями здоровья</accessibility>
					</training_object>
				</training_objects>
				<library>
					<name>Название библиотеки</name>
					<collection>Описание коллекции книг и других материалов в библиотеке</collection>
					<services>Описание услуг, предоставляемых библиотекой</services>
					<accessibility>Доступность для инвалидов и лиц с ограниченными возможностями здоровья</accessibility>
				</library>
				<sports_facilities>
					<sports_facility>
						<name>Наименование объекта спорта</name>
						<type>Тип объекта</type>
						<equipment>Список оборудования на объекте</equipment>
						<accessibility>Доступность для инвалидов и лиц с ограниченными возможностями здоровья</accessibility>
					</sports_facility>
				</sports_facilities>
				<learning_resources>
					<resource>
						<name>Наименование средства обучения и воспитания</name>
						<type>Тип средства</type>
						<accessibility>Доступность для инвалидов и лиц с ограниченными возможностями здоровья</accessibility>
					</resource>
				</learning_resources>
				<facilities_for_disabled>
					<facility>
						<name>Наименование объекта, оборудованного для использования инвалидами и лицами с ограниченными возможностями здоровья</name>
						<type>Тип объекта</type>
						<equipment>Список оборудования на объекте</equipment>
					</facility>
				</facilities_for_disabled>
			</technical_equipment>
		</section_content>
	</section>',
            'xslt' => '',
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
              <li>Импорт данных из XML файла, с выгруженной информацией из сторонних информационных систем, таких как 1С. <a href="<?php echo plugins_url( 'structure.xml', __FILE__ ); ?>" target="_blank" style="text-decoration: none; color: #007bff;">Структура XML файла</a></li>
              <li>Внешняя обработка для экспорта данных из информационной системы 1С. <a href="<?php echo plugins_url( 'export.csv', __FILE__ ); ?>" target="_blank" style="text-decoration: none; color: #007bff;"> Пример внешней обработки</a></li>
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

        //Обработчик сохранения изменений в таблице
        if (isset($_POST['save_table_changes'])) {            
            $this->save_table_changes($subsection_id);
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
        echo '<form method="post" action="" enctype="multipart/form-data">';

        //Заголовок - название подраздела
        echo '<h1>' . $subsection_name . '</h1>';

        // Данные в таблице
        echo '<h3>Таблица данных подраздела</h3>';
        
        $this->table_data($subsection_id);   
        
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
            case 3:
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

                // Очищаем таблицу перед импортом
                global $wpdb;
                $table_programs = "{$wpdb->prefix}reginfoeduorg_education_programs";

                $wpdb->query("DELETE FROM $table_programs");

                // Проходимся по всем программам
                foreach ($section->educational_programs->program as $program) {
                    // Получаем данные
                    $major_group = (string)$program->major_group;
                    $training_program = (string)$program->training_program;
                    $level_of_training = (string)$program->level_of_training;
                    $qualification = (string)$program->qualification;
                    $form_of_education = (string)$program->form_of_education;
                    $term_based_on_9_class = (string)$program->term_based_on_9_class;
                    $term_based_on_11_class = (string)$program->term_based_on_11_class;
                    $study_group_prefix = (string)$program->study_group_prefix;

                    // Создаем массив с данными для таблицы programs
                    $data_programs = array(
                        'major_group' => $major_group,
                        'training_program' => $training_program,
                        'level_of_training' => $level_of_training,
                        'qualification' => $qualification,
                        'form_of_education' => $form_of_education,
                        'term_based_on_9_class' => $term_based_on_9_class,
                        'term_based_on_11_class' => $term_based_on_11_class,
                        'study_group_prefix' => $study_group_prefix,
                    );

                    // Вставляем данные в таблицу programs
                    if ($wpdb->insert($table_programs, $data_programs) === false) {
                        // Выводим сообщение об ошибке при вставке данных
                        echo "<div class='notice notice-error is-dismissible'><p>Ошибка при вставке информации о программе в таблицу: " . $wpdb->last_error . "</p></div>";
                        break;
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
                    );

                    // Вставляем данные в таблицу staff
                    if ($wpdb->insert($table_staff, $data_staff) === false) {
                        // Выводим сообщение об ошибке при вставке данных
                        echo "<div class='notice notice-error is-dismissible'><p>Ошибка при вставке информации о сотруднике в таблицу: " . $wpdb->last_error . "</p></div>";
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
    //Сохранение изменений в таблице
    function save_table_changes($subsection_id)
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
        switch ($subsection_id)
        {
            case 1:
                $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_general_information");
                $data_to_update = $_POST['data'];
                $data_keys = array_keys($data_to_update);
                $data_values = array_values($data_to_update);

                for ($i = 0; $i < count($data_to_update); $i++) {
                    $wpdb->update(
                        "{$wpdb->prefix}reginfoeduorg_general_information",
                        array($data_keys[$i] => $data_values[$i]),
                        array('id' => $id),
                        array('%s'),
                        array('%d')
                    );
                }
                break;
            case 3:
            case 8:
            case 9:
            case 10:
            case 11:
                $data = $wpdb->get_results($wpdb->prepare("SELECT d.id, d.document_name, dt.id as dt_id, dt.document_type, d.document_link 
                 FROM {$wpdb->prefix}reginfoeduorg_documents as d
                 JOIN {$wpdb->prefix}reginfoeduorg_document_types as dt
                 ON d.document_type = dt.id
                 WHERE d.subsection_id = %d", $subsection_id), ARRAY_A);
                foreach ($data as $row) {
                    $id = $row['id']; // Получаем ID документа
                    $new_name = $_POST['document_name'][$id]; // Получаем новое название из формы
                    $new_type_id = $_POST['document_type'][$id]; // Получаем новый тип из формы
                    $new_link = $_POST['document_link'][$id]; // Получаем новую ссылку из формы
                    
                    // Обновляем данные в базе данных
                    $wpdb->update(
                        "{$wpdb->prefix}reginfoeduorg_documents", // Название таблицы
                        array(
                            'document_name' => $new_name, 
                            'document_type' => $new_type_id, 
                            'document_link' => $new_link
                        ), // Данные для обновления
                        array('id' => $id), // Условие WHERE
                        array('%s', '%d', '%s'), // Формат данных для обновления
                        array('%d')  // Формат данных в условии WHERE
                    );
                }
                break;
            case 5:
                $data = $wpdb->get_results($wpdb->prepare("SELECT id, major_group, training_program, level_of_training, qualification, form_of_education, term_based_on_9_class, term_based_on_11_class, study_group_prefix FROM {$wpdb->prefix}reginfoeduorg_education_programs"), ARRAY_A);

                foreach ($data as $row) {
                    $id = $row['id']; // Получаем ID программы
                    $new_major_group = $_POST['major_group'][$id]; // Получаем новую главную группу из формы
                    $new_training_program = $_POST['training_program'][$id]; // Получаем новую программу обучения из формы
                    $new_level_of_training = $_POST['level_of_training'][$id]; // Получаем новый уровень обучения из формы
                    $new_qualification = $_POST['qualification'][$id]; // Получаем новую квалификацию из формы
                    $new_form_of_education = $_POST['form_of_education'][$id]; // Получаем новую форму обучения из формы
                    $new_term_based_on_9_class = $_POST['term_based_on_9_class'][$id]; // Получаем новый срок на основе 9 класса из формы
                    $new_term_based_on_11_class = $_POST['term_based_on_11_class'][$id]; // Получаем новый срок на основе 11 класса из формы
                    $new_study_group_prefix = $_POST['study_group_prefix'][$id]; // Получаем новый префикс группы изучения из формы

                    // Обновляем данные в базе данных
                    $wpdb->update(
                        "{$wpdb->prefix}reginfoeduorg_education_programs", // Название таблицы
                        array(
                            'major_group' => $new_major_group,
                            'training_program' => $new_training_program,
                            'level_of_training' => $new_level_of_training,
                            'qualification' => $new_qualification,
                            'form_of_education' => $new_form_of_education,
                            'term_based_on_9_class' => $new_term_based_on_9_class,
                            'term_based_on_11_class' => $new_term_based_on_11_class,
                            'study_group_prefix' => $new_study_group_prefix,
                        ), // Данные для обновления
                        array('id' => $id), // Условие WHERE
                        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'), // Формат данных для обновления
                        array('%d')  // Формат данных в условии WHERE
                    );
                }
                break;

            case 6:
                $data = $wpdb->get_results($wpdb->prepare("SELECT id, full_name, position, email, phone, disciplines, education, specialization, qualification_improvement, career, overall_experience, specialization_experience FROM {$wpdb->prefix}reginfoeduorg_staff"), ARRAY_A);
                
                foreach ($data as $row) {
                    $id = $row['id']; // Получаем ID сотрудника
                    $new_full_name = $_POST['full_name'][$id]; // Получаем новое имя из формы
                    $new_position = $_POST['position'][$id]; // Получаем новую должность из формы
                    $new_email = $_POST['email'][$id]; // Получаем новый email из формы
                    $new_phone = $_POST['phone'][$id]; // Получаем новый телефон из формы
                    $new_overall_experience = $_POST['overall_experience'][$id]; // Получаем новый общий опыт из формы
                    $new_specialization_experience = $_POST['specialization_experience'][$id]; // Получаем новый специализированный опыт из формы
                    $new_education = stripslashes($_POST['education'][$id]); // Получаем новую информацию об образовании из формы
                    $new_career = stripslashes($_POST['career'][$id]); // Получаем новую информацию о карьере из формы
                    $new_disciplines = stripslashes($_POST['disciplines'][$id]); // Получаем новую дисциплину из формы
                    $new_qualification_improvement = stripslashes($_POST['qualification_improvement'][$id]); // Получаем новую информацию о повышении квалификации из формы
                    
                    // Обновляем данные в базе данных
                    $wpdb->update(
                        "{$wpdb->prefix}reginfoeduorg_staff", // Название таблицы
                        array(
                            'full_name' => $new_full_name,
                            'position' => $new_position,
                            'email' => $new_email,
                            'phone' => $new_phone,
                            'overall_experience' => $new_overall_experience,
                            'specialization_experience' => $new_specialization_experience,
                            'education' => $new_education,
                            'career' => $new_career,
                            'disciplines' => $new_disciplines,
                            'qualification_improvement' => $new_qualification_improvement
                        ), // Данные для обновления
                        array('id' => $id), // Условие WHERE
                        array('%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s'), // Формат данных для обновления
                        array('%d')  // Формат данных в условии WHERE
                    );
                }
                break;





            

            default:
        }
        echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Данные таблицы обновлены.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';

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
        if(!$subsection_id)
        {
            return;
        }
        global $wpdb;
        switch ($subsection_id) {
            case 1:
                $data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_general_information", ARRAY_A);
                if ($data) 
                {                   

                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr>';
                    echo '<th scope="col" class="manage-column column-name">Название поля</th>';
                    echo '<th scope="col" class="manage-column column-value">Значение</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';
                    $fields = array(
                        'Полное название образовательной организации' => 'full_name',
                        'Краткое название образовательной организации' => 'short_name',
                        'Дата создания образовательной организации' => 'creation_date',
                        'Учредитель' => 'founder',
                        'Место нахождения образовательной организации' => 'location',
                        'Филиалы образовательной организации' => 'branches',
                        'График работы' => 'working_hours',
                        'Контактные телефоны' => 'contact_phones',
                        'Адреса электронной почты' => 'email_addresses',
                    );

                    foreach ($data as $row) 
                    {
                        foreach ($fields as $field_name => $field_key) 
                        {
                            echo '<tr>';
                            echo '<td class="column-name">' . $field_name . '</td>';
                            echo '<td class="column-value"><input type="text" name="data[' . $field_key . ']" value="' . $row[$field_key] . '"></td>';
                            echo '</tr>';
                        }
                    }
                    echo '</tbody>';
                    echo '</table>';
                    echo '<input type="submit" name="save_table_changes[' . $row['id'] . ']" value="Сохранить изменения в таблице" class="button-primary">';
                } 
                else 
                {
                    echo '<p>Данные отсутствуют.</p>';
                }
                break;
            case 5:
                echo '<style>
        .wp-list-table input, .wp-list-table textarea {
            width: 100%;
            box-sizing: border-box;
        }
    </style>
    ';

                // Получаем данные из таблицы образовательных программ
                $data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_education_programs", ARRAY_A);

                if ($data) {
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr>';
                    echo '<th>Группа направлений</th>';
                    echo '<th>Программа обучения</th>';
                    echo '<th>Уровень обучения</th>';
                    echo '<th>Квалификация</th>';
                    echo '<th>Форма обучения</th>';
                    echo '<th>Срок обучения на основе 9 классов</th>';
                    echo '<th>Срок обучения на основе 11 классов</th>';
                    echo '<th>Префикс учебной группы</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';

                    foreach ($data as $row) {
                        echo '<tr>';
                        echo '<td><input type="text" name="major_group[' . $row['id'] . ']" value="' . $row['major_group'] . '"></td>';
                        echo '<td><input type="text" name="training_program[' . $row['id'] . ']" value="' . $row['training_program'] . '"></td>';
                        echo '<td><input type="text" name="level_of_training[' . $row['id'] . ']" value="' . $row['level_of_training'] . '"></td>';
                        echo '<td><input type="text" name="qualification[' . $row['id'] . ']" value="' . $row['qualification'] . '"></td>';
                        echo '<td><input type="text" name="form_of_education[' . $row['id'] . ']" value="' . $row['form_of_education'] . '"></td>';
                        echo '<td><input type="text" name="term_based_on_9_class[' . $row['id'] . ']" value="' . $row['term_based_on_9_class'] . '"></td>';
                        echo '<td><input type="text" name="term_based_on_11_class[' . $row['id'] . ']" value="' . $row['term_based_on_11_class'] . '"></td>';
                        echo '<td><input type="text" name="study_group_prefix[' . $row['id'] . ']" value="' . $row['study_group_prefix'] . '"></td>';
                        echo '</tr>';
                    }

                    echo '</tbody>';
                    echo '</table>';
                    echo '<input type="submit" name="save_table_changes" value="Сохранить изменения в таблице" class="button-primary">';
                } else {
                    echo '<p>Данные отсутствуют.</p>';
                }
                break;

            case 6:
                echo '<style>
                    .wp-list-table input, .wp-list-table textarea {
                        width: 100%;
                        box-sizing: border-box;
                    }
                </style>
                ';

                // Получаем данные из таблицы staff
                $data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_staff", ARRAY_A);

                if ($data) {
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr>';
                    echo '<th>ФИО</th>';
                    echo '<th>Должность</th>';
                    echo '<th>Email</th>';
                    echo '<th>Телефон</th>';
                    echo '<th>Общий стаж</th>';
                    echo '<th>Стаж по специализации</th>';
                    echo '<th>Информация об образовании</th>';
                    echo '<th>Информация о карьере</th>';
                    echo '<th>Дисциплины</th>';
                    echo '<th>Повышение квалификации</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';

                    foreach ($data as $row) {
                        echo '<tr>';
                        echo '<td><input type="text" name="full_name[' . $row['id'] . ']" value="' . $row['full_name'] . '"></td>';
                        echo '<td><input type="text" name="position[' . $row['id'] . ']" value="' . $row['position'] . '"></td>';
                        echo '<td><input type="text" name="email[' . $row['id'] . ']" value="' . $row['email'] . '"></td>';
                        echo '<td><input type="text" name="phone[' . $row['id'] . ']" value="' . $row['phone'] . '"></td>';
                        echo '<td><input type="number" name="overall_experience[' . $row['id'] . ']" value="' . $row['overall_experience'] . '"></td>';
                        echo '<td><input type="number" name="specialization_experience[' . $row['id'] . ']" value="' . $row['specialization_experience'] . '"></td>';
                        echo '<td><textarea name="education[' . $row['id'] . ']">' . $row['education'] . '</textarea></td>';
                        echo '<td><textarea name="career[' . $row['id'] . ']">' . $row['career'] . '</textarea></td>';
                        echo '<td><textarea name="disciplines[' . $row['id'] . ']">' . $row['disciplines'] . '</textarea></td>';
                        echo '<td><textarea name="qualification_improvement[' . $row['id'] . ']">' . $row['qualification_improvement'] . '</textarea></td>';
                        echo '</tr>';
                    }

                    echo '</tbody>';
                    echo '</table>';
                    echo '<input type="submit" name="save_table_changes" value="Сохранить изменения в таблице" class="button-primary">';
                } else {
                    echo '<p>Данные отсутствуют.</p>';
                }
                break;


            case 3:
            case 8:
            case 9:
            case 10:
            case 11:
                $data = $wpdb->get_results($wpdb->prepare("SELECT d.id, d.document_name, dt.id as dt_id, dt.document_type, d.document_link 
             FROM {$wpdb->prefix}reginfoeduorg_documents as d
             JOIN {$wpdb->prefix}reginfoeduorg_document_types as dt
             ON d.document_type = dt.id
             WHERE d.subsection_id = %d", $subsection_id), ARRAY_A);
                $document_types = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_document_types", ARRAY_A);
                
                if ($data) {
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr>';
                    echo '<th scope="col" class="manage-column column-name">Название документа</th>';
                    echo '<th scope="col" class="manage-column column-name">Тип документа</th>';
                    echo '<th scope="col" class="manage-column column-value">Ссылка на документ</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';

                    foreach ($data as $row) {
                        
                        echo '<tr>';
                        echo '<td class="column-name"><input type="text" name="document_name[' . $row['id'] . ']" value="' . $row['document_name'] . '"></td>';
                        echo '<td class="column-name">';                        
                        // Создаем выпадающий список с типами документов
                        echo '<select name="document_type[' . $row['id'] . ']">';
                        
                        foreach ($document_types as $type) {
                            
                            $selected = $type['id'] == $row['dt_id'] ? ' selected' : '';
                            echo '<option value="' . $type['id'] . '"' . $selected . '>' . $type['document_type'] . '</option>';
                        }

                        echo '</select>';
                        echo '</td>';
                        echo '<td class="column-value"><input type="text" name="document_link[' . $row['id'] . ']" value="' . $row['document_link'] . '"></td>';
                        echo '</tr>';
                    }

                    echo '</tbody>';
                    echo '</table>';
                    echo '<input type="submit" name="save_table_changes" value="Сохранить изменения в таблице" class="button-primary">';

                } else {
                    echo '<p>Данные отсутствуют.</p>';
                }

                break;

            default:
                echo '<p>Страница находится в разработке.</p>';
                break;
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
            case 3:
                // Выбираем данные из таблицы 
                $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_documents WHERE subsection_id = '$subsection_id'");                
                $shortcode = '[documents_info id="' . $id . '"]';
                break;
            case 5:
                // Выбираем данные из таблицы 
                $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_education_programs");                
                $shortcode = '[education_programs_info id="' . $id . '"]';
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
