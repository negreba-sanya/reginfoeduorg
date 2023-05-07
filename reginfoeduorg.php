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

            CREATE TABLE {$wpdb->prefix}staff (
                id INT(11) NOT NULL AUTO_INCREMENT,
                full_name VARCHAR(255) NOT NULL,
                position VARCHAR(255) NOT NULL,
                email VARCHAR(255) DEFAULT '',
                phone VARCHAR(255) DEFAULT '',
                overall_experience INT NOT NULL,
                specialization_experience INT NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;

            CREATE TABLE {$wpdb->prefix}disciplines (
                id INT(11) NOT NULL AUTO_INCREMENT,
                staff_id INT(11) NOT NULL,
                discipline TEXT NOT NULL,
                PRIMARY KEY (id),
                FOREIGN KEY (staff_id) REFERENCES {$wpdb->prefix}staff(id) ON DELETE CASCADE ON UPDATE CASCADE
            ) $charset_collate;

            CREATE TABLE {$wpdb->prefix}education (
                id INT(11) NOT NULL AUTO_INCREMENT,
                staff_id INT(11) NOT NULL,
                education_info TEXT NOT NULL,
                specialization VARCHAR(255) NOT NULL,
                PRIMARY KEY (id),
                FOREIGN KEY (staff_id) REFERENCES {$wpdb->prefix}staff(id) ON DELETE CASCADE ON UPDATE CASCADE
            ) $charset_collate;

            CREATE TABLE {$wpdb->prefix}qualification_improvement (
                id INT(11) NOT NULL AUTO_INCREMENT,
                staff_id INT(11) NOT NULL,
                improvement_info TEXT NOT NULL,
                PRIMARY KEY (id),
                FOREIGN KEY (staff_id) REFERENCES {$wpdb->prefix}staff(id) ON DELETE CASCADE ON UPDATE CASCADE
            ) $charset_collate;
            
            CREATE TABLE {$wpdb->prefix}career (
                id INT(11) NOT NULL AUTO_INCREMENT,
                staff_id INT(11) NOT NULL,
                career_info TEXT NOT NULL,
                PRIMARY KEY (id),
                FOREIGN KEY (staff_id) REFERENCES {$wpdb->prefix}staff(id) ON DELETE CASCADE ON UPDATE CASCADE
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
                id INT(11) NOT NULL AUTO_INCREMENT,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                role_id INT(11) NOT NULL,
                PRIMARY KEY (id),
                FOREIGN KEY (role_id) REFERENCES $table_roles(id) ON DELETE CASCADE
            ) $charset_collate;";

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
	</section>'
            ],
            [ 
            'name' =>'Документы',
            'xml' => '<section>
		<section_title>Документы</section_title>
		<section_content>
			<documents>
				<charter>ссылка на файл устава образовательной организации</charter>
				<license>ссылка на файл лицензии на осуществление образовательной деятельности</license>
				<accreditation>ссылка на файл свидетельства о государственной аккредитации</accreditation>
				<finance_plan>ссылка на файл плана финансово-хозяйственной деятельности образовательной организации</finance_plan>
				<normative_acts>
					<local_normative_acts>ссылка на файл локальных нормативных актов, предусмотренных законодательством</local_normative_acts>
					<student_internal_rules>ссылка на файл правил внутреннего распорядка обучающихся</student_internal_rules>
					<employee_internal_rules>ссылка на файл правил внутреннего трудового распорядка</employee_internal_rules>
					<collective_agreement>ссылка на файл коллективного договора</collective_agreement>
				</normative_acts>
				<self_evaluation_report>ссылка на файл отчета о результатах самообследования</self_evaluation_report>
				<paid_services>
					<paid_services_document>ссылка на документ о порядке оказания платных образовательных услуг</paid_services_document>
					<contract_example>ссылка на образец договора об оказании платных образовательных услуг</contract_example>
					<tuition_fee>ссылка на документ об утверждении стоимости обучения по каждой образовательной программе</tuition_fee>
					<maintenance_fee>ссылка на документ об установлении размера платы за присмотр и уход за детьми в образовательной организации</maintenance_fee>
				</paid_services>
				<supervision_reports>
					<supervision_report>ссылка на отчеты об исполнении предписаний органов, осуществляющих государственный контроль (надзор) в сфере образования</supervision_report>
				</supervision_reports>
			</documents>
		</section_content>
	</section>'
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
	</section>'
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
	</section>'
            ],
            [ 
            'name' =>'Руководство. Педагогический (научно-педагогический) состав',
            'xml' => '<section>
		<section_title>Руководство. Педагогический (научно-педагогический) состав</section_title>
		<section_content>
			<management>
				<director>
					<full_name>Питенина Оксана Николаевна</full_name>
					<position>Директор</position>
					<email></email>
					<phone></phone>
					<disciplines>Организация учебной деятельности, экономика организации</disciplines>
					<education>2004, ФГБОУ ВПО Сибирский государственный технологический университет, по специальности "Экономика и управление на предприятиях химико-лесного комплекса", экономист - менеджер</education>
					<specialization>экономика и управление на предприятии</specialization>
					<qualification_improvement>КГБОУ ДПО ПКС «Центр современных технологий профессионального образования» «организация и содержание методической работы в образовательном учреждении профессионального образования» удостоверение (72 часа) № 46-ОМР, 2013 2018, ЦРПО, по программе «Учебно-методический комплекс как условие обеспечения качества внедрения образовательных программ ТОП-50», 72 часа</qualification_improvement>
					<career></career>
					<overall_experience>16</overall_experience>
					<specialization_experience>12</specialization_experience>
				</director>
				<deputy_directors>
					<deputy_director>
						<full_name>Константинова Наталья Андреевна</full_name>
						<email></email>
						<phone></phone>
						<position>Заместитель директора по учебно-производственной работе</position>
						<disciplines>Математика</disciplines>
						<education>1994, Карагандинский государственный университет им. Е.А. Букетова, специальность «Математика», квалификация математик-преподаватель</education>
						<specialization>математик-преподаватель</specialization>
						<qualification_improvement>2018, ФГБОУ ДПО "Гос. Академия пром. менеджмента им. Н.П. Пастухова" по программе Применение моделей и механизмов непрерывного образования пед. работников СПО для подготовки высококвалифицированных рабочих кадров по перспективным и востребованным профессиям и специальностям, 48 часов</qualification_improvement>
						<career></career>
						<overall_experience>21</overall_experience>
						<specialization_experience>21</specialization_experience>
					</deputy_director>
				</deputy_directors>
				<branch_directors>
					<branch_director>
						<full_name>Мурзина Маргарита Геннадьевна</full_name>
						<email></email>
						<phone></phone>
						<position>Руководитель Многофункционального центра прикладных квалификаций</position>
						<disciplines>Основы алгоритмизации и программирования, МДК.02.01. Информационные технологии и платформы разработки информационных систем, МДК.01.02.Методы и средства проектирования информационных систем, МДК 01.01Эксплуатация иформационных систем, МДК 03.01 Сопровождение и продвижение программного обеспечения отраслевой направленности</disciplines>
						<education>2009, Красноярский государственный педагогический университет им. В.П. Астафьева, специальность – информатика, квалификация – учитель информатики</education>
						<specialization>информатика</specialization>
						<qualification_improvement>2018, ОГБПОУ "Томский техникум информационных технологий" по программе «Практика и методика подготовки кадров по профессии "Разработчик Web и мультимедийных приложений" с учетом стандарта Ворлдскиллс Россия по компетенции "Веб дизайн и разработка", 78 часов</qualification_improvement>
						<career></career>
						<overall_experience>13</overall_experience>
						<specialization_experience>6</specialization_experience>
					</branch_director>
				</branch_directors>
			</management>

			<pedagogical_staff>
				<pedagogical_worker>
					<full_name>Степанова Ольга Викторовна</full_name>
					<email></email>
					<phone></phone>
					<position>и.о. зам.директора по УМР, заведующая практикой, преподаватель</position>
					<disciplines>Основы алгоритмизации и программирования, МДК.02.01. Информационные технологии и платформы разработки информационных систем, МДК.01.02.Методы и средства проектирования информационных систем, МДК 01.01Эксплуатация иформационных систем, МДК 03.01 Сопровождение и продвижение программного обеспечения отраслевой направленности</disciplines>
					<education>2009, Красноярский государственный педагогический университет им. В.П. Астафьева, специальность – информатика, квалификация – учитель информатики</education>
					<specialization>информатика</specialization>
					<qualification_improvement>2018, ОГБПОУ "Томский техникум информационных технологий" по программе «Практика и методика подготовки кадров по профессии "Разработчик Web и мультимедийных приложений" с учетом стандарта Ворлдскиллс Россия по компетенции "Веб дизайн и разработка", 78 часов</qualification_improvement>
					<career></career>
					<overall_experience>13</overall_experience>
					<specialization_experience>6</specialization_experience>
				</pedagogical_worker>
			</pedagogical_staff>
		</section_content>
	</section>'
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
	</section>'
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
	</section>'
            ],
            [ 
            'name' =>'Платные образовательные услуги',
            'xml' => '<section>
		<section_title>Платные образовательные услуги</section_title>
		<section_content>
			<paid_services_info>Информация о порядке оказания платных образовательных услуг, включая перечень услуг, цены, порядок оплаты и возврата средств, информацию о скидках и льготах и другие существенные условия оказания услуг</paid_services_info>
		</section_content>
	</section>'
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
	</section>'
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
	</section>'
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
            
            $existing_subsection = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_site_subsections WHERE name = %s", $subsection));
            if ($existing_subsection == 0) {
                $wpdb->insert($table_site_subsections, array(
                    'name' => $subsection['name'],
                    'xml' => $subsection['xml'],
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
            ),
            array(
                'title' => 'Настройка содержания подразделов сайта',
                'menu_title' => 'Содержание подразделов сайта',
                'slug' => 'reginfoeduorg-contents',
                'callback' => array($this, 'reginfoeduorg_submenu')
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
    
    //Настройка содержания подразделов сайта
    function reginfoeduorg_submenu() 
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
            $menu_item_name = 'Настройка содержания подразделов сайта';

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
                $sections = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_site_subsections");
                // Выводим верстку
                echo '<div class="wrap">';
                echo '<h1>Изменение содержания подразделов:</h1>';
                echo '<form method="post" action="" enctype="multipart/form-data">';
                echo '<table class="form-table">';
                if (is_array($sections)) {
                    foreach ($sections as $key => $section) {                
                        if ($access_settings[$section->name]['read'] == 1) { 
                            if ($access_settings[$section->name]['edit'] == 1) {
                                echo '<tr><th><label for="section-'.$key.'">'.esc_attr($section->name).':</label></th><td>';
                                $post = get_page_by_title($section->name);
                                $content = $post ? $post->post_content : '';
                                $editor_id = 'section-' . $key;
                                $settings = array(
                                    'textarea_name' => 'reginfoeduorg_subsections['.$key.']',
                                    'editor_height' => 200,
                                    'media_buttons' => true,
                                );
                                wp_editor($content, $editor_id, $settings);
                            } 
                            else {
                                echo '<tr><th><label for="section-'.$key.'">'.esc_attr($section->name).':</label></th><td>';
                                $post = get_page_by_title($section->name);
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
                    global $wpdb;
                    $sections = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_site_subsections");

                    // Перебираем все подразделы и обновляем контент, если он был изменен
                    foreach ($sections as $key => $section) {
                        $post_id = get_page_by_title($section->name)->ID;
                        $content = $_POST['reginfoeduorg_subsections'][$key];

                        // Обновляем контент в таблице reginfoeduorg_site_subsections
                        $wpdb->update(
                            "{$wpdb->prefix}reginfoeduorg_site_subsections",
                            array('content' => $content),
                            array('id' => $section->id),
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
                    }

                    // Выводим сообщение об успешном сохранении изменений
                    echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Изменения сохранены.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
                }



                

                // Получаем список подразделов из базы данных
                global $wpdb;
                $sections = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_site_subsections");

                // Выводим верстку
                echo '<div class="wrap">';
                echo '<h1>Изменение содержания подразделов:</h1>';
                echo '<form method="post" action="" enctype="multipart/form-data">';
                echo '<table class="form-table">';
                if (is_array($sections)) {
                    foreach ($sections as $key => $section) {                
                        if ($access_settings[$section->name]['read'] == 1) { 
                            if ($access_settings[$section->name]['edit'] == 1) {
                                echo '<tr><th><label for="section-'.$key.'">'.esc_attr($section->name).':</label></th><td>';
                                $post = get_page_by_title($section->name);
                                $content = $post ? $post->post_content : '';
                                $editor_id = 'section-' . $key;
                                $settings = array(
                                    'textarea_name' => 'reginfoeduorg_subsections['.$key.']',
                                    'editor_height' => 200,
                                    'media_buttons' => true,
                                );
                                wp_editor($content, $editor_id, $settings);
                            } 
                            else {
                                echo '<tr><th><label for="section-'.$key.'">'.esc_attr($section->name).':</label></th><td>';
                                $post = get_page_by_title($section->name);
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
    



    function reginfoeduorg_display_section_data() {
        if ( isset( $_POST['import_file_submit'] ) && isset( $_FILES['import_file'] ) ) {
            if ($_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
                $xml = simplexml_load_file($_FILES['import_file']['tmp_name']);

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

                // Выводим сообщение об успешном импорте данных
                echo "<div class='notice notice-success is-dismissible'><p>Данные успешно импортированы из файла.</p></div>";
            } else {
                // Выводим сообщение об ошибке при загрузке файла
                echo "<div class='notice notice-error is-dismissible'><p>Ошибка при загрузке файла: " . $_FILES['import_file']['error'] . "</p></div>";
            }
        }

        

        global $wpdb;
        $url = $_SERVER['REQUEST_URI'];
        $matches = array();
        preg_match('/reginfoeduorg_subsection_(\d+)/', $url, $matches);
        $subsection_id = isset($matches[1]) ? intval($matches[1]) : 0;
        $subsection_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE id = %d", $subsection_id));
        // Проверяем, была ли кнопка "Сохранить изменения" нажата
        if (isset($_POST['save_changes'])) {
            $post_id = get_page_by_title($subsection_name)->ID;
            $content = $_POST['reginfoeduorg_content'];

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
        }

        if (isset($_POST['save_table_changes'])) {
            $data_to_update = $_POST['data'];
            $data_keys = array_keys($data_to_update);
            $data_values = array_values($data_to_update);

            for ($i = 0; $i < count($data_to_update); $i++) {
                $wpdb->update(
                    "{$wpdb->prefix}reginfoeduorg_general_information",
                    array($data_keys[$i] => $data_values[$i]),
                    array('id' => 1),
                    array('%s'),
                    array('%d')
                );
            }

            echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Данные таблицы обновлены.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button></div>';
        }

        echo '<div class="wrap">';
        echo '<form method="post" action="" enctype="multipart/form-data">';
        echo '<h1>' . $subsection_name . '</h1>';
        echo '<h3>Контент на странице</h3>';
        $content = $wpdb->get_var($wpdb->prepare("SELECT content FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE id = %d", $subsection_id));
        $editor_id = 'reginfoeduorg_content_editor';
        $settings = array(
            'textarea_name' => 'reginfoeduorg_content',
            'editor_height' => 200,
            'media_buttons' => true,
        );
        wp_editor($content, $editor_id, $settings);
        echo '<p>';
        echo '<input type="submit" name="save_changes" value="Сохранить изменения на сайте" class="button-primary">';
        echo '</p>';
        echo '<h3>Таблица данных подраздела</h3>';
        switch ($subsection_id) {
            case 1:
                $data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}reginfoeduorg_general_information", ARRAY_A);
                if ($data) {
                    if (isset($_POST['apply_styles'])) {
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
                        $xml = $this->generate_xml();
                        // Применяем XSLT-код к данным подраздела
                        $transformed_data = $this->apply_xslt($xml, $xslt_code);

                        // Обновляем поле для ввода контента на странице
                        $content = $transformed_data;
                        echo '<script>
                        jQuery(document).ready(function() {
                            var new_content = ' . json_encode($content) . ';
                            var editor = tinyMCE.get("' . $editor_id . '");
                            editor.setContent(new_content);
                        });
                        </script>';

                    }

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

                    foreach ($data as $row) {
                        foreach ($fields as $field_name => $field_key) {
                            echo '<tr>';
                            echo '<td class="column-name">' . $field_name . '</td>';
                            echo '<td class="column-value"><input type="text" name="data[' . $field_key . ']" value="' . $row[$field_key] . '"></td>';
                            echo '</tr>';
                        }
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
        echo '<br>';
        echo  '<input type="file" name="import_file" accept=".xml">';
        echo '<input type="submit" name="import_file_submit" value="Импортировать" class="button-primary">';
        
        echo '<br>';
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


    function apply_xslt($data, $xslt_code) {
        // Создаем экземпляр XSLTProcessor
        $xslt_processor = new XSLTProcessor();

        // Создаем экземпляр DOMDocument для XSLT-кода и загружаем его
        $xslt = new DOMDocument();
        $xslt->loadXML($xslt_code);

        // Импортируем XSLT-стили в XSLTProcessor
        $xslt_processor->importStylesheet($xslt);
        
        // Применяем XSLT-код к XML-данным
        $transformed_data = $xslt_processor->transformToXml($data);


        return $transformed_data;
    }


    function generate_xml() {
        global $wpdb;

        // Выбираем данные из таблицы reginfoeduorg_general_information
        $general_information = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}reginfoeduorg_general_information", ARRAY_A);

        // Выбираем пустую структуру XML для подраздела "Основные сведения" из базы данных
        $subsection_xml = $wpdb->get_var("SELECT xml FROM {$wpdb->prefix}reginfoeduorg_site_subsections WHERE name = 'Основные сведения'");

        // Загружаем пустую структуру XML и дополняем ее данными
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        $xml->loadXML($subsection_xml);
        // Находим элемент section_content для подраздела "Основные сведения"
        $section_content = $xml->getElementsByTagName('section_content')->item(0);

        // Удаляем имеющиеся элементы с данными
        while ($section_content->hasChildNodes()) {
            $section_content->removeChild($section_content->firstChild);
        }

        // Создаем элемент general_information
        $general_information_node = $xml->createElement('general_information');
        $section_content->appendChild($general_information_node);

        // Создаем элементы для каждого поля из таблицы и добавляем их в general_information
        foreach ($general_information as $key => $value) {
            $element = $xml->createElement($key, htmlspecialchars($value));
            $general_information_node->appendChild($element);
        }

        // Возвращаем готовый XML документ
        return $xml;
    }





}

if(class_exists('RegInfoEduOrg'))
{
    new RegInfoEduOrg(__FILE__);
}
