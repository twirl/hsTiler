<?php

    /**
     * @class Точка
     */
    class Point {

        // Первая и вторая координаты точки
        private $x, $y;

        /**
         * Создает точку по координатам
         * @param Float $x Первая координата
         * @param Float $y Вторая координата
         */
        public function __construct ($x, $y) {
            $this->x = floatval($x);
            $this->y = floatval($y);
        }

        /**
         * Возвращает первую координату
         * @return Float Значение первой координаты
         */
        public function getX() {
            return $this->x;
        }

        /**
         * Возвращает вторую координату
         * @return Float Значение второй координаты
         */
        public function getY() {
            return $this->y;
        }

        /**
         * Масштабирует точку
         * @param Float|Point $coeff Коэффициент
         * @return Point Новая точка
         */
        public function scale ($coeff) {
            if ($coeff instanceof Point) {
                return new Point ($this->x * $coeff->getX(), $this->y * $coeff->getY());
            } else {
                return new Point ($this->x * $coeff, $this->y * $coeff);
            }
        }

        /**
         * Сдвигает точку
         * @param Point $vector Вектор сдвига
         * @return Point Новая точка
         */
        public function moveBy ($vector) {
            return new Point ($this->x + $vector->getX(), $this->y + $vector->getY());
        }
        
        /**
         * Преобразует географические координаты в пиксельные
         * на указанном масштабе
         * @param Float $long Долгота
         * @param Float $lat Широта
         * @param Integer $zoom Коэффициент масштабирования
         * @return Point Пиксельные координаты 
         */
        public static function fromGeoPoint ($long, $lat, $zoom) {
            $tile = self::mercatorToTile(self::geoToMercator($long, $lat), $zoom);
            return $tile;
        }
        
        const radius = 6378137, // Радиус Земли
              equator = 40075016.685578488, // Длина Экватора
              e = 0.0818191908426, // Эксцентриситет
              e2 = 0.00669437999014; // Эксцентриситет в квадрате

        /**
         * Преобразует географические координаты в меркаторовские
         * @param Float $long Долгота
         * @param Float $lat Широта
         * @return Point Меркаторовские координаты
         */
        private static function geoToMercator ($long, $lat) {
            $longitude = $long * M_PI / 180.0;
            $latitude = $lat * M_PI / 180.0;
            $esinLat = self::e * sin($latitude);
            $tan_temp = tan(M_PI / 4.0 + $latitude / 2.0);
            $pow_temp = pow(tan(M_PI / 4.0 + asin($esinLat) / 2.0), self::e);

            return new Point(self::radius * $longitude, self::radius * log($tan_temp / $pow_temp));
        }

        /**
         * Преобразует меркаторовские координаты в пиксельные
         * на заданном масштабе
         * @param Point $point Меркаторовские координаты
         * @param Integer $zoom Коэффициент масштабирования
         * @return Point Пиксельные координаты
         */
        private static function mercatorToTile ($point, $zoom) {
            $worldSize = 256 * pow(2, $zoom);
            $a = $worldSize / self::equator;
            $b = self::equator / 2.0;
            return new Point(
                round(($b + $point->getX()) * $a),
                round(($b - $point->getY()) * $a)
            );
        }

    }

?>
