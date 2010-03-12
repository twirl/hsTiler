<?php

    /**
     * Прямоугольная фигура в составе активной области
     * @see IHotspot
     */
    class RectShape {

        public $left, $bottom, $right, $top, $style, $offset;

        public function __construct ($leftBottom, $rightTop, $style, $offset) {
            $this->left = $leftBottom->getX();
            $this->bottom = $leftBottom->getY();
            $this->right = $rightTop->getX();
            $this->top = $rightTop->getY();
            $this->style = $style;
            $this->offset = $offset;
        }

        public function intersects ($leftBottom, $rightTop) {
            return !(
                $this->right < $leftBottom->getX() ||
                $this->top < $leftBottom->getY() ||
                $this->left > $rightTop->getX() ||
                $this->bottom > $rightTop->getY()
            );
        }

        public function printSelf ($canvas, $tileLeftBottom) {
            $position = new Point(
                $this->left - $tileLeftBottom->getX() + $this->offset->getX(),
                $this->bottom - $tileLeftBottom->getY() + $this->offset->getY()
            );

            $size = new Point(
                $this->style['iconStyle']['size']['x'],
                $this->style['iconStyle']['size']['y']
            );

            Application::get()->imageProcessor->copyOn(
                $canvas,
                Application::get()->imageProcessor->getImage($this->style['iconStyle']['href']),
                $position,
                $size
            );
        }

        public function getDescription ($base) {
            return '[' .
                ($this->left - $base->getX()) . ',' .
                ($this->bottom - $base->getY()) . ',' .
                ($this->right - $base->getX()) . ',' .
                ($this->top - $base->getY()) . ',' .
            ']';
        }
    }

?>
