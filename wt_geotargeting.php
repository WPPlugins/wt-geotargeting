<?php
/*
Plugin Name: WT GeoTargeting
Plugin URI: http://web-technology.biz/cms-wordpress/plugin-wt-geotargeting
Description: Настройка геотаргетинга с помощью Шорткодов.
Version: 1.4.4
Author: Кусты Роман, АИТ "Web-Techology"
Author URI: http://web-technology.biz
*/

include('geo.php'); // Подключаем класс для работы с базой http://ipgeobase.ru
include('data_files.php');


/**
 * Class WtGeoTargeting
 */
class WtGeoTargeting
{
	var $ip;
	var $data = array();
	var $record = array();

	var $geo_contacts;

	function __construct(){
		add_action('plugins_loaded', array($this, 'initial'),8);
	}

	public static function basename() {
		return plugin_basename(__FILE__);
	}

	/**
	 * Инициализация плагина.
	 * 22.11.2016
	 *
	 * @version 1.4.4
	 * Перенес код из __construct() в initial(), так как возникла проблема инициализации.
     */
	public function initial(){
		// Открываем доступ к коду через статический класс WT плагина WT Static
		if (class_exists('Wt')){
			Wt::setObject('geo', $this);
		}

		// Подгружаем значения региона по умолчанию
		$option_default = get_option('wt_geotargeting_default');
		if (is_array($option_default))
			$this->data = array_merge($this->data, get_option('wt_geotargeting_default'));

		$options = array();

		// ТЕСТОВЫЙ РЕЖИМ
		// Проверяем роль пользователя для включения тестового режима
		if (is_user_logged_in() && current_user_can('administrator')){

			
			$options_debug = get_option('wt_geotargeting_debug');

			if (isset($options_debug['mode']) && $options_debug['mode'] == 'ip'
				&& isset($options_debug['ip']) && filter_var($options_debug['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)){

				$options['ip'] = $options_debug['ip'];
			}

			if (isset($options_debug['mode']) && $options_debug['mode'] == 'city'
				&& isset($options_debug['city_id'])){

				// Проверка и получение выбранного для тестирования города
				$data_files = new WtGeoTargetingDataFiles();
				$debug_geo_data = $data_files->getCityInfo($options_debug['city_id']);
			}

			if (isset($options_debug['mode']) && $options_debug['mode'] == 'country'
				&& isset($options_debug['country_alpha2'])){

				$debug_geo_data = array('country' => $options_debug['country_alpha2']);
			}
		}

		$geo = new Geo($options);
		$this->ip = $geo->ip;

		// Очищаем cookie
		if (isset($_GET['wt_geo_clean'])){
			$wt_geo_clean = strip_tags(urldecode($_GET['wt_geo_clean']));
			if ($wt_geo_clean == 1) $geo->cookie_clean();
		} 
		
		// Сохраняем массив значений региона для работы плагина

		if (isset($options_debug['mode']) && $options_debug['mode'] == 'ip'){

			// Получаем значения из IpGoeBase не сохраняя в cookie 
			$this->data = array_merge($this->data, $geo->get_value(null, false));

		}elseif (isset($options_debug['mode']) 
			&& ($options_debug['mode'] == 'city' || $options_debug['mode'] == 'country') 
			&& count($debug_geo_data) > 0){

			$this->data = array_merge($this->data, $debug_geo_data);

		}elseif ($this->checkDataDefault()){  // Default значения
			$data_default = $this->setDataDefault($geo);
			$this->data = array_merge($this->data, $data_default);

		}else{
			$this->data = array_merge($this->data, $geo->get_value());
		}
		
		// Регистрируем шорткод и хук для него
		add_shortcode('wt_geotargeting', array (&$this, 'shortcodeGeotargetingAction'));
	}


	/**
	 * Сохраняем Default значения
	 * 22.11.2016
	 *
	 * @version 1.4.4
	 * @param $geo Класс геотаргетинга
	 * @return array
     */
	function setDataDefault($geo = null)
    {
		if (isset($_GET['wt_country_by_default'])) $wt_country_by_default = strip_tags(urldecode($_GET['wt_country_by_default']));
		if (isset($_GET['wt_district_by_default'])) $wt_district_by_default = strip_tags(urldecode($_GET['wt_district_by_default']));
		if (isset($_GET['wt_region_by_default'])) $wt_region_by_default = strip_tags(urldecode($_GET['wt_region_by_default']));
		if (isset($_GET['wt_city_by_default'])) $wt_city_by_default = strip_tags(urldecode($_GET['wt_city_by_default']));
		
		$data_default = array(
			'country' => null,
			'district' => null,
			'region' => null,
			'city' => null
		);

		if (!empty($wt_country_by_default)) $data_default['country'] = $wt_country_by_default;
		if (!empty($wt_district_by_default)) $data_default['district'] = $wt_district_by_default;
		if (!empty($wt_region_by_default)) $data_default['region'] = $wt_region_by_default;
		if (!empty($wt_city_by_default)) $data_default['city'] = $wt_city_by_default;

		if (!empty($geo)) $geo->set_cookie($data_default);

		return $data_default;    	
    }


	/**
	 * Проверка наличия входящих дефолтных значений региона
	 * 22.11.2016
	 *
	 * @version 1.4.4
	 * @return bool
     */
	function checkDataDefault(){
		if (empty($_GET['wt_country_by_default']) &&
			empty($_GET['wt_district_by_default']) &&
			empty($_GET['wt_region_by_default']) &&
			empty($_GET['wt_city_by_default'])
		) return false;

		return true;
	}


	/**
	 * Шорткод [geotargeting]
	 *
	 * @param $param
	 * @param $content
     */
	function shortcodeGeotargetingAction($param, $content){
		
		// Определяем выводился-ли ранее контент для указанного типа, если да, то завершаем выполнение
		if (isset($param['type']) && isset($this->record[$param['type']]) &&
			$this->record[$param['type']] > 0)
			return;

		// Проверяем совпадение локаций

		if (!empty($this->data['city'])){

			if (!empty($param['city_show']) && $param['city_show'] == $this->data['city']){
				if (!empty($param['type'])) $this->record[$param['type']] = 1;
				return do_shortcode($content);
			}

			if (!empty($param['city_not_show']) && $param['city_not_show'] != $this->data['city']){
				if (!empty($param['type'])) $this->record[$param['type']] = 1;
				return do_shortcode($content);
			}
		}

		if (!empty($this->data['region'])) {

			if (!empty($param['region_show']) && $param['region_show'] == $this->data['region']) {
				if (!empty($param['type'])) $this->record[$param['type']] = 1;
				return do_shortcode($content);
			}

			if (!empty($param['region_not_show']) &&	$param['region_not_show'] != $this->data['region']) {
				if (!empty($param['type'])) $this->record[$param['type']] = 1;
				return do_shortcode($content);
			}
		}

		if (!empty($this->data['district'])) {

			if (!empty($param['district_show']) && $param['district_show'] == $this->data['district']) {
				if (!empty($param['type'])) $this->record[$param['type']] = 1;
				return do_shortcode($content);
			}

			if (!empty($param['district_not_show']) && $param['district_not_show'] != $this->data['district']) {
				if (!empty($param['type'])) $this->record[$param['type']] = 1;
				return do_shortcode($content);
			}
		}

		if (!empty($this->data['country'])) {

			if (!empty($param['country_show']) && $param['country_show'] == $this->data['country']){
				if (!empty($param['type'])) $this->record[$param['type']] = 1;
				return do_shortcode($content);
			}

			if (!empty($param['country_not_show']) && $param['country_not_show'] != $this->data['country']){
				if (!empty($param['type'])) $this->record[$param['type']] = 1;
				return do_shortcode($content);
			}
		}

		if (!empty($param['default']) && $param['default'] == true){
			
			if (!empty($param['type'])) $this->record[$param['type']] = 1;
			return do_shortcode($content);
		}

		// Вывод текущих значений
		if (!empty($param['get'])){

			if ($param['get'] == 'ip') return $this->ip;

			$return = $this->getRegion($param['get']);

			if (empty($return) && isset($content)) $return = $content;

			return $return;
		}

		return;
	}


	/**
	 * Получение региона
	 *
	 * @version 1.4.3
	 * @param string $type Тип региона (city, region, district, country)
	 * @return null
     */
	public function getRegion($type = 'city'){
		if (!empty($this->data[$type])) return $this->data[$type];

		return NULL;
	}

	/**
	 * Получить привязанную к региону контактную информацию
	 *
	 * @param null $type
	 * @param null $region
	 * @return null
     */
	public function getContact($type = null, $region = NULL){
		if (empty($this->geo_contacts)) $this->geoContactsReload();

		if (!$region && $this->getRegion()) $region = $this->getRegion();

		if (!$region) return NULL;

		if (empty($this->geo_contacts[$region])) return NULL;

		if (empty($type)) return $this->geo_contacts[$region];

		if (!empty($this->geo_contacts[$region][$type])) return $this->geo_contacts[$region][$type];

		return NULL;
	}

	/**
	 * Обновление справочника контактов
	 *
	 * @return bool|null
     */
	public function geoContactsReload(){
		$uploads_path = WP_CONTENT_DIR.'/uploads';
		$file_name = $uploads_path . '/multisite_geo_info.txt';

		if (!file_exists($uploads_path) || !file_exists($file_name)) return FALSE;

		$file_content = file_get_contents($file_name);

		if (empty($file_content)) return NULL;

		$this->geo_contacts = json_decode($file_content, true);
	}
}

$wt_geotargeting = new WtGeoTargeting();

if (defined('ABSPATH') && is_admin()) {
	require('admin_panel.php');
	$wt_geotargeting_admin = new WtGeoTargetingAdmin();
	$wt_geotargeting_admin->geotargeting = $wt_geotargeting;
}

?>