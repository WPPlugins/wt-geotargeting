<?php

/**
 * Class WtGeoTargetingDataFiles
 */
class WtGeoTargetingDataFiles
{
	function __construct(){
		
	}

	/**
	 * Формируем массив городов для справочника
	 *
	 * @return array
     */
	public function getCountriesForSelect(){
    	$options = array();

    	$countries_file_path = dirname(__FILE__) . '/countries.txt';	
    	$countries_file = fopen($countries_file_path, 'r');

    	// Перебираем данные файла
    	rewind($countries_file);

    	$title = fgets($countries_file); // Заголовки

		while(!feof($countries_file))
		{
			$str = fgets($countries_file);				// Сохраняем строку
			if (empty($str)) continue;
			$arRecord = explode("\t", trim($str));	// Дробим на массив
			$country_alpha2 = $arRecord[3];
			$country_name = $arRecord[0];		// Меняем кодировку с windows-1251 на UTF-8 и сохраняем
			$options[$country_alpha2] = $country_name . ' ('.$country_alpha2.')';
		}	

    	return $options;
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

		asort($options); // Сортирует массив, сохраняя ключи

    	return $options;
    }


	/**
	 * Получить информацию о городе
	 *
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
}
?>