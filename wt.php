<?php

if (!class_exists('Wt')) {
    class Wt
    {
        static $contacts;
        static $geo;

        protected static $instance;

        protected function __construct()
        {
        }

        private function __clone()
        {
        }

        private function __wakeup()
        {
        }

        public static function getInstance()
        {
            if (null === static::$instance) {
                static::$instance = new static();
            }

            return static::$instance;
        }

        public static function setContacts($value)
        {
            self::$contacts = $value;
        }

        public static function setGeo($value)
        {
            self::$geo = $value;
        }
    }

    Wt::getInstance();
}

?>