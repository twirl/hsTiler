<?php
    set_time_limit(600);

    // Подключаем парсер YMapsML
    include_once('XmlParser.class.php');
    // Подключаем построитель тайлов
    include_once('TileMaker.class.php');
    // Объявляем интерфейс процессора изображений
    include_once('IImageProcessor.interface.php');

    /**
     * @class Singleton приложение для подготовки слоя тайлов
     */
    class Application {
        /**
         * Инстанция приложения
         */
        private static $instance = NULL;

        /**
         * Процессор изображений
         */
        public $imageProcessor;

        /**
         * Возвращает инстанцию приложения
         * @return Application Приложение
         */
        public static function get () {
            if (self::$instance == NULL) {
                self::$instance = new self;
            }
            return self::$instance;
        }

        private function __construct () {}

        private function __clone() {}

        /**
         * Инициирует запуск приложения
         * @param Struct $options Опции
         */
        public function run ($options) {
            // Пытаемся создать экзепляр процессора изображений
            if (!include_once($options['imageProcessor']['file'])) {
                throw new Exception('Файл ' . $options['imageProcessor']['file'] . ' процессора изображений недоступен');
            }

            $className = $options['imageProcessor']['name'];
            if (!class_exists($className)) {
                throw new Exception('Класс '. $className . ' процессора изображений не существует');
            }
            $this->imageProcessor = new $className ();

            // Разбираем исходный YMapsML
            $json = XmlParser::parse($options['XML']);
            // Делаем тайлы
            TileMaker::makeTiles($json, $options);
            // Делаем пример кода
            self::makeExample($options);
        }

        /**
         * Строит пример кода.
         * @param Struct $options Опции
         */
        private static function makeExample ($options) {
            $filename = $options['directory'] . '/' . $options['htmlExample'];
            $handle = fopen($filename, 'w');
            if (!$handle) {
                throw new Exception('Файл ' . htmlspecialchars($filename) . ' недоступен для записи');
            }

            $text = file_get_contents(dirname(__FILE__). '/example.html.tmpl');
            if (!$text) {
                throw new Exception('Файл example.html.tmpl недоступен для чтения');
            }

            foreach (array('imageTemplate', 'jsTemplate', 'keyTemplate', 'stylesFile', 'apiKey') as $key) {
                $text = str_replace('$[' . $key . ']', $options[$key], $text);
            }

            fwrite($handle, $text);
            fclose($handle);
        }
    }

?>