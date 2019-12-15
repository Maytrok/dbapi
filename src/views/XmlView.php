<?php

namespace dbapi\views;

use dbapi\db\Database;
use dbapi\tools\App;
use Exception;
use SimpleXMLElement;

class XmlView extends JsonView
{

    public static function setEncoding()
    {
        header('Content-Type: application/xml; charset=utf-8');
    }

    public function output()
    {

        $xml = new SimpleXMLElement('<root/>');
        self::setEncoding();

        if (!key_exists("error", $this->data)) {

            $xml->addChild('status', "Ok");

            if (key_exists($this->dataKey, $this->data)) {

                if (count($this->data[$this->dataKey]) > 1) {

                    $xml->addChild('count', count($this->data[$this->dataKey]));
                }
            }
        }
        foreach ($this->data as $key => $value) {
            $this->recurse($xml, $key, $value);
        }

        echo $xml->asXML();

        // echo json_encode($this->data);
    }

    private function recurse(SimpleXMLElement $element, $key, $data)
    {

        if (is_array($data)) {
            $el = $element->addChild($key);

            foreach ($data as $arrkey => $arrvalue) {
                $k = is_numeric($arrkey) ? "item" : $arrkey;
                $this->recurse($el,  $k, $arrvalue);
            }
        } else {
            $element->addChild($key, $data);
        }
    }
}
