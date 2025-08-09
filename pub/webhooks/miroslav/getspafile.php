<?php
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);
define("NO_AGENT_CHECK", true);
define("HOST", 'metrirent.bitrix24.ru');
define("USER", '16258');
define("TOKENID", '61z0960j5rbewckz');

global $APPLICATION;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$params = http_build_query(array(
        'halt' => 0,
        'cmd' => array(
            'get_spa' => 'crm.item.list?'
                . http_build_query(array(
                    'entityTypeId' => 1032,
                    'select' => [
                        "id",
                        "title",
                        "uf_*",
                    ],
                    'filter' => array(
                        'ID' => 580
                    ),
                ))
        )
    )
);

$out = opt($params, HOST, USER, TOKENID);

$res = $out['result']['result']['get_spa'];

echo "<pre>";
print_r($res);
echo "</pre>";

$fileUrl = current($res['items'][0]['ufCrm8_1754670291']);

$headers = get_headers($fileUrl['urlMachine'], true);
if (isset($headers['Content-Disposition'])) {
    if (preg_match('/filename="([^"]+)"/i', $headers['Content-Disposition'], $matches)) {
        $fileName = $matches[1];
    }
}

// Путь для сохранения на сервере
$savePath = __DIR__ . '/spafile/' . $fileName; // сохраняем в папку downloads

// Проверяем, существует ли папка, если нет - создаем
if (!file_exists(dirname($savePath))) {
    mkdir(dirname($savePath), 0644, true);
}

// Получаем содержимое файла
$fileContent = file_get_contents($fileUrl['urlMachine']);

if ($fileContent !== false) {
    // Сохраняем файл
    file_put_contents($savePath, $fileContent);
    $file = \CFile::MakeFileArray(
        $savePath,
        false,
        false,
        ''
    );
    $fileSave = \CFile::SaveFile(
        $file,
        '/images',
        false,
        false
    );
    echo "<pre>";
    echo "Файл {$fileName} успешно сохранен в {$savePath} c id {$fileSave}";
    echo "</pre>";
    if (unlink($savePath)) {
        echo " Файл удалён.";
    } else {
        echo " Ошибка удаления!";
    }
} else {
    echo "<pre>";
    echo "Не удалось загрузить файл";
    echo "</pre>";
}

function opt($appParams, $domain, $user, $auth)
{
    $appRequestUrl = 'https://'.$domain.'/rest/'.$user.'/'.$auth.'/batch';
    $curl=curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $appRequestUrl,
        CURLOPT_POSTFIELDS => $appParams
    ));
    $out=curl_exec($curl);



    return json_decode($out, 1);
}