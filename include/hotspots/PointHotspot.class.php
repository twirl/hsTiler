<?php

    include('RectShape.class.php');

    /**
     * Активная область - иконка
     * @see IHotspot
     */
    class PointHotspot implements IHotspot {

        private $long, $lat, $pixelOffsets, $style, $data, $priority,
                $cache = array ();

        public function __construct ($json, $groupStyle) {
            $parts = explode(' ', $json['Point']['pos']);
            $this->long = floatval($parts[0]);
            $this->lat = floatval($parts[1]);

            $style = isset($json['style']) ? $json['style'] : NULL;
            $this->style = Styles::getComputedStyle($style, $groupStyle, true);

            $metaData = isset($json['metaDataProperty']) && isset($json['metaDataProperty']['anyMetaData']) ?
                            $json['metaDataProperty']['anyMetaData'] :
                            array();
            
            $this->priority = isset($metaData['priority']) ? floatval($metaData['priority']) : 0;

            $size = $this->style['iconStyle']['size'];
            $offset = $this->style['iconStyle']['offset'];

            $this->pixelOffsets = array(
                intval($offset['x']), intval($offset['y']),
                intval($size['x']) + intval($offset['x']), intval($size['y']) + intval($offset['y'])
            );

            $this->data = $json;
        }

        public function intersects ($tileNumber, $zoom) {
            $shapes = $this->getPixelShapes($zoom);
            $tileLeftBottom = $tileNumber->scale(256);
            $tileRightTop = $tileNumber->moveBy(new Point(1, 1))->scale(256);

            foreach ($shapes as $shape) {
                if ($shape->intersects($tileLeftBottom, $tileRightTop)) {
                    return true;
                }
            }

            return false;
        }

        private static function cycleRestrict ($value, $min, $max) {
            return $value - floor(($value - $min)/($max - $min)) * ($max - $min);
        }

        public function getDescription ($tileNumber, $zoom, $style) {
            $shapes = $this->getPixelShapes($zoom);
            $shapesDescription = array();
            $tileLeftBottom = $tileNumber->scale(256);
            $tileRightTop = $tileNumber->moveBy(new Point(1, 1))->scale(256);
            $pixelCenter = Point::fromGeoPoint($this->long, $this->lat, $zoom);
            foreach ($shapes as $shape) {
                if ($shape->intersects($tileLeftBottom, $tileRightTop)) {
                    $shapesDescription[] = $shape->getDescription($pixelCenter);
                }
            }

            return '{' .
                '"data":' . json_encode($this->data) . ',' .
                '"style":' . json_encode($style) . ',' .
                '"base":new YMaps.GeoPoint(' . $this->long . ',' . $this->lat . '),' .
                '"geometry":[' . join(',', $shapesDescription) . '],' .
                '"priority":' . $this->priority .
            '}';
        }

        public function getPriority () {
            return $this->priority;
        }

        public function printSelf ($canvas, $tileNumber, $zoom) {
            $shapes = $this->getPixelShapes($zoom);
            $tileLeftBottom = $tileNumber->scale(256);
            $tileRightTop = $tileNumber->moveBy(new Point(1, 1))->scale(256);

            foreach ($shapes as $shape) {
                if ($shape->intersects($tileLeftBottom, $tileRightTop)) {
                    $shape->printSelf($canvas, $tileLeftBottom);
                }
            }
        }

        /**
         * Возвращает массив геометрических фигур, соответствующих
         * области на данном масштабе
         * @param Integer $zoom Масштаб
         * @return Array Массив RectShape
         */
        private function getPixelShapes ($zoom) {
            if (isset($this->cache['zoom']) && ($this->cache['zoom']== $zoom)) {
                return $this->cache['pixelShapes'];
            }

            $pixelCenter = Point::fromGeoPoint($this->long, $this->lat, $zoom);
            $worldSize = 256 * pow(2, $zoom);
            $left = self::cycleRestrict($pixelCenter->getX() + $this->pixelOffsets[0], 0, $worldSize);
            $bottom = self::cycleRestrict($pixelCenter->getY() + $this->pixelOffsets[1], 0, $worldSize);
            $right = self::cycleRestrict($pixelCenter->getX() + $this->pixelOffsets[2], 0, $worldSize);
            $top = self::cycleRestrict($pixelCenter->getY() + $this->pixelOffsets[3], 0, $worldSize);

            $pixelShapes = array();
            if ($left <= $right) {
                 $pixelShapes[] = new RectShape(new Point ($left, $bottom), new Point ($right, $top), $this->style, new Point(0,0));
            } else {
                 $pixelShapes[] = new RectShape(
                     new Point (0, $bottom),
                     new Point ($right, $top),
                     $this->style,
                     new Point($right - $this->style['iconStyle']['size']['x'], 0)
                 );
                 $pixelShapes[] = new RectShape(new Point ($left, $bottom), new Point ($worldSize, $top), $this->style, new Point(0,0));
            }

            $this->cache = array ('zoom' => $zoom, 'pixelShapes' => $pixelShapes);
            return $pixelShapes;
        }

        public function getComputedStyle () {
            return $this->style;
        }
    };

?>
