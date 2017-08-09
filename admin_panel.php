<?php


/**
 * Class WtGeoTargetingAdmin
 * Административная часть
 */
class WtGeoTargetingAdmin
{
	var $geo;
	var $data_files;
	var $debug_mode_array = array(
		'disabled' => 'Отключен',
		'ip' => 'По заданному IP',
		'city' => 'По городу',
		'country' => 'По стране'
		);


	/**
	 * @var WtGeoTargeting
     */
	var $geotargeting;

	function __construct(){

		// Добавляем страницу настроек в панель администратора
	    add_action('admin_menu', array(&$this, 'adminMenu'));

	    //Добавляем в описание плагина ссылку на справку.
	    add_filter('plugin_row_meta', 'WtGeoTargetingAdmin::pluginRowMeta', 10, 2);

	    add_action('admin_init', array(&$this, 'pluginSettings'));
	}

	/**
	 * Проверяем и подключаем справочники с данными
     */
	function dataFilesInit(){

		if (!is_object($this->data_files)) $this->data_files = new WtGeoTargetingDataFiles();
	}


	/**
	 * Добавляем страницу настроек в панель администратора
     */
	function adminMenu()
	{
	    // Добавляем в сайдбар раздел геотаргетинга
	    add_menu_page(
	    	'WT GeoTargeting - Настройки', 
	    	'WT GeoTargeting', 
	    	'manage_options', 
	    	'wt_geotargeting', 
	    	'',
	    	'dashicons-location-alt'
	    );

	    // В первом пункте вложенного меню дублируем slug с главного пункта меню, дабы избежать дублей
	    add_submenu_page(
	    	'wt_geotargeting', 
	    	'WT GeoTargeting - Инструкция', 
	    	'Справка', 
	    	'manage_options', 
	    	'wt_geotargeting',
	        array(&$this, 'adminPageReference')
	    );

	    add_submenu_page(
	    	'wt_geotargeting', 
	    	'WT GeoTargeting - Настройки', 
	    	'Настройки', 
	    	'manage_options', 
	    	'wt_geotargeting/admin/setting.php',
	        array(&$this, 'optionsPageOutput')
	    );
	}

	/**
	 * Страница с инструкцией
     */
	function adminPageReference()
	{
		include('admin/reference.php'); 
	}

	/**
	 * Добавление ссылок к описанию плагина
	 *
	 * @param $meta
	 * @param $file
	 * @return array
     */
	public static function pluginRowMeta($meta, $file) {
        if ($file == WtGeoTargeting::basename()) {
        	// Ссылка на страницу справки
            $meta[] = '<a href="options-general.php?page=wt_geotargeting">Как настроить геотаргетинг?</a>';
        }
        return $meta;
    }

	/**
	 * Формируем массив городов для справочника
	 *
	 * @return array
     */
	public function getCities(){
    	$options = array();

    	$cities_file_path = dirname(__FILE__) . '/cities.txt';	
    	$cities_file = fopen($cities_file_path, 'r');

    	// Перебираем данные файла
    	rewind($cities_file);
		while(!feof($cities_file))
		{
			$str = fgets($cities_file);				// Сохраняем строку
			if (empty($str)) continue;
			$arRecord = explode("\t", trim($str));	// Дробим на массив
			$city_id = $arRecord[0];
			$city_name = iconv('windows-1251', 'UTF-8', $arRecord[1]);		// Меняем кодировку с windows-1251 на UTF-8 и сохраняем
			$options[$city_id] = $city_name;
		}	

		asort($options);

    	return $options;
    }

	/**
	 * @param $id
	 * @return array
     */
	public function getCityInfo($id){
    	$city_info = array();

    	$cities_file_path = dirname(__FILE__) . '/cities.txt';	
    	$cities_file = fopen($cities_file_path, 'r');

    	// Перебираем данные файла
    	rewind($cities_file);
		while(!feof($cities_file))
		{
			$str = fgets($cities_file);				// Сохраняем строку
			$str = iconv('windows-1251', 'UTF-8', $str); // Меняем кодировку

			if (empty($str)) continue;
			$arRecord = explode("\t", trim($str));	// Дробим на массив
			if ($arRecord[0] != $id) continue;

			if (!empty($arRecord[0])) $city_info['id'] = $arRecord[0];
			if (!empty($arRecord[1])) $city_info['city'] = $arRecord[1];
			if (!empty($arRecord[2])) $city_info['region'] = $arRecord[2];
			if (!empty($arRecord[3])) $city_info['district'] = $arRecord[3];
			if (!empty($arRecord[4])) $city_info['lat'] = $arRecord[4];
			if (!empty($arRecord[5])) $city_info['lng'] = $arRecord[5];	
			break;	
		}	

    	return $city_info;
    }

    // ---------- НАСТРОЙКА ПЛАГИНА ----------

