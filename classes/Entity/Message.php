<?php

namespace App\Chat\Entity;

use Bitrix\Main\Type\DateTime;

/**
 * Одно сообщение в диалоге.
 * Маппинг: HLBLOCK_15
 *   ID, UF_DIALOG_ID, UF_AUTHOR_ID, UF_MESSAGE, UF_IS_READ, UF_DATE_CREATE
 */
class Message {
    public readonly int      $id;
    public readonly int      $dialogId;
    public readonly int      $authorId;
    public readonly string   $text;
    public readonly bool     $isRead;
    public readonly bool     $isOwn;
    public readonly ?DateTime $dateCreate;

    public function __construct(array $data, int $currentUserId) {
        $this->id         = (int)$data['ID'];
        $this->dialogId   = (int)$data['UF_DIALOG_ID'];
        $this->authorId   = (int)$data['UF_AUTHOR_ID'];
        $this->text       = (string)$data['UF_MESSAGE'];
        $this->isRead     = (bool)$data['UF_IS_READ'];
        $this->isOwn      = ($this->authorId === $currentUserId);
        $this->dateCreate = $data['UF_DATE_CREATE'] instanceof DateTime
                                ? $data['UF_DATE_CREATE']
                                : null;
    }
}