<?php

    /**
     * Хранилище стилей
     */
    class Styles {

        private static $styles = array();

        /**
         * Удаляет из имени стиля знак #
         * @param String $key Имя стиля
         * @return String Имя стиля без #
         */
        public static function trim ($key) {
            return str_replace('#', '', $key);
        }

        /**
         * Добавляет в хранилище новый стиль
         * @param String $key Имя стиля
         * @param Array $style Стиль
         */
        public static function add ($key, $style) {
            self::$styles[self::trim($key)] = $style;
        }

        /**
         * Возвращает стиль по имени
         * @param String $key Имя стиля
         * @return Array Стиль
         */
        public static function get ($key) {
            $key = self::trim($key);
            if (isset(self::$styles[$key])) {
                return self::$styles[$key];
            }
            return array();
        }

        /**
         * Стиль по умолчанию
         * @var Array
         */
        public static $defaultStyle = array (
            'iconStyle' => array (
                'href' => 'icons/default.png',
                'offset' => array (
                    'x' => -7,
                    'y' => -28
                ),
                'size' => array (
                    'x' => 28,
                    'y' => 29
                ),
                'shadow' => NULL,
                'template' => '#iconTemplate'
            ),
            'parent' => NULL,
            'hintContentStyle' => '#hintTemplate',
            'balloonContentStyle' => '#balloonTemplate',
            'lineStyle' => array (
                'strokeColor' => 'FF0000FF',
                'strokeWidth' => '1'
            ),
            'polygonStyle' => array (
                'strokeColor' => 'FF0000FF',
                'strokeWidth' => '1',
                'fillColor' => 'FF0000FF',
                'fill' => true,
                'outline' => true
            )
        );

        /**
         * Расширяет один стиль другим. При этом все поля,
         * не указанные в первом стиле, заполняются значениями
         * из второго стиля
         * @param Array $style1 Первый стиль
         * @param Array $style2 Второй стиль
         * @return Array Расширенный стиль
         */
        public static function extend ($style1, $style2) {
           $result = array_slice($style1, 0);

           foreach ($style2 as $k => $v) {
               if (isset($result[$k])) {
                   if (is_array($v)) {
                       $result[$k] = self::extend($result[$k], $v);
                   }
               } else {
                   $result[$k] = $v;
               }
           }

           if (isset($style1['iconStyle']) &&  isset($style1['iconStyle']['shadow']) && $style1['iconStyle']['shadow'] === NULL) {
               $result['iconStyle']['shadow'] = NULL;
           }
           return $result;
        }

        /**
         * Возвращает полный вычисленный стиль
         * @param String $styleKey Имя стиля
         * @param Array $groupStyle Стиль коллекции
         * @param Boolean $full true - дополнить стиль значениями
         * по умолчанию, false - нет
         * @return Array Полный стиль
         */
        public static function getComputedStyle ($styleKey, $groupStyle, $full) {
            $result = self::get($styleKey);
            $parentKey = isset($result['parent']) ? $result['parent'] : NULL;

            while ($parentKey) {
                $parentStyle = self::get($parentKey);
                $parentKey = $parentStyle['parent'];
                $result = self::extend($result, $parentStyle);
            }

            if ($groupStyle) {
                $result = self::extend($result, $groupStyle);
            }

            if ($full) {
                $result = self::extend($result, self::$defaultStyle);
            }

            return $result;
        }

    };

?>
