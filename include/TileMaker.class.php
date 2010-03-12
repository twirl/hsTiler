<?php

    include_once('HotspotMaker.class.php');
    include_once('Point.class.php');

    /**
     * Статический класс, готовящий тайлы по
     * json-описанию объектов
     */
    class TileMaker {

        private static $queue = array(),
                       $hotspots = array(),
                       $options = array(),
                       $zoom = 0,
                       $styleNumber = 0,
                       $styles = array();

        /**
         * Готовит тайлы
         * @param Array $json json-описание объектов
         * @param Array $options Опции
         */
        public static function makeTiles ($json, $options) {
            // Готовим массив активных областей в виде объектов IHotspot
            self::$hotspots = HotspotMaker::makeHotspots($json);
            self::$options = $options;
            // В очереди находятся объекты вида array($tile, $indexes)
            // $tile - номер тайла, $indexes - индексы активных областей,
            // которые могут лежаь в этом тайле. Изначально в очередь помещается
            // тайл (0, 0) и индексы всех активных областей.
            self::$queue[] = array(new Point(0, 0), array_keys(self::$hotspots));
            self::processQueue();
            // Сохраняем все стили в отдельный js-файл
            self::saveStyles(self::$options['directory'] . '/' . self::$options['stylesFile']);
        }

        /**
         * Проходит списку номеров тайлов и готовит
         * png и js описания
         */
        private static function processQueue () {
            self::makeDir(self::$options['directory']);

            // На каждом проходе по очереди готовятся тайлы для
            // определенного масштаба и формируется новая очередь
            // для следующего масштаба
            while (count(self::$queue) > 0) {
                $newQueue = array();
                foreach (self::$queue as $entry) {
                    $indexes = self::processTile($entry[0], $entry[1]);
                    if (count($indexes) > 0 && self::$zoom < self::$options['maxZoom']) {
                        $newTile = $entry[0]->scale(2);
                        array_push($newQueue,
                            array($newTile, $indexes),
                            array($newTile->moveBy(new Point(1,0)), $indexes),
                            array($newTile->moveBy(new Point(1,1)), $indexes),
                            array($newTile->moveBy(new Point(0,1)), $indexes)
                        );
                    }
                }

                self::$zoom++;
                self::$queue = $newQueue;
            }
        }

        /**
         * Создает папку
         * @param String $dir Имя папки
         */
        private static function makeDir ($dir) {
            if (!is_dir($dir)) {
                mkdir($dir, self::$options['permissions'], true);
            }

            if (!is_writable($dir)) {
                throw new Exception('Директория ' . $dir . ' не доступна на запись');
            }
        }

        /**
         * Функция, возвращающая результат сравнения двух
         * активных областей по приоритету. Нужна для передачи
         * в usort
         * @param IHotspot $h1 Первая активная область
         * @param IHotspot $h2 Вторая активная область
         * @return Integer 1, если приоритет первой области меньше,
         * -1 в противном случае
         */
        private static function priorityCompare ($h1, $h2) {
            return ($h1->getPriority() < $h2->getPriority() ? 1 : -1);
        }

        /**
         * Готовит пару тайлов с заданным номером
         * @param Point $tile Номер тайла
         * @param Array $indexes Список номеров активных областей,
         * которые могут находиться в этом тайле
         * @return Array Список номеров активных областей,
         * которые действительно находятся в этом тайле
         */
        private static function processTile ($tile, $indexes) {
            $hotspots = array();
            $hotspotIndexes = array();

            foreach ($indexes as $index) {
                $hotspot = self::$hotspots[$index];
                if ($hotspot->intersects($tile, self::$zoom)) {
                    $hotspots[] = $hotspot;
                    $hotspotIndexes[] = $index;
                }
            }

            if (count($hotspots) > 0 && self::$zoom >= self::$options['minZoom']) {

                $image = Application::get()->imageProcessor->createTile();

                $description = 'YMaps.Hotspots.Loader.onLoad("' . self::getKey($tile) . '",{"objects":[';

                usort($hotspots, array(__CLASS__, 'priorityCompare'));

                $isNotEmpty = false;
                foreach ($hotspots as $hotspot) {
                    $style = $hotspot->getComputedStyle();
                    $key = self::$options['styleKey'] . '#' . self::getContentStyle($style['hintContentStyle']['template'], $style['balloonContentStyle']['template']);
                    $description .= ($isNotEmpty ? ',' : '') . $hotspot->getDescription($tile, self::$zoom, $key);
                    $hotspot->printSelf($image, $tile, self::$zoom);
                    $isNotEmpty = true;
                }

                $description .= ']});';

                $imageFilename = self::$options['directory'].'/'.self::getImageName($tile);
                self::makeDir(dirname($imageFilename));
                Application::get()->imageProcessor->saveImage($image, $imageFilename);
                $jsFilename = self::$options['directory'].'/'.self::getJsName($tile);
                self::makeDir(dirname($jsFilename));
                $handle = fopen ($jsFilename, 'w');
                fwrite($handle, $description);
                fclose($handle);
            }

            return $hotspotIndexes;
        }

        /**
         * Возвращает ключ запроса для конкретного тайла
         * @param Point $tile Номер тайла
         * @return String Ключ запроса
         */
        private static function getKey ($tile) {
            return self::processTemplate(self::$options['keyTemplate'], $tile);
        }

        /**
         * Возвращает имя картиночного файла для конкретного тайла
         * @param Point $tile Номер тайла
         * @return String Имя картиночного файла
         */
        private static function getImageName ($tile) {
            return self::processTemplate(self::$options['imageTemplate'], $tile);
        }

        /**
         * Возвращает имя js-файла для конкретного тайла
         * @param Point $tile Номер тайла
         * @return String Имя js-файла
         */
        private static function getJsName ($tile) {
            return self::processTemplate(self::$options['jsTemplate'], $tile);
        }

        /**
         * Подставляет в шаблон номер тайла и масштаб
         * @param String $template Шаблон (имени файла или ключа)
         * @param Point $tile Номер тайла
         * @return String Результат подстановка
         */
        private static function processTemplate ($template, $tile) {
            return str_ireplace(
                array('%x', '%y', '%z'),
                array($tile->getX(), $tile->getY(), self::$zoom),
                $template);
        }

        /**
         * Возвращает имя стиля по переданным именам шаблонов
         * контента всплывающей подсказки и балуна. Если такого
         * стиля еще нет, создает его.
         * @param String $hint Имя шаблона всплывающей подсказки
         * @param String $balloon Имя шаблона балуна
         * @return String Имя стиля
         */
        private static function getContentStyle ($hint, $balloon) {
            $hint = Templates::trim($hint);
            $balloon = Templates::trim($balloon);

            if (!self::$styles[$hint]) {
                self::$styles[$hint] = array();
            }
            if (!self::$styles[$hint][$balloon]) {
                self::$styles[$hint][$balloon] = 'style' . (self::$styleNumber++);
            }
            return self::$styles[$hint][$balloon];
        }

        /**
         * Сохраняет все стили в файл
         * @param String $filename Имя файла
         */
        private static function saveStyles ($filename) {
            $handle = fopen($filename, 'w');
            if (!$handle) {
                throw new Exception('Файл ' . htmlspecialchars($filename) . ' недоступен для записи');
            }

            $styleKey = self::$options['styleKey'];
            $text = '(function(){var makeStyle=function(key,hint,balloon){' .
                'var s = new YMaps.Style();' .
                'if(hint){s.hintContentStyle={template:"' . $styleKey . '#"+hint};}' .
                'if(balloon){s.balloonContentStyle={template:"' . $styleKey . '#"+balloon};}' .
                'YMaps.Styles.add("' . $styleKey . '#"+key,s);'
            . '};' .
            'var makeTemplate=function(key, template){' .
                'var t=new YMaps.Template(template);' .
                'YMaps.Templates.add("' . $styleKey . '#"+key,t);' .
            '};';

            $hintTemplates = array_keys(self::$styles);
            foreach ($hintTemplates as $template) {
                if ($template) {
                    $text .= 'makeTemplate("' .$template . '",' . json_encode(Templates::get($template)) . ');';
                }
            }

            $balloonTemplates = array();
            foreach (self::$styles as $arr) {
                $balloonTemplates = array_keys($arr);
            }
            $balloonTemplates = array_unique($balloonTemplates);
            foreach ($balloonTemplates as $template) {
                if ($template) {
                    $text .= 'makeTemplate("' .$template . '",' . json_encode(Templates::get($template)) . ');';
                }
            }

            foreach (self::$styles as $hint => $arr) {
                foreach ($arr as $balloon => $key) {
                    $text .= 'makeStyle("' . $key . '","' . $hint . '","' . $balloon . '");';
                }
            }

            $text .= '})();';

            fwrite($handle, $text);
            fclose($handle);
        }
    };

?>
