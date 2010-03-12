<?php

    // Подключаем класс точки
    include_once('Point.class.php');
    // Объявляем интерфейс активной области
    include_once('IHotspot.interface.php');
    // Подключаем класс точка-активная область
    include_once('hotspots/PointHotspot.class.php');

    /**
     * @class Фабрика активных областей
     */
    class HotspotMaker {

        /**
         * Счетчик создаваемых хотспотов.
         * Если хотспоту не указан приоритет, то приоритет
         * выставляется равным счетчик / 1e8,
         * чтобы не происходило коллизий на границах тайлов
         * @var Integer
         */
        private static $counter = 0;
        /**
         * Строит массив активных областей по готовому описанию
         * @param Array $json Описание активных областей
         * @return IHotspot[] Массив активных областей
         */
        public static function makeHotspots ($json) {
            return self::mapCollection($json['collection']);
        }

        /**
         * Строит массив активных областей, соответствующий
         * коллекции геообъектов
         * @param Array $json Описание коллекции
         * @return IHotspot[] Массив активных областей
         */
        private static function mapCollection ($json) {
            $style = $json['style'];
            $result = array();
            // Проходим по всем элементам коллекции
            foreach ($json['members'] as $member) {
                // Если есть поле members, элемент - вложенная коллекция
                if (isset($member['members'])) {
                    $result = array_merge($result, self::mapCollection($member));
                } else {
                    $result[] = self::mapMember($member, $style);
                }
            }
            return $result;
        }

        /**
         * Строит активную область по ее описанию
         * @param Array $member Описание элемента коллекции
         * @param Array $style Стиль коллекции
         * @return PointHotspot Активная область
         */
        private static function mapMember ($member, $style) {
            self::$counter++;

            if (!isset($member['metaDataProperty']) || !isset($member['metaDataProperty']['AnyMetaData'])) {
                $member['metaDataProperty'] = array ('AnyMetaData' => array() );
            }
            if (!isset($member['metaDataProperty']['AnyMetaData']['priority'])) {
                $member['metaDataProperty']['AnyMetaData']['priority'] = self::$counter / 1e6;
            }

            if ($member['Point']) {
                return new PointHotspot($member, $style);
            }
        }
    }

?>
