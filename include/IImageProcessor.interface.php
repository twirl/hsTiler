<?php

    /**
     * @interface Интерфейс процессора изображений
     */
    interface IImageProcessor {

        /**
         * Создает пустой тайл
         * @return Object Тайл
         */
        public function createTile ();

        /**
         * Наносит на тайл изображение
         * @param Object $dst Тайл
         * @param Object $src Изображение
         * @param Point $pos Позиция
         * @param Point $size Размер изображения
         */
        public function copyOn ($dst, $src, $pos, $size);

        /**
         * Возвращает изображение по указанному URL
         * @param Object $href URL
         * @return Object Изображение
         */
        public function getImage ($href);

        /**
         * Сохраняет изображение в указанный файл
         * @param Object $image Изображение
         * @param String $filename Имя файла
         */
        public function saveImage ($image, $filename);

    };

?>
