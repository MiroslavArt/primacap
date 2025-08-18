<?php

namespace B24help\App\Robots;

use B24help\App\B24Activity;

/**
 * Активити "Отправить сообщение в чат"
 * Отправляет в заданный групповой чат определенное сообщение.
 */
class SendToChat extends B24Activity
{
    const ChatID = 'ChatID';
    const message = 'message';
    const outputmessage = 'outputmessage';

    protected function getSelfFile()
    {
        return __FILE__;
    }

    const MESS = [
        'ru' => [
            'RESULT' => 'Сообщение отправлено ',
            'ERROR' => 'Сообщение не отправлено',
        ],
        'en' => [
            'RESULT' => 'Message is sent',
            'ERROR' => 'Message is sent',
        ],
    ];

    protected function execute()
    {
        if (isset($this->arRequest['workflow_id'])) {
            $this->lang = 'en' == $this->lang ? 'en' : 'ru';

            $result = $this->obB24App->call('im.message.add', [
                'CHAT_ID' => $this->arRequest['properties'][static::ChatID],
                'MESSAGE' => $this->arRequest['properties'][static::message],
                'SYSTEM' => 'N', // Отображать сообщения в виде системного сообщения или нет, необязательное поле, по умолчанию 'N'
                'ATTACH' => '', // Вложение, необязательное поле
                'URL_PREVIEW' => 'Y', // Преобразовывать ссылки в rich-ссылки, необязательное поле, по умолчанию 'Y'
                'KEYBOARD' => '', // Клавиатура, необязательное поле
                'MENU' => '', // Контекстное меню, необязательное поле
            ]);

            if (!empty($result['result'])) {
                $this->sendBizprocResult(static::MESS[$this->lang]['RESULT'], [static::outputmessage => $this->arRequest['properties'][static::message]]);
            } else {
                $this->sendBizprocResult(static::MESS[$this->lang]['ERROR'], []);
            }
        }
    }
}
