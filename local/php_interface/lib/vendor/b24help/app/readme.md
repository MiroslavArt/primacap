# B24help App

Вспомогательная библиотека классов для быстрой разработки приложений Bitrix24 - обработчиков веб-хуков, активити и роботов бизнес-процессов, интеграционных решений, построителей отчетов и т.п.

Базируется на библиотеке `mesilov/bitrix24-php-sdk`.

## Установка пакета

Добавьте `"b24help/app": "dev-master"` в `composer.json` вашего приложения. Или клонируйте репозитарий в ваш проект.

## Класс B24Activity

Класс `B24Activity` и пример реализации `Activities/GetTask` - упрощенный подход **без сохранения авторизации** и автоматической установкой REST-действия в Битрикс24.

### Пример подключения готового активити `Activities/GetTask`

```php
<?
require_once $_SERVER['DOCUMENT_ROOT'].'/local/php_interface/lib/vendor/autoload.php';

\B24help\App\Activities\GetTask::run(
	__FILE__,
	'-',
	'-',
	\Monolog\Logger::INFO,
	"https://__app_host_domain__/some_path/get_task.php"
);

```

Заменить в ссылке **domain** на хост портала.

Установить приложение с разрешениями "Бизнес-процесс", "Задачи" и "Хранилища данных".

**Обязательно** указать ссылку на установщик: `https://__app_host_domain__/some_path/get_task.php?install=Y`.

## Класс B24Application

Поддерживает установку по полному сценарию oAuth и сохранением авторизации,
что позволяет применять его в бэкенд приложениях, выполняемых без участия авторизованного
на портале пользователя.

### Пример кода приложения

Пример активити для получения названия компании.

```php
<?
use B24help\App\B24Application;

class ContactActivity extends B24Application
{
  protected function afterInstall()
  {
    $this->addBizprocActivity(json_decode(file_get_contents(__DIR__.'/activity.json'), true));
  }

  protected function execute()
  {
    try
    {
      $companyID = $_REQUEST['properties']['InputCompanyID'];
      if (intval($companyID) > 0)
      {
        $result = $this->obB24App->call('crm.company.get', array('id' => $companyID));
        $arCompany = $result['result'];
        $this->sendBizprocResult('Данные компании #' . $companyID, ['OutputName' => $arCompany['TITLE']]);
      }
      else
      {
        $this->sendBizprocResult('Компания в сделке не определена',['OutputName' => '',]);
      }
    }
    catch (\B24help\App\B24Exception $e)
    {
      $this->log->error($e->getMessage());
    }
    catch (Bitrix24\Exceptions\Bitrix24ApiException $e)
    {
      $this->log->error('B24 API error' . $e->getMessage());
    }
  }
}

ContactActivity::run("crm,bizproc", \Monolog\Logger::INFO, __DIR__);
```

Конфигурационный файл активити

```json
{
    "CODE": "companyactivity",
    "HANDLER": "https://домен_хостинга_приложения/папка_приложения/app.php",
    "AUTH_USER_ID": 1,
    "USE_SUBSCRIPTION": "Y",
    "NAME": {
        "ru": "Загрузить данные компании по ID",
        "en": "Load company fields by ID"
    },
    "DESCRIPTION": {
        "ru": "Действие загружает массив полей компании",
        "en": "Load company (CRM)"
    },
    "PROPERTIES": {
        "InputCompanyID": {
            "Name": {
                "ru": "Компания (ID)",
                "en": "Компания ID"
            },
            "Description": {
                "ru": "Укажите ID компани",
                "en": "Input a company ID"
            },
            "Type": "int",
            "Required": "Y",
            "Multiple": "N",
            "Default": "{=Document:COMPANY_ID}"
        }
    },
    "RETURN_PROPERTIES": {
        "OutputName": {
            "Name": {
                "ru": "Имя",
                "en": "Name"
            },
            "Type": "string",
            "Multiple": "N",
            "Default": ""
        }
    }
}
```

### Авторизация приложения

Для авторизации приложения в Битрикс24 перейдите в браузере по ссылке следующего вида
https://домен_хостинга_приложения/папка_приложения/app.php?b24action=install

## Лицензия

`b24help/app` is licensed under the MIT License

## Автор

Владимир Тыртов - [info@b24.help](mailto:info@b24.help) - https://www.b24.help

## Требуется разработка приложения или интеграции для Bitrix24?

Пишите: [info@b24.help](mailto:info@b24.help)
