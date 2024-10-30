<?php

namespace Webmatrik\Integrations;

use \Webmatrik\Integrations\Integration;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use \Bitrix\Main\Data\Cache;
use \Bitrix\Main\Application;

abstract class AbstractIntegration implements Integration
{
    /**
     * @var int
     */
    protected $gmt = 0;
    protected $token;
    protected $subscribed = false;

    public function __construct()
    {
        \Bitrix\Main\Loader::includeModule('crm');
    }

    protected function isSubscribed()
    {
        $this->subscribed = true;
    }
    /**
     * @return void
     */
    //abstract public function processWebhook();

    /**
     * @param $dateStr
     * @return null
     */
    protected function getDateFromString($dateStr)
    {
        if(empty($dateStr))
        {
            return null;
        }
        $dateArr = explode('.', $dateStr);

        $tmstmp = strtotime($dateArr[0]);
        if($this->gmt > 0)
        {
            $tmstmp += 3600 * $this->gmt;
        }

        $date = \Bitrix\Main\Type\DateTime::createFromTimestamp($tmstmp);
        $this->isWinter($date);

        return $date;

    }

    /**
     * @param $yourdate
     * @return void
     */
    public function createLead()
    {
        $oLead = new \CCrmLead(false);
        $arFields = array(
            "TITLE" => $this->title,
            "SOURCE_ID" => $this->source,
            "STATUS_ID" => 'NEW',
            'ASSIGNED_BY_ID' => $this->assigned,
            $this->proplinkuf => $this->proplinkufval,
            $this->proprefuf => $this->proprefufval,
            "FM" => Array(
                'PHONE' => array(
                    'n0' => array(
                        'VALUE' => $this->phone,
                        'VALUE_TYPE' => 'WORK'
                    )
                ) ,
            ) ,
        );

        $newlead_id = $oLead->Add($arFields, true);
        if($newlead_id>0) {
            echo $newlead_id;
        }
    }

    /**
     * @param $yourdate
     * @return void
     */
    protected function isWinter(\Bitrix\Main\Type\DateTime $yourdate)
    {
        $day = $yourdate->format('d');
        $month = $yourdate->format('m');
        $year = $yourdate->format('Y');

        $cy =  date("Y");

        $winter = false;

        if(($day >= 25 && $month >= 10 && $year==$cy) || ($day <= 29 && $month <= 3 && $year<$cy)) {
            $winter = true;
        }

        if($winter) {
            $yourdate->modify( "-1 hour"); // 1 hour back
        }

    }

    /**
     * @param $method
     * @param $data
     * @return void
     */
    protected function sendCurlRequest(
        $method,
        $data = []
    )
    { }

    /**
     * logging. TODO: monologging
     * @param $data
     * @param $sHeader
     * @param $sFilePath
     * @return void
     */
    public static function dump($data, $sHeader = null, $sFilePath = "__mylog_integrations.log") {

        $sHeader = ($sHeader != null ? $sHeader . '   ' : '') . date('l jS \of F Y h:i:s A');

        \Bitrix\Main\Diag\Debug::dumpToFile($data  , $sHeader, $sFilePath  );
    }

    /**
     * @return string
     */
    protected static function translateToEnglish($str)
    {
        $tr = array(
            "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
            "Д"=>"D","Е"=>"E","Ж"=>"J","З"=>"Z","И"=>"I",
            "Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
            "О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
            "У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"TS","Ч"=>"CH",
            "Ш"=>"SH","Щ"=>"SCH","Ъ"=>"","Ы"=>"YI","Ь"=>"",
            "Э"=>"E","Ю"=>"YU","Я"=>"YA","а"=>"a","б"=>"b",
            "в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
            "з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
            "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
            "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
            "ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
            "ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya",
            "Ё"=>"E","Є"=>"E","Ї"=>"YI","ё"=>"e","є"=>"e","ї"=>"yi",
            " "=> "_", "/"=> "_"
        );
        if (preg_match('/[^A-Za-z0-9_\-]/', $str)) {
            $str = strtr($str,$tr);
            $str = preg_replace('/[^A-Za-z0-9_\-.]/', '', $str);
        }
        return $str;
    }

    /**
     * @return string
     */
    protected static function getModuleId()
    {
        return 'webmatrik.integrations';
    }

    /**
     * @param $optionName
     * @param $default
     * @return mixed
     */
    public static function getModuleOption($optionName, $default = null)
    {
        return Option::get(static::getModuleId(), $optionName);
    }

    /**
     * @param $optionName
     * @param $optionValue
     * @return mixed
     */
    public static function setModuleOption($optionName, $optionValue)
    {
        return Option::set(static::getModuleId(), $optionName, $optionValue);
    }

}
