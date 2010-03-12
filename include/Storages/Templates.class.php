<?php

    /**
     * Хранилище шаблонов
     */
    class Templates {

        private static $templates = array();

        /**
         * Удаляет из имени шаблона знак #
         * @param String $key Имя шаблона
         * @return String Имя шаблона без #
         */
        public static function trim ($key) {
            return str_replace('#', '', $key);
        }

        /**
         * Добавляет в хранилище новый шаблон
         * @param String $key Имя шаблона
         * @param String $template Шаблон
         */
        public static function add ($key, $template) {
            self::$templates[self::trim($key)] = $template;
        }

        /**
         * Возвращает шаблон по имени
         * @param String $key Имя шаблона
         * @return String Шаблон
         */
        public static function get ($key) {
            $res = self::$templates[self::trim($key)];
            if ($res) {
                return $res;
            }
            return array();
        }

    };

    // Шаблоны по умолчанию
    Templates::add('iconTemplate', NULL);
    Templates::add('balloonTemplate', '<h3>[$name]</h3><p>[$description]</p>');
    Templates::add('hintTemplate', '<div>[$name]</div>');

?>