    /**
	 * Создаем страницу настроек плагина
	 */
	function optionsPageOutput(){
		?>
		<div class="wrap">
			<h2><?php echo get_admin_page_title() ?></h2>

			<form action="options.php" method="POST">
				<?php
					settings_fields( 'wt_geotargeting_group' );     // скрытые защитные поля
					do_settings_sections( 'wt_geotargeting_default_page' ); // секции с настройками (опциями).
					do_settings_sections( 'wt_geotargeting_debug_page' ); // секции с настройками (опциями).
					submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Регистрируем настройки.
	 * Настройки будут храниться в массиве, а не одна настройка = одна опция.
	 */
	function pluginSettings(){
		
		$this->dataFilesInit(); // Подключаем справочники

		// $option_group, $option_name, $sanitize_callback
		register_setting('wt_geotargeting_group', 'wt_geotargeting_debug', array(&$this, 'sanitizeCallback'));
		register_setting('wt_geotargeting_group', 'wt_geotargeting_default', array(&$this, 'sanitizeCallback'));

		// $id, $title, $callback, $page
		add_settings_section(
			'wt_geotargeting_default',
			'Региональные значения по умолчанию',
			array(&$this, 'displaySettingSectionDefaultInfo'),
			'wt_geotargeting_default_page');

		$field_params = array(
			'type'      => 'text', // тип
			'id'        => 'city',
			'option_name' => 'wt_geotargeting_default',
			'label_for' => 'city'
		);
		add_settings_field('city', 'Город', array(&$this, 'displaySettings'), 'wt_geotargeting_default_page', 'wt_geotargeting_default', $field_params);

		$field_params = array(
			'type'      => 'text', // тип
			'id'        => 'region',
			'option_name' => 'wt_geotargeting_default',
			'label_for' => 'region'
		);
		add_settings_field( 'region', 'Регион', array(&$this, 'displaySettings'), 'wt_geotargeting_default_page', 'wt_geotargeting_default', $field_params );

		$field_params = array(
			'type'      => 'text', // тип
			'id'        => 'district',
			'option_name' => 'wt_geotargeting_default',
			'label_for' => 'district'
		);
		add_settings_field( 'district', 'Округ', array(&$this, 'displaySettings'), 'wt_geotargeting_default_page', 'wt_geotargeting_default', $field_params );

		$field_params = array(
			'type'      => 'select',
			'id'        => 'country',
			'option_name' => 'wt_geotargeting_default',
			'vals'		=> $this->data_files->getCountriesForSelect()
		);
		add_settings_field( 'country', 'Страна посетителя', array(&$this, 'displaySettings'), 'wt_geotargeting_default_page', 'wt_geotargeting_default', $field_params );



		// $id, $title, $callback, $page
		add_settings_section(
			'wt_geotargeting_debug',
			'Тестирование и отладка',
			array(&$this, 'displaySettingSectionDebugInfo'),
			'wt_geotargeting_debug_page');

		$field_params = array(
			'type'      => 'select', // тип
			'id'        => 'mode',
			'option_name' => 'wt_geotargeting_debug',
			'desc'      => 'Выберите режим.', // описание
			'vals' => $this->debug_mode_array
			);
		add_settings_field( 'mode', 'Режим тестирования', array(&$this, 'displaySettings'), 'wt_geotargeting_debug_page', 'wt_geotargeting_debug', $field_params );
	 

		$field_params = array(
			'type'      => 'text', // тип
			'id'        => 'ip',
			'option_name' => 'wt_geotargeting_debug',
			'desc'      => 'Введите IP-адрес посетителя.', // описание
			'label_for' => 'ip' // позволяет сделать название настройки лейблом (если не понимаете, что это, можете не использовать), по идее должно быть одинаковым с параметром id
		);
		add_settings_field( 'ip', 'IP-адрес посетителя', array(&$this, 'displaySettings'), 'wt_geotargeting_debug_page', 'wt_geotargeting_debug', $field_params );
	 
		$field_params = array(
			'type'      => 'select',
			'id'        => 'city_id',
			'option_name' => 'wt_geotargeting_debug',
			'desc'      => 'Выберите город посетителя.',
			'vals'		=> $this->data_files->getCities()
		);
		add_settings_field( 'city_id', 'Город посетителя', array(&$this, 'displaySettings'), 'wt_geotargeting_debug_page', 'wt_geotargeting_debug', $field_params );

		$field_params = array(
			'type'      => 'select',
			'id'        => 'country_alpha2',
			'option_name' => 'wt_geotargeting_debug',
			'desc'      => 'Выберите страну посетителя.',
			'vals'		=> $this->data_files->getCountriesForSelect()
		);
		add_settings_field( 'country_alpha2', 'Страна посетителя', array(&$this, 'displaySettings'), 'wt_geotargeting_debug_page', 'wt_geotargeting_debug', $field_params );

		// Обновление файла с контактной информацией с мультисайта
		add_settings_field(
			'multisite_contacts_update',
			'Мультисайт',
			array(&$this, 'displaySettingButtonUpdateContactsMultisite'),
			'wt_geotargeting_debug_page',
			'wt_geotargeting_debug',
			array() );

		add_action(	 // Добавление скрипта для обработки нажатия кнопки
			'admin_print_footer_scripts',
			array(&$this, 'javascriptUpdateContactsMultisite'),
			99);

		add_action( // Регистрируем функцию для обработки ajax запроса
			'wp_ajax_update_contacts_multisite',
			array(&$this, 'callbackUpdateContactsMultisite'));
	}

	/*
	 * Функция отображения полей ввода
	 * Здесь задаётся HTML и PHP, выводящий поля
	 */

	/**
	 * Поясняющее сообщение для секции "Значения по умолчанию"
     */
	function displaySettingSectionDefaultInfo(){
		echo '<p>Указанные вами значения "По умолчанию" будут использоваться в случае отсутствия в базе "IpGeoBase" данных о местоположении посетителя.</p>';
	}

	/**
	 * Поясняющее сообщение для секции тестирования и отладки
     */
	function displaySettingSectionDebugInfo(){
		echo '<p>Воспользовавшись нижепредставленными полями вы можете протестировать работу сайта от лица пользователей из других регионов.<br>Тестирование возможно только администратором сайта.</p>';
	}


	/**
	 * Кнопка "Обновить контактную информацию с сайтов"
     */
	function displaySettingButtonUpdateContactsMultisite(){
		echo '<button type="button" onclick="click_update_contacts_multisite();">Обновить контактную информацию с сайтов</button>';
	}

	function javascriptUpdateContactsMultisite() {
		?>
		<script type="text/javascript" >
			function click_update_contacts_multisite() {
				var data = {
					action: 'update_contacts_multisite',
					whatever: 1234
				};
				// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
				jQuery.post( ajaxurl, data, function(response) {
					alert(response);
				});
			}
		</script>
		<?php
	}

	/**
	 * Выборка контактных данных с мультисайтов
     */
	function callbackUpdateContactsMultisite() {
		$contacts = array();

		$sites = wp_get_sites();

		$site_count = 0;
		foreach ($sites as $site){
			switch_to_blog($site['blog_id']);

			$site_contacts = (array) get_option('wt_contacts', array());

			if (empty($site_contacts['region'])) continue;

			$site_contacts['blog_id'] = $site['blog_id'];
			$contacts[$site_contacts['region']] = $site_contacts;

			$site_count++;
		}

		$file_contacts = json_encode($contacts);

		// Определяем каталог и создаем в нем файл
		$new_file = WP_CONTENT_DIR.'/uploads/multisite_geo_info.txt';
		wp_mkdir_p(dirname($new_file));

		// Открываем созданный файл для записи и сохраняем в него данные
		$ifp = @ fopen( $new_file, 'wb' );
		@fwrite( $ifp, $file_contacts);
		fclose( $ifp );
		clearstatcache();

		echo 'Информация успешно сохранена. '.$site_count.' мультисайт.';

		wp_die(); // выход нужен для того, чтобы в ответе не было ничего лишнего, только то что возвращает функция
	}


	/**
	 * Отображение элементов формы
	 *
	 * @param $args
     */
	function displaySettings($args) {
		extract( $args );

		$o = get_option( $option_name );
	 
		switch ( $type ) {  
			case 'text':  
				$o[$id] = esc_attr( stripslashes($o[$id]) );
				echo "<input class='regular-text' type='text' id='$id' name='" . $option_name . "[$id]' value='$o[$id]' />";  
				echo (!empty($desc)) ? "<br /><span class='description'>$desc</span>" : "";
			break;
			case 'textarea':  
				$o[$id] = esc_attr( stripslashes($o[$id]) );
				echo "<textarea class='code large-text' cols='50' rows='10' type='text' id='$id' name='" . $option_name . "[$id]'>$o[$id]</textarea>";  
				echo (!empty($desc)) ? "<br /><span class='description'>$desc</span>" : "";
			break;
			case 'checkbox':
				$checked = ($o[$id] == 'on') ? " checked='checked'" :  '';  
				echo "<label><input type='checkbox' id='$id' name='" . $option_name . "[$id]' $checked /> ";  
				echo (!empty($desc)) ? $desc : "";
				echo "</label>";  
			break;
			case 'select':
				echo "<select id='$id' name='" . $option_name . "[$id]'>";
				foreach($vals as $v=>$l){
					$selected = ($o[$id] == $v) ? "selected='selected'" : '';  
					echo "<option value='$v' $selected>$l</option>";
				}
				echo "</select>"; 
				echo (!empty($desc)) ? "<br /><span class='description'>$desc</span>" : "";
			break;
			case 'radio':
				echo "<fieldset>";
				foreach($vals as $v=>$l){
					$checked = ($o[$id] == $v) ? "checked='checked'" : '';  
					echo "<label><input type='radio' name='" . $option_name . "[$id]' value='$v' $checked />$l</label><br />";
				}
				echo "</fieldset>";  
			break;
			case 'info':  
				echo '<p>'.$text.'</p>';
			break; 
		}
	}

	/**
	 * Очистка данных
	 *
	 * @param $options
	 * @return mixed
     */
	function sanitizeCallback($options){
		foreach( $options as $name => & $val ){
			if( $name == 'input' )
				$val = strip_tags( $val );

			if( $name == 'checkbox' )
				$val = intval( $val );
		}
		return $options;
	}

}
?>