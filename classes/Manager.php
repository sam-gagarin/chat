<?php

namespace App\Chat;

use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
use Bitrix\Main\Loader;
use App\Chat\Entity\Dialog;
use App\Chat\Entity\Message;

Loader::includeModule('highloadblock');

class Manager {

    /**
     * Кеш DataClass для HL-блоков
     * [
     *   'dialogs' => DialogTable::class,
     *   'messages' => MessageTable::class,
     * ]
     */
    private static array $entityCache = [];
    
    /**
     * Возвращает DataClass HL-блока с кешированием
     */
    protected static function getEntityDataClass(string $tableName): string
    {
        if (isset(self::$entityCache[$tableName])) {
            return self::$entityCache[$tableName];
        }

        $hlblock = HL\HighloadBlockTable::getList([
            'filter' => ['=TABLE_NAME' => $tableName]
        ])->fetch();

        if (!$hlblock) {
            throw new \RuntimeException("HL block {$tableName} not found");
        }

        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $dataClass = $entity->getDataClass();

        self::$entityCache[$tableName] = $dataClass;

        return $dataClass;
    }

    /**
     * Проверяет, имеет ли пользователь доступ к этому диалогу
     */
    public static function canUserAccessDialog($dialogId, $userId) {
        $dataClass = self::getEntityDataClass(CHAT_DIALOGS_HL_ID);
        $res = $dataClass::getList([
            'select' => ['ID'],
            'filter' => [
                'ID' => $dialogId,
                [
                    'LOGIC' => 'OR',
                    ['UF_USER_1' => $userId],
                    ['UF_USER_2' => $userId],
                ],
            ],
            'limit' => 1
        ]);
        
        return (bool)$res->fetch();
    }

    /**
     * Получаем список чатов текущего пользователя
     */
    public static function getUserDialogs($userId) {
        $dataClass = self::getEntityDataClass(CHAT_DIALOGS_HL_ID);
        
        $res = $dataClass::getList([
            'filter' => [
                [
                    'LOGIC' => 'OR',
                    ['UF_USER_1' => $userId],
                    ['UF_USER_2' => $userId],
                ],
            ],
            'order' => ['UF_LAST_MESSAGE_DATE' => 'DESC']
        ]);

        $dialogs = [];
        while ($row = $res->fetch()) {
            $dialogs[] = new Dialog($row, $userId);
        }
        return $dialogs;
    }

    public static function getDialogMessages(int $dialogId, int $currentUserId): array {
        $dataClass = self::getEntityDataClass(CHAT_MESSAGES_HL_ID);
        $res = $dataClass::getList([
            'filter' => ['UF_DIALOG_ID' => $dialogId],
            'order'  => ['ID' => 'ASC'],
            'limit'  => 50,
        ]);

        $messages = [];
        while ($row = $res->fetch()) {
            $messages[] = new Message($row, $currentUserId);
        }
        return $messages;
    }

    public static function getMessageById(int $id, int $currentUserId): ?Message {
        $dataClass = self::getEntityDataClass(CHAT_MESSAGES_HL_ID);
        $row = $dataClass::getList([
            'filter' => ['ID' => $id],
            'limit'  => 1,
        ])->fetch();

        return $row ? new Message($row, $currentUserId) : null;
    }

    /**
     * Отправка сообщения (MVP)
     */
    public static function sendMessage($dialogId, $authorId, $text) {
        $dataClass = self::getEntityDataClass(CHAT_MESSAGES_HL_ID);
        
        $result = $dataClass::add([
            'UF_DIALOG_ID' => $dialogId,
            'UF_AUTHOR_ID' => $authorId,
            'UF_MESSAGE'   => $text,
            'UF_DATE_CREATE' => new \Bitrix\Main\Type\DateTime(),
            'UF_IS_READ'   => false
        ]);

        if ($result->isSuccess()) {
            // Обновляем дату в диалоге для сортировки
            self::updateDialogDate($dialogId);
            return $result->getId();
        }
        return false;
    }
    

    private static function updateDialogDate($dialogId) {
        $dataClass = self::getEntityDataClass(CHAT_DIALOGS_HL_ID);
        $dataClass::update($dialogId, [
            'UF_LAST_MESSAGE_DATE' => new \Bitrix\Main\Type\DateTime()
        ]);
    }
}