<?php

namespace Webmatrik\Integrations;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\Service;
use Bitrix\Main\Application;

class FeedBayut extends Feed
{
    protected static $root;

    public function __construct()
    {
        $server = Application::getInstance()->getContext()::getCurrent()->getServer();
        static::$root = $server->getDocumentRoot().'/pub/feed';
        parent::__construct();
    }

    public function makeBayutFeed() {
        self::cleanDir(static::$root);

        $data = [
            ['ref' => 111],
            ['ref' => 222]
        ];
        self::packtoXML($data);
    }

    protected static function packtoXML($data) {
        $inputUTF8 = <<<INPUT
            <?xml version="1.0" encoding="UTF-8"?>
            <Properties>
            </Properties>    
            INPUT;
        $root = simplexml_load_string($inputUTF8);
        foreach ($data as $key => $item) {
            $property = $root->addChild('Property');
            $property->Property_Ref_No = '<![CDATA['.$item['ref'].']]';

        }
        $root->asXML(static::$root."/bayutdubizzle.xml");
    }

    protected static function cleanDir($dir) {
        $files = glob($dir."/*");
        $c = count($files);
        if (count($files) > 0) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

}