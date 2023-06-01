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
        add_shortcode('general_info', array($this,'general_info_shortcode'));
        add_shortcode('documents_info', array($this,'documents_info_shortcode'));
        add_shortcode('paid_services_info', array($this,'paid_services_shortcode'));
    }
    
    //-------------------------------Шорткоды-------------------------------

    public function general_info_shortcode($atts) {
        global $wpdb;
        // Извлекаем ID из атрибутов шорткода
        $id = $atts['id'];
        
        $subsection_id = 1;
        $xml = $this->generate_xml($subsection_id);
        
        if (!$xml) {
            return null;
        }

        $xslt_code = $wpdb->get_var("SELECT xslt FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE name = 'Основные сведения'");


        // Преобразуем XML контент в HTML с использованием вашего XSLT-преобразования
        $html_content =  $this->convert_xml_xslt_to_html($xml, $xslt_code,$subsection_id);

        // Возвращаем HTML-контент, который заменит шорткод на странице
        return $html_content;
    }

    public function documents_info_shortcode($atts) {
        global $wpdb;
        // Извлекаем ID из атрибутов шорткода
        $id = $atts['id'];
        $subsection_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE name = 'Документы'");
        $xml = $this->generate_xml($subsection_id);
        
        if (!$xml) {
            return null;
        }

        $xslt_code = $wpdb->get_var("SELECT xslt FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE name = 'Документы'");


        // Преобразуем XML контент в HTML с использованием вашего XSLT-преобразования
        $html_content =  $this->convert_xml_xslt_to_html($xml, $xslt_code,$subsection_id);

        // Возвращаем HTML-контент, который заменит шорткод на странице
        return $html_content;
    }

    public function paid_services_shortcode($atts) {
        global $wpdb;
        // Извлекаем ID из атрибутов шорткода
        $id = $atts['id'];
        $subsection_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE name = 'Платные образовательные услуги'");
        $xml = $this->generate_xml($subsection_id);
        
        if (!$xml) {
            return null;
        }

        $xslt_code = $wpdb->get_var("SELECT xslt FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE name = 'Платные образовательные услуги'");


        // Преобразуем XML контент в HTML с использованием вашего XSLT-преобразования
        $html_content =  $this->convert_xml_xslt_to_html($xml, $xslt_code,$subsection_id);
    }
    
    //-------------------------------Шорткоды-------------------------------


    //-------------------------------Обработка вывода html документов при активации шорткодов-------------------------------
    function convert_xml_xslt_to_html($xml, $xslt_code, $subsection_id) {
        // Создаем экземпляр XSLTProcessor и загружаем XSLT-код
        $xslt_processor = new XSLTProcessor();
        $xslt = new DOMDocument();
        $xslt->loadXML($xslt_code);
        $xslt_processor->importStylesheet($xslt);
        
        // Применяем XSLT-код к XML данным
        $transformed_data = $xslt_processor->transformToXml($xml); // Применяем XSLT-преобразование
        return $transformed_data;
    }


    function generate_xml($subsection_id) {
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
                // Выбираем данные из таблицы reginfoeduorg_documents
                $documents_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_documents", ARRAY_A);
                if (!$documents_data) {
                    return null;
                }
                // Выбираем пустую структуру XML для подраздела "Документы" из базы данных
                $subsection_xml = $wpdb->get_var("SELECT xml FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE name = 'Документы'");
                
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
                    $document_type = $wpdb->get_var("SELECT document_type FROM {$wpdb->prefix}reginfoeduorg_documents_types WHERE id = $document_type_id");

                    // Создаем элементы type, name и link для каждого документа
                    $type = $xml->createElement('type', htmlspecialchars($document_type));
                    $name = $xml->createElement('name', htmlspecialchars($document['document_name']));
                    $link = $xml->createElement('link', htmlspecialchars($document['document_link']));
                    $document_node->appendChild($type);
                    $document_node->appendChild($name);
                    $document_node->appendChild($link);

                }
                break;

            default:
        }
        return $xml;
    }
    //-------------------------------Обработка вывода html документов при активации шорткодов-------------------------------





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
        $table_disciplines = $wpdb->prefix.'reginfoeduorg_disciplines';
        $table_education = $wpdb->prefix.'reginfoeduorg_education';
        $table_qualification_improvement = $wpdb->prefix.'reginfoeduorg_qualification_improvement';
        $table_career = $wpdb->prefix.'reginfoeduorg_career';
        $table_management_structure = $wpdb->prefix . 'reginfoeduorg_management_structure';
        $table_management = $wpdb->prefix . 'reginfoeduorg_management';
        $table_founders = $wpdb->prefix . 'reginfoeduorg_founders';
        $table_meetings = $wpdb->prefix . 'reginfoeduorg_meetings';
        $table_documents = $wpdb->prefix . 'reginfoeduorg_documents';
        $table_documents_types = $wpdb->prefix . 'reginfoeduorg_documents_types';
        $table_education_programs = $wpdb->prefix . 'reginfoeduorg_education_programs';
        $table_free_education = $wpdb->prefix . 'reginfoeduorg_free_education';
        $table_property = $wpdb->prefix . 'reginfoeduorg_property';
        $table_scholarships = $wpdb->prefix . 'reginfoeduorg_scholarships';
        $table_social_support = $wpdb->prefix . 'reginfoeduorg_social_support';
        $table_dormitories = $wpdb->prefix . 'reginfoeduorg_dormitories';
        $table_employment = $wpdb->prefix . 'reginfoeduorg_employment';
        $table_paid_services = $wpdb->prefix . 'reginfoeduorg_paid_services';
        $table_funding_sources = $wpdb->prefix . 'reginfoeduorg_funding_sources';
        $table_financial_report = $wpdb->prefix . 'reginfoeduorg_financial_report';
        $table_vacancies = $wpdb->prefix . 'reginfoeduorg_vacancies';
        $table_educational_standards = $wpdb->prefix . 'reginfoeduorg_educational_standards';


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
                overall_experience INT NOT NULL,
                specialization_experience INT NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;

            CREATE TABLE $table_disciplines (
                id INT(11) NOT NULL AUTO_INCREMENT,
                staff_id INT(11) NOT NULL,
                discipline TEXT NOT NULL,
                PRIMARY KEY (id),
                FOREIGN KEY (staff_id) REFERENCES $table_staff(id) ON DELETE CASCADE ON UPDATE CASCADE
            ) $charset_collate;

            CREATE TABLE $table_education (
                id INT(11) NOT NULL AUTO_INCREMENT,
                staff_id INT(11) NOT NULL,
                education_info TEXT NOT NULL,
                PRIMARY KEY (id),
                FOREIGN KEY (staff_id) REFERENCES $table_staff(id) ON DELETE CASCADE ON UPDATE CASCADE
            ) $charset_collate;

            CREATE TABLE $table_qualification_improvement (
                id INT(11) NOT NULL AUTO_INCREMENT,
                staff_id INT(11) NOT NULL,
                improvement_info TEXT NOT NULL,
                PRIMARY KEY (id),
                FOREIGN KEY (staff_id) REFERENCES $table_staff(id) ON DELETE CASCADE ON UPDATE CASCADE
            ) $charset_collate;
            
            CREATE TABLE $table_career (
                id INT(11) NOT NULL AUTO_INCREMENT,
                staff_id INT(11) NOT NULL,
                career_info TEXT NOT NULL,
                PRIMARY KEY (id),
                FOREIGN KEY (staff_id) REFERENCES $table_staff(id) ON DELETE CASCADE ON UPDATE CASCADE
            ) $charset_collate;

            CREATE TABLE $table_site_subsections (
                id INT(11) NOT NULL AUTO_INCREMENT,
                name TEXT NOT NULL,
                content LONGTEXT NOT NULL,
                xslt LONGTEXT NOT NULL,
                xml LONGTEXT NOT NULL,
                visible BOOLEAN NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;


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

            CREATE TABLE $table_management_structure (
              id INT AUTO_INCREMENT PRIMARY KEY,
              management_title VARCHAR(255),
              management_info TEXT
            )$charset_collate;

            CREATE TABLE $table_management (
              id INT AUTO_INCREMENT PRIMARY KEY,
              management_post VARCHAR(255),
              management_fullname VARCHAR(255),
              management_reassignment VARCHAR(255),
              management_biography TEXT
            )$charset_collate;

            CREATE TABLE $table_founders (
              id INT AUTO_INCREMENT PRIMARY KEY,
              founders_title VARCHAR(255),
              founders_info TEXT
            )$charset_collate;

            CREATE TABLE $table_meetings (
              id INT AUTO_INCREMENT PRIMARY KEY,
              meeting_title VARCHAR(255),
              meeting_info TEXT
            )$charset_collate;

             CREATE TABLE $table_documents_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                document_type VARCHAR(255)
            )$charset_collate;

            CREATE TABLE $table_documents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                document_name VARCHAR(255),
                document_type INT(11) NOT NULL,
                FOREIGN KEY (document_type) REFERENCES $table_documents_types(id) ON DELETE CASCADE,
                document_link VARCHAR(255)
            )$charset_collate;

            CREATE TABLE $table_education_programs (
              id INT AUTO_INCREMENT PRIMARY KEY,
              program_title VARCHAR(255),
              program_info TEXT
            )$charset_collate;

            CREATE TABLE $table_free_education (
              id INT AUTO_INCREMENT PRIMARY KEY,
              free_education_title VARCHAR(255),
              free_education_info TEXT
            )$charset_collate;

            CREATE TABLE $table_property (
              id INT AUTO_INCREMENT PRIMARY KEY,
              property_title VARCHAR(255),
              property_info TEXT
            )$charset_collate;

            CREATE TABLE $table_scholarships (
              id INT AUTO_INCREMENT PRIMARY KEY,
              scholarship_title VARCHAR(255),
              scholarship_info TEXT
            )$charset_collate;

            CREATE TABLE $table_social_support (
              id INT AUTO_INCREMENT PRIMARY KEY,
              support_title VARCHAR(255),
              support_info TEXT
            )$charset_collate;

            CREATE TABLE $table_dormitories (
              id INT AUTO_INCREMENT PRIMARY KEY,
              dormitory_title VARCHAR(255),
              dormitory_info TEXT
            )$charset_collate;

            CREATE TABLE $table_employment (
              id INT AUTO_INCREMENT PRIMARY KEY,
              employment_title VARCHAR(255),
              employment_info TEXT
            )$charset_collate;

            CREATE TABLE $table_paid_services (
              id INT AUTO_INCREMENT PRIMARY KEY,
              document_type VARCHAR(255),
              document_link VARCHAR(255)
            )$charset_collate;

            CREATE TABLE $table_funding_sources (
              id INT AUTO_INCREMENT PRIMARY KEY,
              funding_title VARCHAR(255),
              funding_info TEXT
            )$charset_collate;

            CREATE TABLE $table_financial_report (
              id INT AUTO_INCREMENT PRIMARY KEY,
              report_title VARCHAR(255),
              report_info TEXT
            )$charset_collate;

            CREATE TABLE $table_vacancies (
              id INT AUTO_INCREMENT PRIMARY KEY,
              vacancies_info TEXT
            )$charset_collate;

            CREATE TABLE $table_educational_standards (
              id INT AUTO_INCREMENT PRIMARY KEY,
              standard_title VARCHAR(255),
              standard_info TEXT,
              standard_file VARCHAR(255)
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
'
            ],
            [ 
            'name' =>'Структура и органы управления образовательной организацией',
            'xml' => '<section>
		<section_title>Структура и органы управления образовательной организацией</section_title>
		<section_content>
			<management_structure>
				<structural_units>
					<structural_unit>
						<name>Наименование структурного подразделения</name>
						<leader>ФИО руководителя структурного подразделения</leader>
						<position>Должность руководителя структурного подразделения</position>
						<location>Местонахождение структурного подразделения</location>
						<official_website>Адрес официального сайта структурного подразделения</official_website>
						<email>Адрес электронной почты структурного подразделения</email>
						<regulations>Сведения о положении о структурном подразделении (об органе управления) с приложением копии указанного положения</regulations>
					</structural_unit>
				</structural_units>
				<management_bodies>
					<management_body>
						<name>Наименование органа управления</name>
						<leader>ФИО руководителя органа управления</leader>
						<position>Должность руководителя органа управления</position>
						<location>Местонахождение органа управления</location>
						<official_website>Адрес официального сайта органа управления</official_website>
						<email>Адрес электронной почты органа управления</email>
						<regulations>Сведения о положении об органе управления с приложением копии указанного положения</regulations>
					</management_body>
				</management_bodies>
			</management_structure>
		</section_content>
	</section>',
            'xslt' => ''
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
        <xsl:for-each select="//documents/document[generate-id() = generate-id(key('"documents-by-type"', type)[1])]">
          <xsl:sort select="type" />
          <p><strong><xsl:value-of select="type" /></strong></p>
          <xsl:for-each select="key('"documents-by-type"', type)">
            <p><a href="{link}"><xsl:value-of select="name" /></a></p>
          </xsl:for-each>
        </xsl:for-each>
      </body>
    </html>
  </xsl:template>
  
</xsl:stylesheet>

'
            ],
            [ 
            'name' =>'Образование',
            'xml' => '<section>
		<section_title>Образование</section_title>
		<section_content>
			<education_levels>
				<level_title>Уровни образования</level_title>
				<level_info>Информация о реализуемых уровнях образования (начальное, основное, среднее), нормативных сроках обучения и формах обучения</level_info>
			</education_levels>
			<educational_programs>
				<program_title>Образовательные программы</program_title>
				<program_info>Информация о реализуемых образовательных программах, в том числе адаптированных, с указанием учебных предметов, курсов, дисциплин, практики, предусмотренных образовательной программой, а также о языках, на которых осуществляется обучение</program_info>
				<program_attachment>Приложение с копией образовательной программы</program_attachment>
			</educational_programs>
			<educational_plan>
				<plan_title>Учебный план</plan_title>
				<plan_info>Информация об учебном плане, его копия</plan_info>
			</educational_plan>
			<educational_schedule>
				<schedule_title>Календарный учебный график</schedule_title>
				<schedule_info>Информация о календарном учебном графике, его копия</schedule_info>
			</educational_schedule>
			<educational_documents>
				<documents_title>Документы для обеспечения образовательного процесса</documents_title>
				<documents_info>Информация о методических и иных документах, разработанных образовательной организацией для обеспечения образовательного процесса</documents_info>
			</educational_documents>
		</section_content>
	</section>',
            'xslt' => ''
            ],
            [ 
            'name' =>'Образовательные стандарты',
            'xml' => '<section>
		<section_title>Образовательные стандарты</section_title>
		<section_content>
			<federal_standards>
				<standard_title>Федеральные государственные образовательные стандарты</standard_title>
				<standard_info>Информация о ФГОС</standard_info>
				<standard_copy>Копия ФГОС</standard_copy>
			</federal_standards>
			<educational_standards>
				<standard_title>Образовательные стандарты</standard_title>
				<standard_info>Информация об образовательных стандартах</standard_info>
				<standard_copy>Копия образовательных стандартов</standard_copy>
			</educational_standards>
		</section_content>
	</section>',
            'xslt' => ''
            ],
            [ 
            'name' =>'Руководство. Педагогический (научно-педагогический) состав',
            'xml' => '<section>
		<section_title>Руководство. Педагогический (научно-педагогический) состав</section_title>
		<section_content>
			<management>
				<director>
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
				</director>
				<deputy_directors>
					<deputy_director>
						<full_name></full_name>
						<email></email>
						<phone></phone>
						<position></position>
						<disciplines></disciplines>
						<education></education>
						<specialization></specialization>
						<qualification_improvement></qualification_improvement>
						<career></career>
						<overall_experience></overall_experience>
						<specialization_experience></specialization_experience>
					</deputy_director>
				</deputy_directors>
				<branch_directors>
					<branch_director>
						<full_name></full_name>
						<email></email>
						<phone></phone>
						<position></position>
						<disciplines></disciplines>
						<education></education>
						<specialization></specialization>
						<qualification_improvement></qualification_improvement>
						<career></career>
						<overall_experience></overall_experience>
						<specialization_experience></specialization_experience>
					</branch_director>
				</branch_directors>
			</management>
			<pedagogical_staff>
				<pedagogical_worker>
					<full_name></full_name>
					<email></email>
					<phone></phone>
					<position></position>
					<disciplines></disciplines>
					<education></education>
					<specialization></specialization>
					<qualification_improvement></qualification_improvement>
					<career></career>
					<overall_experience></overall_experience>
					<specialization_experience></specialization_experience>
				</pedagogical_worker>
			</pedagogical_staff>
		</section_content>
	</section>',
            'xslt' => ''
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
            'xslt' => ''
            ],
            [ 
            'name' =>'Стипендии и иные виды материальной поддержки',
            'xml' => '<section>
		<section_title>Стипендии и иные виды материальной поддержки</section_title>
		<section_content>
			<scholarships>
				<scholarship_title>Студенческие стипендии</scholarship_title>
				<scholarship_info>Условия предоставления стипендий для обучающихся</scholarship_info>
			</scholarships>
			<social_support>
				<support_title>Меры социальной поддержки</support_title>
				<support_info>Условия и порядок предоставления мер социальной поддержки</support_info>
			</social_support>
			<dormitories>
				<dormitory_title>Общежития и интернаты</dormitory_title>
				<dormitory_info>Информация о наличии общежитий и интернатов для иногородних обучающихся, в том числе приспособленных для использования инвалидами и лицами с ограниченными возможностями здоровья, количестве жилых помещений в общежитии, формировании платы за проживание</dormitory_info>
			</dormitories>
			<employment>
				<employment_title>Трудоустройство выпускников</employment_title>
				<employment_info>Информация о трудоустройстве выпускников</employment_info>
			</employment>
		</section_content>
	</section>',
            'xslt' => ''
            ],
            [ 
            'name' =>'Платные образовательные услуги',
            'xml' => '	<section>
		<section_title>Платные образовательные услуги</section_title>
		<section_content>
			<paid_services_info>
				<document>
					<name></name>
					<link></link>
				</document>				
			</paid_services_info>
		</section_content>
	</section>',
            'xslt' => '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="html" encoding="UTF-8" indent="yes" />
  <xsl:template match="/">
    <html>
      <body>
        <xsl:apply-templates select="//paid_services_info/document" />
      </body>
    </html>
  </xsl:template>
  <xsl:template match="document">
    <p>
      <a href="{link}"><xsl:value-of select="name" /></a>
    </p>
  </xsl:template>
</xsl:stylesheet>
'
            ],
            [ 
            'name' =>'Финансово-хозяйственная деятельность',
            'xml' => '<section>
		<section_title>Финансово-хозяйственная деятельность</section_title>
		<section_content>
			<funding_sources>
				<funding_title>Источники финансирования</funding_title>
				<funding_info>Информация об объеме образовательной деятельности, финансовое обеспечение которой осуществляется за счет бюджетных ассигнований федерального бюджета, бюджетов субъектов Российской Федерации, местных бюджетов, по договорам об образовании за счет средств физических и (или) юридических лиц</funding_info>
			</funding_sources>
			<financial_report>
				<report_title>Финансовый отчет</report_title>
				<report_info>Информация о поступлении финансовых и материальных средств и об их расходовании по итогам финансового года</report_info>
			</financial_report>
		</section_content>
	</section>',
            'xslt' => ''
            ],
            [ 
            'name' =>'Вакантные места для приема (перевода)',
            'xml' => '<section>
		<section_title>Вакантные места для приема (перевода)</section_title>
		<section_content>
			<vacancies_info>
				<vacancies_title>Информация о вакантных местах</vacancies_title>
				<vacancies_list>
					<program>
						<program_name>Название образовательной программы</program_name>
						<profession>Профессия</profession>
						<specialization>Специальность</specialization>
						<study_direction>Направление подготовки</study_direction>
						<budget_vacancies>Количество вакантных мест за счет бюджетных ассигнований</budget_vacancies>
						<contract_vacancies>Количество вакантных мест по договорам об образовании</contract_vacancies>
					</program>
				</vacancies_list>
			</vacancies_info>
		</section_content>
	</section>',
            'xslt' => ''
            ]             
        ];

        $menu_items_data = [
            'Начальная страница плагина',
            'Настройка присвоения ролей пользователям', 
            'Настройка ролей', 
            'Настройка отображения подразделов сайта', 
            'Настройка содержания подразделов сайта'
        ];
        
        
        

        foreach ($site_subsections_data as $subsection) {
            
            $existing_subsection = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_site_subsections WHERE name = %s", $subsection['name']));
            if ($existing_subsection == 0) {
                $wpdb->insert($table_site_subsections, array(
                    'name' => $subsection['name'],
                    'xml' => $subsection['xml'],
                    'xslt' => $subsection['xslt'],
                    'visible' => true
                    ));
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
            ),
            array(
                'title' => 'Настройка отображения подразделов сайта',
                'menu_title' => 'Подразделы сайта',
                'slug' => 'reginfoeduorg-submenu',
                'callback' => array($this, 'submenu_page')
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

    //Настройка отображения подразделов сайта
    function submenu_page() 
    {
        // получаем текущего пользователя
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        global $wpdb;

        // получаем ID роли текущего пользователя из таблицы wp_reginfoeduorg_users_roles
        $user_role_id = $wpdb->get_var($wpdb->prepare("SELECT role_id FROM {$wpdb->prefix}reginfoeduorg_users_roles WHERE user_id = %d", $user_id));


        // проверяем, принадлежит ли текущий пользователь какой-то роли
        if ($user_role_id) {
            // Задаем название подпункта меню, для которого хотим проверить доступ
            $menu_item_name = 'Настройка отображения подразделов сайта';

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
                
            }
            else
            {
                global $wpdb;
                $table_site_subsections = $wpdb->prefix . 'reginfoeduorg_site_subsections';

                $child_sections = $wpdb->get_results("SELECT name FROM {$table_site_subsections}");

                $this->child_sections = $child_sections;

                if (isset($_POST['submit'])) {
                    $visible_subsections = isset($_POST['reginfoeduorg_subsections']) ? $_POST['reginfoeduorg_subsections'] : array();
                    foreach ($child_sections as $child_section) {
                        $visible = in_array($child_section->name, $visible_subsections) ? 1 : 0;
                        $wpdb->update(
                            $table_site_subsections,
                            array('visible' => $visible),
                            array('name' => $child_section->name),
                            array('%d'),
                            array('%s')
                        );
                    }
                    echo '<div id="message" class="updated notice is-dismissible"><p>Настройки сохранены</p></div>';
                }

                $visible_subsections_objects = $wpdb->get_results("SELECT name FROM {$table_site_subsections} WHERE visible = 1");
                $visible_subsections = array();
                foreach ($visible_subsections_objects as $visible_subsection) {
                    $visible_subsections[] = $visible_subsection->name;
                }

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
                        <input type="checkbox" name="reginfoeduorg_subsections[]" value="<?php echo $child_section->name; ?>" <?php checked(in_array($child_section->name, $visible_subsections)); ?>>
                        <?php echo $child_section->name; ?>
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
    document.addEventListener("DOMContentLoaded", function () {
        const selectAllButton = document.getElementById("select-all-button");
        const checkboxes = document.querySelectorAll("input[type='checkbox']");
        selectAllButton.addEventListener("click", function () {
            checkboxes.forEach(function (checkbox) {
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
                echo "<div class='notice notice-success is-dismissible'><p>Данные успешно импортированы из файла.</p></div>";
            }
            else {
                // Выводим сообщение об ошибке при загрузке файла
                echo "<div class='notice notice-error is-dismissible'><p>Ошибка при загрузке файла: " . $_FILES['import_file']['error'] . "</p></div>";
            }
        }

        //Обработчик сохранения изменений в таблице
        if (isset($_POST['save_table_changes'])) {            
            $this->save_table_changes($subsection_id);
            echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Данные таблицы обновлены.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
        }

        //Обработчик применения стилей
        if (isset($_POST['apply_styles'])) {
            $this->apply_styles($subsection_id);
            echo '<div id="message" class="updated notice notice-success is-dismissible"><p>XSLT стиль применен.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
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
        echo '<th><label for="xslt-styles">XSLT стили:</label></th>';
        echo '<td>';
        $subsection_data = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE id = {$subsection_id}", ARRAY_A);
        $saved_xslt_code = isset($subsection_data['xslt']) ? $subsection_data['xslt'] : '';
        echo '<textarea name="reginfoeduorg_xslt_code" id="reginfoeduorg_xslt_code" rows="10" style="width: 100%;">' . esc_textarea($saved_xslt_code) . '</textarea>';
        echo '</td>';
        echo '</tr>';
        echo '</table>';
        echo '<p>';
        echo '<input type="submit" name="apply_styles" value="Применить стиль" class="button">';
        echo '</p>';
        echo '</form>';
        echo '</div>';
    }


    //-----------------------Процесс обработки и вывода данных в подпунктах меню с настройкой подразделов-------------------------------
    function import_data($subsection_id, $xml)
    {
        global $wpdb;
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
                // Находим секцию "Документы"
                $documents = $xml->xpath('//reginfoeduorg/section[section_title="Документы"]/section_content/documents')[0];

                // Очищаем таблицы перед импортом
                global $wpdb;
                $table_documents_types = "{$wpdb->prefix}reginfoeduorg_documents_types";
                $table_documents = "{$wpdb->prefix}reginfoeduorg_documents";

                $wpdb->query("TRUNCATE TABLE $table_documents");
                $wpdb->query("TRUNCATE TABLE $table_documents_types");

                // Массив для хранения идентификаторов типов документов
                $document_types_ids = array();

                // Проходимся по всем документам
                foreach ($documents->document as $document) {
                    // Получаем данные
                    $document_name = (string)$document->name;
                    $document_link = (string)$document->link;
                    $document_type = (string)$document->type;

                    
                    // Если тип документа еще не импортирован, проверяем его наличие в базе данных
                    if (!array_key_exists($document_type, $document_types_ids)) {
                        // Проверяем, существует ли тип документа в базе данных
                        $existing_type_id = $wpdb->get_var($wpdb->prepare(
                            "SELECT id FROM $table_documents_types WHERE document_type = %s",
                            $document_type
                        ));

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

            case 9:               
                // Находим секцию "Документы"
                $documents = $xml->xpath('//reginfoeduorg/section[section_title="Платные образовательные услуги"]/section_content/paid_services_info')[0];

                // Очищаем таблицу перед импортом
                global $wpdb;
                $table_name = "{$wpdb->prefix}reginfoeduorg_paid_services";
                $wpdb->query("TRUNCATE TABLE $table_name");

                // Проходимся по всем документам
                foreach ($documents->document as $document) {
                    // Получаем название документа и ссылку
                    $document_name = (string)$document->name;
                    $document_link = (string)$document->link;

                    if (!empty($document_link)) {
                        // Создаем массив с данными для таблицы reginfoeduorg_documents
                        $data = array(
                            'document_type' => $document_name,
                            'document_link' => $document_link,
                        );

                        // Вставляем данные в таблицу reginfoeduorg_documents
                        if ($wpdb->insert($table_name, $data) === false) {
                            // Выводим сообщение об ошибке при вставке данных
                            echo "<div class='notice notice-error is-dismissible'><p>Ошибка при вставке данных в таблицу: " . $wpdb->last_error . "</p></div>";
                            break;
                        }
                    }
                }

                break;

            case 6:                
                
                // Находим секцию "Руководство. Педагогический (научно-педагогический) состав"
                $section_content = $xml->xpath('//section[section_title="Руководство. Педагогический (научно-педагогический) состав"]/section_content')[0];
                // Объединяем всех сотрудников в одном массиве
                // Объединяем всех сотрудников в одном массиве
                $staff_data = array();

                if ($section_content->management->director) {
                    $staff_data[] = $section_content->management->director;
                }

                if ($section_content->management->deputy_directors) {
                    foreach ($section_content->management->deputy_directors->deputy_director as $deputy_director) {
                        $staff_data[] = $deputy_director;
                    }
                }

                if ($section_content->management->branch_directors) {
                    foreach ($section_content->management->branch_directors->branch_director as $branch_director) {
                        $staff_data[] = $branch_director;
                    }
                }

                if ($section_content->pedagogical_staff) {
                    foreach ($section_content->pedagogical_staff->pedagogical_worker as $pedagogical_worker) {
                        $staff_data[] = $pedagogical_worker;
                    }
                }


                // Очистить таблицу staff перед добавлением новых данных
                global $wpdb;
                $table_staff = $wpdb->prefix . 'reginfoeduorg_staff';
                $table_disciplines = $wpdb->prefix . 'reginfoeduorg_disciplines';
                $table_education = $wpdb->prefix . 'reginfoeduorg_education';
                $table_qualification_improvement = $wpdb->prefix . 'reginfoeduorg_qualification_improvement';
                $table_career = $wpdb->prefix . 'reginfoeduorg_career';
                $wpdb->query("DELETE FROM $table_staff");
                // Вставляем данные о сотрудниках
                foreach ($staff_data as $staff_member) {
                    $staff_member_data = array(
                        'full_name' => (string)$staff_member->full_name,
                        'position' => (string)$staff_member->position,
                        'email' => (string)$staff_member->email,
                        'phone' => (string)$staff_member->phone,
                        );
                    // Вставляем основные данные сотрудника и получаем ID вставленного сотрудника
                    $wpdb->insert($table_staff, $staff_member_data);
                    $staff_member_id = $wpdb->insert_id;

                    // Вставляем дисциплины
                    if (isset($staff_member->disciplines)) {
                        foreach ($staff_member->disciplines as $discipline) {
                            $wpdb->insert($table_disciplines, array(
                                'staff_id' => $staff_member_id,
                                'discipline' => (string)$discipline,
                            ));
                        }
                    }

                    // Вставляем данные об образовании
                    if (isset($staff_member->education)) {
                        foreach ($staff_member->education as $education) {
                            $wpdb->insert($table_education, array(
                                'staff_id' => $staff_member_id,
                                'education_info' => (string)$education
                            ));
                        }
                    }

                    // Вставляем данные о повышении квалификации
                    if (isset($staff_member->qualification_improvement)) {
                        foreach ($staff_member->qualification_improvement as $improvement) {
                            $wpdb->insert($table_qualification_improvement, array(
                                'staff_id' => $staff_member_id,
                                'improvement_info' => (string)$improvement,
                            ));
                        }
                    }


                    // Вставляем данные о карьере
                    if (isset($staff_member->career)) {
                        foreach ($staff_member->career as $career) {
                            $career_info = (string)$career;
                            $wpdb->insert($table_career, array(
                                'staff_id' => $staff_member_id,
                                'career_info' => $career_info,
                            ));
                        }
                    }

                    // Вставляем данные об опыте работы
                    $wpdb->update($table_staff, array(
                        'overall_experience' => (string)$staff_member->overall_experience,
                        'specialization_experience' => (string)$staff_member->specialization_experience,
                ), array('id' => $staff_member_id));
                }

                break;
        }    
    }

    function save_table_changes($subsection_id)
    {
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
                $data = $wpdb->get_results("SELECT d.id, d.document_name, dt.document_type, d.document_link 
                                 FROM {$wpdb->prefix}reginfoeduorg_documents as d
                                 JOIN {$wpdb->prefix}reginfoeduorg_documents_types as dt
                                 ON d.document_type = dt.id", ARRAY_A);
                foreach ($data as $row) {
                    $id = $row['id']; // Получаем ID документа
                    $new_name = $_POST['document_name'][$id]; // Получаем новое название из формы
                    $new_type = $_POST['document_type'][$id]; // Получаем новый тип из формы
                    $new_link = $_POST['document_link'][$id]; // Получаем новую ссылку из формы
           
                    // Обновляем данные в базе данных
                    $wpdb->update(
                        "{$wpdb->prefix}reginfoeduorg_documents", // Название таблицы
                        array(
                            'document_name' => $new_name, 
                            'document_type' => $new_type, 
                            'document_link' => $new_link
                        ), // Данные для обновления
                        array('id' => $id), // Условие WHERE
                        array('%s', '%d', '%s'), // Формат данных для обновления
                        array('%d')  // Формат данных в условии WHERE
                    );
                }
                break;

            default:
        }
    }

    function apply_styles($subsection_id)
    {
        global $wpdb;
        $xslt_code = isset($_POST['reginfoeduorg_xslt_code']) ? stripslashes($_POST['reginfoeduorg_xslt_code']) : '';
        
        // Сохраняем XSLT стиль в базу данных
        $wpdb->update(
            "{$wpdb->prefix}reginfoeduorg_site_subsections",
            array('xslt' => $xslt_code),
            array('id' => $subsection_id),
            array('%s'),
            array('%d')
        );
        $xml = new DOMDocument();
        $xml = $this->generate_shortcode($subsection_id);
    }

    function table_data($subsection_id)
    {
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
            case 3:
                $data = $wpdb->get_results("SELECT d.id, d.document_name, dt.id as document_type_id, dt.document_type, d.document_link 
                             FROM {$wpdb->prefix}reginfoeduorg_documents as d
                             JOIN {$wpdb->prefix}reginfoeduorg_documents_types as dt
                             ON d.document_type = dt.id", ARRAY_A);
                
                // Получаем все типы документов для создания выпадающего списка
                $document_types = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_documents_types", ARRAY_A);
                
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
                        echo '<select name="document_type[' . $row['doc_id'] . ']">';
                        foreach ($document_types as $type) {
                            $selected = $type['id'] == $row['document_type_id'] ? ' selected' : '';
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

            case 9:
                $data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_paid_services", ARRAY_A);
                if ($data) {
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr>';
                    echo '<th scope="col" class="manage-column column-name">Тип документа</th>';
                    echo '<th scope="col" class="manage-column column-value">Ссылка на документ</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';

                    foreach ($data as $row) {
                        echo '<tr>';
                        echo '<td class="column-name">' . $row['document_type'] . '</td>';
                        echo '<td class="column-value">' . $row['document_link'] . '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody>';
                    echo '</table>';
                } else {
                    echo '<p>Данные отсутствуют.</p>';
                }

                break;
            case 6:
                $staff_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_staff", ARRAY_A);
                if ($staff_data) {
                    echo "<style>
                      .custom-textarea {
                          max-width: 100%;
                          overflow-wrap: break-word;
                          white-space: normal;
                          resize: vertical;
                          max-height: none;
                          height: auto;
                          box-sizing: border-box;
                        }
                    </style>";
                    if (isset($_POST['save_table_changes'])) {
                        // Обработка данных таблицы reginfoeduorg_staff
                        foreach ($_POST['data'] as $id => $row_data) {
                            $wpdb->update(
                                "{$wpdb->prefix}reginfoeduorg_staff",
                                $row_data,
                                array('id' => $id),
                                array('%s', '%s', '%s', '%s', '%d', '%d'),
                                array('%d')
                            );
                        }

                        // Обработка связанных данных
                        foreach ($_POST['related_data'] as $id => $related_data) {
                            // Удаление существующих записей для сотрудника
                            $wpdb->delete("{$wpdb->prefix}reginfoeduorg_disciplines", array('staff_id' => $id), array('%d'));
                            $wpdb->delete("{$wpdb->prefix}reginfoeduorg_education", array('staff_id' => $id), array('%d'));
                            $wpdb->delete("{$wpdb->prefix}reginfoeduorg_qualification_improvement", array('staff_id' => $id), array('%d'));
                            $wpdb->delete("{$wpdb->prefix}reginfoeduorg_career", array('staff_id' => $id), array('%d'));

                            // Добавление новых записей с обновленными данными
                            $disciplines = explode(',', $related_data['disciplines']);
                            if (is_array($disciplines)) {
                                foreach ($disciplines as $discipline) {
                                    $wpdb->insert("{$wpdb->prefix}reginfoeduorg_disciplines", array('staff_id' => $id, 'discipline' => $discipline), array('%d', '%s'));
                                }
                            }

                            $education = explode(',', $related_data['education']);
                            if (is_array($education)) {
                                foreach ($education as $edu) {
                                    $wpdb->insert("{$wpdb->prefix}reginfoeduorg_education", array('staff_id' => $id, 'education_info' => $edu), array('%d', '%s'));
                                }
                            }

                            $qualification_improvements = explode(',', $related_data['qualification_improvement']);
                            if (is_array($qualification_improvements)) {
                                foreach ($qualification_improvements as $improvement_info) {
                                    $wpdb->insert("{$wpdb->prefix}reginfoeduorg_qualification_improvement", array('staff_id' => $id, 'improvement_info' => $improvement_info), array('%d', '%s'));
                                }
                            }

                            $careers = explode(',', $related_data['career']);
                            if (is_array($careers)) {
                                foreach ($careers as $career_info) {
                                    $wpdb->insert("{$wpdb->prefix}reginfoeduorg_career", array('staff_id' => $id, 'career_info' => $career_info), array('%d', '%s'));
                                }
                            }
                        }
                    }

                    foreach ($staff_data as $row) {
                        $id = $row['id'];
                        $disciplines = $wpdb->get_results($wpdb->prepare("SELECT discipline FROM {$wpdb->prefix}reginfoeduorg_disciplines WHERE staff_id = %d", $id), ARRAY_A);
                        $education = $wpdb->get_results($wpdb->prepare("SELECT education_info FROM {$wpdb->prefix}reginfoeduorg_education WHERE staff_id = %d", $id), ARRAY_A);
                        $qualification_improvement = $wpdb->get_results($wpdb->prepare("SELECT improvement_info FROM {$wpdb->prefix}reginfoeduorg_qualification_improvement WHERE staff_id = %d", $id), ARRAY_A);
                        $career = $wpdb->get_results($wpdb->prepare("SELECT career_info FROM {$wpdb->prefix}reginfoeduorg_career WHERE staff_id = %d", $id), ARRAY_A);
                        
                        echo '<h3>Сотрудник: ' . $row['full_name'] . '</h3>';

                        echo '<table class="wp-list-table widefat fixed striped">';
                        echo '<thead><tr><th>ФИО</th><th>Должность</th><th>Email</th><th>Телефон</th><th>Общий стаж</th><th>Стаж по специализации</th></tr></thead>';
                        echo '<tbody>';
                        echo '<tr>';
                        echo '<td><textarea class="custom-textarea" name="data[' . $id . '][full_name]">' . $row['full_name'] . '</textarea></td>';
                        echo '<td><textarea class="custom-textarea" name="data[' . $id . '][position]">' . $row['position'] . '</textarea></td>';
                        echo '<td><textarea class="custom-textarea" name="data[' . $id . '][email]">' . $row['email'] . '</textarea></td>';
                        echo '<td><textarea class="custom-textarea" name="data[' . $id . '][phone]">' . $row['phone'] . '</textarea></td>';
                        echo '<td><textarea class="custom-textarea" name="data[' . $id . '][overall_experience]">' . $row['overall_experience'] . '</textarea></td>';
                        echo '<td><textarea class="custom-textarea" name="data[' . $id . '][specialization_experience]">' . $row['specialization_experience'] . '</textarea></td>';
                        echo '</tr>';
                        echo '</tbody>';
                        echo '</table>';
                        echo '<br>';
                        
                        echo '<table class="wp-list-table widefat fixed striped">';
                        echo '<thead><tr><th>Дисциплины</th></tr></thead>';
                        echo '<tbody>';
                        echo '<td><textarea class="custom-textarea" name="related_data[' . $id . '][disciplines]">' . implode(', ', array_column($disciplines, 'discipline')) . '</textarea></td>';
                        echo '</tbody>';
                        echo '</table>';
                        echo '<br>';

                        echo '<table class="wp-list-table widefat fixed striped">';
                        echo '<thead><tr><th>Образование</th></tr></thead>';
                        echo '<tbody>';
                        echo '<td><textarea class="custom-textarea" name="related_data[' . $id . '][education]">' . implode(', ', array_column($education, 'education_info')) . '</textarea></td>';
                        echo '</tbody>';
                        echo '</table>';
                        echo '<br>';

                        echo '<table class="wp-list-table widefat fixed striped">';
                        echo '<thead><tr><th>Повышение квалификации</th></tr></thead>';
                        echo '<tbody>';
                        echo '<td><textarea class="custom-textarea" name="related_data[' . $id . '][qualification_improvement]">' . implode(', ', array_column($qualification_improvement, 'improvement_info')) . '</textarea></td>';
                        echo '</tbody>';
                        echo '</table>';
                        echo '<br>';

                        echo '<table class="wp-list-table widefat fixed striped">';
                        echo '<thead><tr><th>Карьера</th></tr></thead>';
                        echo '<tbody>';
                        echo '<td><textarea class="custom-textarea" name="related_data[' . $id . '][career]">' . implode(', ', array_column($career, 'career_info')) . '</textarea></td>';
                        echo '</tbody>';
                        echo '</table>';
                        echo '<br>';
                        echo '<hr>'; 
                    }
                    
                    echo '<input type="submit" name="save_table_changes" value="Сохранить изменения" class="button-primary">';
                    echo '<br>';
                    
                } 
                else {
                    echo '<p>Данные отсутствуют.</p>';
                }
                break;


            default:
                echo '<p>Страница находится в разработке.</p>';
                break;
        }
    }
        
    function generate_shortcode($subsection_id) {
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
                $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_documents");                
                $shortcode = '[documents_info id="' . $id . '"]';
                break;
            case 9:
                // Выбираем данные из таблицы 
                $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}reginfoeduorg_paid_services");                
                $shortcode = '[paid_services_info id="' . $id . '"]';
                break;
            case 6:
                // Выбираем данные о сотрудниках
                $staff_members = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_staff", ARRAY_A);

                // Выбираем пустую структуру XML для подраздела "Сотрудники" из базы данных
                $subsection_xml = $wpdb->get_var("SELECT xml FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE id=$subsection_id");

                // Загружаем пустую структуру XML и дополняем ее данными
                $xml = new DOMDocument('1.0', 'UTF-8');
                $xml->formatOutput = true;
                $xml->loadXML($subsection_xml);
                // Находим элемент section_content для подраздела "Сотрудники"
                $section_content = $xml->getElementsByTagName('section_content')->item(0);

                // Удаляем имеющиеся элементы с данными
                while ($section_content->hasChildNodes()) {
                    $section_content->removeChild($section_content->firstChild);
                }

                // Создаем элемент staff_members
                $staff_members_node = $xml->createElement('staff_members');
                $section_content->appendChild($staff_members_node);

                // Для каждого сотрудника создаем элемент staff и добавляем его в staff_members
                foreach ($staff_members as $staff) {
                    $staff_node = $xml->createElement('staff');
                    $staff_members_node->appendChild($staff_node);

                    // Создаем элементы для каждого поля из таблицы сотрудников и добавляем их в staff
                    foreach ($staff as $key => $value) {
                        $element = $xml->createElement($key, htmlspecialchars($value));
                        $staff_node->appendChild($element);
                    }
                    
                    // Добавляем данные из связанных таблиц
                    $staff_id = $staff['id'];
                    $related_tables = [
                        'reginfoeduorg_disciplines',
                        'reginfoeduorg_education',
                        'reginfoeduorg_qualification_improvement',
                        'reginfoeduorg_career'
                    ];

                    foreach ($related_tables as $table) {
                        $related_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}{$table} WHERE staff_id = %d", $staff_id), ARRAY_A);
                        
                        // Создаем элемент для связанных данных
                        $related_data_node = $xml->createElement($table);
                        $staff_node->appendChild($related_data_node);

                        // Добавляем элементы для каждого поля из связанных таблиц
                        foreach ($related_data as $related_item) {
                            $item_node = $xml->createElement('item');
                            $related_data_node->appendChild($item_node);
                            
                            foreach ($related_item as $key => $value) {
                                $element = $xml->createElement($key, htmlspecialchars($value));
                                $item_node->appendChild($element);
                            }
                        }
                    }
                }
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
        echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Изменения сохранены.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
        
        return $xml;
    }
    //-----------------------Процесс обработки и вывода данных в подпунктах меню с настройкой подразделов-------------------------------
    

}

if(class_exists('RegInfoEduOrg'))
{
    new RegInfoEduOrg(__FILE__);
}
