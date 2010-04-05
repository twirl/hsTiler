<?php

    include_once('Storages/Templates.class.php');
    include_once('Storages/Styles.class.php');
    /**
     * Класс-преобразователь YMapsML в json-представление.
     */
    class XmlParser {

        /**
         * Статическая функция разбора XML-файла.
         * @param String $file Имя или URL файла. Для того, чтобы использовать
         * URL необходимо разрешить allow_url_fopen в настройках php.ini.
         */
        public static function parse ($file) {
            // Результат преобразования
            $res = array(
                'view' => array(),
                'collection' => array()
            );

            // Читаем из файла
            $text = file_get_contents($file);
            if (!$text) {
                throw new Exception('Ресурс "'.htmlspecialchars($file).'" недоступен для чтения');
            }

            $dom = new DOMDocument();
            $dom->loadXML($text);

            if (!$dom) {
                throw new Exception('Ресурс "'.htmlspecialchars($file).'" не является XML-документом');
            }

            $repr = $dom->getElementsByTagNameNS('*', 'Representation')->item(0);

            if ($repr) {
                $res['view'] = self::getView($repr);
                self::getStyles($repr);
                self::getTemplates($repr);
            }

            foreach ($dom->documentElement->childNodes as $child) {
                if ($child->localName == 'GeoObjectCollection') {
                    $res['collection'] = self::mapCollection($child, array());
                }
            }

            return $res;
        }

        /**
         * Возвращает json-описание секции View в Representation
         * @param DOMElement $repr DOM-элемент Representation
         * @return Array Описание View
         */
        private static function getView ($repr) {
            $view = $repr->getElementsByTagNameNS('*', 'View')->item(0);
            if ($view) {
                return self::mapJSON($view);
            }
            return array();
        }

        /**
         * Разбирает список стилей, указанных в Representation
         * и добавляет их в коллекцию стилей
         * @param DOMElement $repr DOM-элемент Representation
         */
        private static function getStyles ($repr) {
            $styles = $repr->getElementsByTagNameNS('*', 'Style');
            foreach ($styles as $style) {
                Styles::add($style->getAttribute('gml:id'), self::mapJSON($style));
            }
        }

        /**
         * Разбирает список шаблонов, указанных в Representation
         * и добавляет их в коллекцию шаблонов
         * @param DOMElement $repr DOM-элемент Representation
         */
        private static function getTemplates ($repr) {
            $templates = $repr->getElementsByTagNameNS('*', 'Template');
            foreach ($templates as $template) {
                Templates::add($template->getAttribute('gml:id'), self::nodeValue($template));
            }
        }

        /**
         * Список пользовательских полей в описании объекта
         * @var Array
         */
        private static $objectProperties = array('metaDataProperty', 'description', 'name');

        /**
         * Разбирает тэг GeoObjectCollection
         * @param DOMElement $collection DOM-элемент GeoObjectCollection
         * @param Array $parentStyle Стиль родительской коллекции
         * @return Array json-описание
         */
        private static function mapCollection ($collection, $parentStyle) {
            $res = array('members' => array (), 'style' => array_slice($parentStyle, 0));

            foreach ($collection->childNodes as $child) {
                if ($child->localName == 'style') {
                    $res['style'] = Styles::extend(
                        $res['style'],
                        Styles::getComputedStyle(self::nodeValue($child), NULL, false)
                    );
                }
            }

            foreach ($collection->childNodes as $child) {
                $name = $child->localName;
                if ($name != 'style') {
                    if (in_array($name, self::$objectProperties)) {
                        $res[$name] = self::mapJSON($child);
                    } else {
                        if ($name == 'featureMember' || $name == 'featureMembers') {
                            foreach ($child->childNodes as $member) {
                                $memberJSON = self::mapFeatureMember($member, $res['style']);
                                if ($memberJSON) {
                                    $res['members'][] = $memberJSON;
                                }
                            }
                        }
                    }
                }
            }

            return $res;
        }

        /**
         * Разбирает тэг FeatureMember
         * @param DOMElement $member DOM-элемент FeatureMember
         * @param Array $parentStyle Стиль родительской коллекции
         * @return Array json-описание
         */
        private static function mapFeatureMember ($member, $groupStyle) {
            $name = $member->localName;
            $res = NULL;
            if ($name == 'GeoObject') {
                $res = self::mapGeoObject($member);
            } else if ($name == 'GeoObjectCollection') {
                $res = self::mapCollection($member, $groupStyle);
            }

            return $res;
        }

        /**
         * Разбирает тэг GeoObject
         * @param DOMElement $object DOM-элемент GeoObject
         * @return Array json-описание
         */
        private static function mapGeoObject ($object) {
            return self::mapJSON($object);
        }

        /**
         * Список тэгов, которые автоматически преобразуются в
         * текст без просмотра дочерних элементов.
         * @var {String[]}
         */
        private static $htmlNodes = array('name', 'description');

        /**
         * Список типов DOM-нод, которые считаются текстом
         * @var Array
         */
        private static $textNodeTypes = array(XML_TEXT_NODE, XML_CDATA_SECTION_NODE, XML_COMMENT_NODE);

        /**
         * Проверяет, является ли переданная нода текстовой.
         * Нода считается текстовой, если у нее нет атрибутов и все
         * дети - текстовые ноды
         * @param DOMElement $element DOM-элемент
         * @return Boolean true - нода текстовая, false - нет
         */
        private static function isTextNode ($element) {
            if ($element->hasAttributes()) {
                return false;
            }

            foreach ($element->childNodes as $child) {
                if (!in_array($child->nodeType, self::$textNodeTypes)) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Возвращает текстовое значение ноды
         * @param DOMElement $element DOM-элемент
         * @return String Текстовое значение
         */
        private static function nodeValue ($element) {
            if (!$element->hasChildNodes()) {
                return $element->nodeValue;
            }

            $text = '';
            foreach ($element->childNodes as $child) {
                $text .= $child->nodeValue;
            }
            return $text;
        }

        /**
         * Преобразует фрагмент XML-дерева в JSON
         * @param DOMElement $element DOM-элемент
         * @return Array json-описание
         */
        private static function mapJSON ($element) {
            if (self::isTextNode($element)) {
                return self::nodeValue($element);
            }

            $res = array();
            $attributes = $element->attributes;
            foreach ($attributes as $attr) {
                $res[$attr->nodeName] = $attr->nodeValue;
            }

            foreach ($element->childNodes as $child) {
                if (!in_array($child->nodeType, self::$textNodeTypes)) {
                    $res[$child->localName] = (in_array($child->localName, self::$htmlNodes)) ?
                                                self::mapHTML($child) :
                                                self::mapJSON($child);
                }
            }

            return $res;
        }

        /**
         * Возвращает фрагмент XML-дерева в виде текстовой строки
         * @param DOMElement $element DOM-элемент
         * @return String Строковое представление содержимого DOM-элемента
         */
        private static function mapHTML ($element) {
            if (self::isTextNode($element)) {
                return self::nodeValue($element);
            }
            $text = $element->ownerDocument->saveXML($element);
            return str_replace(
                array('<' . $element->nodeName . '>', '</' . $element->nodeName . '>', '<' . $element->nodeName . '/>'),
                array( '', ''),
                $text);
        }

    }

?>
