<?php

/**
 * Класс для взаимодействия с IpGeoBase.
 * Является форком класса от пользователя faniska.
 *
 */
class Geo {

    var $ip;
    var $charset = 'utf-8';

    public function __construct($options = null) {

        $this->dirname = dirname(__file__);

        // ip
        if (isset($options['ip']) && $this->is_valid_ip($options['ip'])) {
            $this->ip = $options['ip'];
        } else {
            $this->ip = $this->get_ip();
        }
        // кодировка
        if (isset($options['charset']) && is_string($options['charset']) && $options['charset'] != 'windows-1251') {
            $this->charset = $options['charset'];
        }
    }

    /**
     * функция возвращет конкретное значение из полученного массива данных по ip
     * @param string - ключ массива. Если интересует конкретное значение. 
     * Ключ может быть равным 'inetnum', 'country', 'city', 'region', 'district', 'lat', 'lng'
     * @param bolean - устанавливаем хранить данные в куки или нет
     * Если true, то в куки будут записаны данные по ip и повторные запросы на ipgeobase происходить не будут.
     * Если false, то данные постоянно будут запрашиваться с ipgeobase
     * @return array OR string - дополнительно читайте комментарии внутри функции.
     */
    function get_value($key = null, $cookie = true) {
        $key_array = array('inetnum', 'country', 'city', 'region', 'district', 'lat', 'lng');
        if (!in_array($key, $key_array)) {
            $key = null;
        }
        $data = $this->get_data($cookie);
        if ($key) { // если указан ключ 
            if (isset($data[$key])) { // и значение есть в массиве данных
                return $data[$key]; // возвращаем строку с нужными данными
            } elseif ($cookie) { // иначе если были включены куки
                return $this->get_value($key, false); // пытаемся вернуть данные без использования cookie
            }
            return NULL; // если ничего нет - отдаем NULL
        }
        return $data; // иначе возвращаем массив со всеми данными            
    }

    /**
     * Получаем данные с сервера или из cookie
     * @param boolean $cookie
     * @return string|array
     */
    function get_data($cookie = true) {
        // если используем куки и параметр уже получен, то достаем и возвращаем данные из куки
        if ($cookie && isset($_COOKIE['geobase']) && stripcslashes($_COOKIE['geobase'])) {
            return unserialize(stripcslashes($_COOKIE['geobase']));
        }
        $data = $this->get_geobase_data();
        
        if (!empty($data) && $cookie) {
            setcookie('geobase', serialize($data), time() + 3600 * 24 * 7, '/'); //устанавливаем куки на неделю
        }
        return $data;
    }

    /**
     * функция получает данные по ip.
     * @return array - возвращает массив с данными
     */
    function get_geobase_data() {
        // получаем данные по ip
        $ch = curl_init('http://ipgeobase.ru:7020/geo?ip=' . $this->ip);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        $string = curl_exec($ch);
        // если указана кодировка отличная от windows-1251, изменяем кодировку
        if ($this->charset != 'windows-1251' && function_exists('iconv')) {
            $string = iconv('windows-1251', $this->charset, $string);
        }
        $data = $this->parse_string($string);
        return $data;
    }

    /**
     * функция парсит полученные в XML данные в случае, если на сервере не установлено расширение Simplexml
     * @return array - возвращает массив с данными
     */
    function parse_string($string) {
        $params = array('inetnum', 'country', 'city', 'region', 'district', 'lat', 'lng');
        $data = $out = array();
        foreach ($params as $param) {
            if (preg_match('#<' . $param . '>(.*)</' . $param . '>#is', $string, $out)) {
                $data[$param] = trim($out[1]);
            }
        }
        return $data;
    }

    /**
     * функция определяет ip адрес по глобальному массиву $_SERVER
     * ip адреса проверяются начиная с приоритетного, для определения возможного использования прокси
     * @return ip-адрес
     */
    function get_ip() {
        $keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR', 'HTTP_X_REAL_IP');
        foreach ($keys as $key) {
            if (empty($_SERVER[$key])) continue;
            
            $ip = trim(strtok($_SERVER[$key], ','));
            if ($this->is_valid_ip($ip)) {
                return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
            }
        }
    }

    /**
     * функция для проверки валидности ip адреса
     * @param ip адрес в формате 1.2.3.4
     * @return bolean : true - если ip валидный, иначе false
     */
    function is_valid_ip($ip = null) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }
        return false; // иначе возвращаем false
    }

    /**
     * функция для присваивания значений в cookie
     * @param data массив значений для сохранения
     * @return array
     */
    function set_cookie($data = null) {
        // Проверяем наличие массива
        if (!is_array($data)) return false;

        // Получаем данные из cookie
        if (stripcslashes($_COOKIE ['geobase'])) {
            $data_cookie = unserialize(stripcslashes($_COOKIE ['geobase']));
        }else $data_cookie = array();

        // Обновляем данные
        foreach ($data as $key => $value) {
            $data_cookie[$key] = $value;
        }

        if (!empty($data_cookie)) {
            setcookie('geobase', serialize($data_cookie), time() + 3600 * 24 * 7, '/'); //устанавливаем куки на неделю
            return $data_cookie;
        }else return false;
    }

    /**
     * Очистка значений cookie
     */
    function cookie_clean(){
        setcookie('geobase', '', time()-3600, '/');
        unset($_COOKIE ['geobase']);
    }

}
