<?php
    
    class GDImageProcessor implements IImageProcessor {

        private $images = array();

        public function createTile () {
            $image = imagecreatetruecolor(256, 256);
            imagesavealpha($image, true);
            imagealphablending($image, true);
            $bg = imagecolorallocatealpha($image, 255, 255, 255, 127);
            imagefill($image, 0, 0, $bg);
            return $image;
        }

        public function copyOn ($dst, $src, $pos, $size) {
            imagecopy($dst, $src, $pos->getX(), $pos->getY(), 0, 0, $size->getX(), $size->getY());
        }

        public function getImage ($href) {
            if ($this->images[$href]) {
                return $this->images[$href];
            }

            $image = imagecreatefromstring(file_get_contents($href));
            imagealphablending($image, true);
            imagesavealpha($image, true);
            $this->images[$href] = $image;
            return $image;
        }

        public function saveImage($image, $filename) {
            imagepng($image, $filename);
        }

    };

?>
