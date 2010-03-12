<?php

    /**
     * @interface Интерфейс активной области
     */
    interface IHotspot {
        /**
         * Создает область по ее описанию
         * @param Array $json Описание области
         * @param Array $groupStyle Стиль коллекции
         */
        public function __construct ($json, $groupStyle);

        /**
         * Проверяет, пересекает ли активная область
         * тайл с заданным номером на заданном масштабе
         * @param Point $tileNumber Номер тайла
         * @param Integer $zoom Уровень масштабирования
         * @return Boolean true - пересекает тайл, false - не пересекает
         */
        public function intersects ($tileNumber, $zoom);

        /**
         * Возвращает описание активной области в
         * стандартном формате обмена данными API Яндекс.Карт
         * @param Point $tileNumber Номер тайла
         * @param Integer $zoom Уровень масштабирования
         * @param String $contentStyle Ключ стиля
         */
        public function getDescription($tileNumber, $zoom, $contentStyle);

        /**
         * Возвращает приоритет области
         * @return Float Приоритет
         */
        public function getPriority ();

        /**
         * Наносит изображение области на переданный тайл
         * @param Object $canvas Тайл
         * @param Number $tileNumber Номер тайла
         * @param Integer $zoom Уровень масштабирования
         */
        public function printSelf ($canvas, $tileNumber, $zoom);

    };

?>
