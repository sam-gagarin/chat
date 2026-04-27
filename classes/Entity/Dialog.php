<?php

namespace App\Chat\Entity;

use Bitrix\Main\Type\DateTime;

/**
 * Диалог между двумя пользователями по конкретному объявлению.
 * Маппинг: HLBLOCK_14
 *   ID, UF_USER_1, UF_USER_2, UF_ITEM_ID, UF_LAST_MESSAGE_DATE
 */
class Dialog {
    public readonly int      $id;
    public readonly int      $itemId;
    public readonly int      $user1Id;
    public readonly int      $user2Id;
    public readonly int      $opponentId;
    public readonly ?DateTime $lastMessageDate;

    public function __construct(array $data, int $currentUserId) {
        $this->id              = (int)$data['ID'];
        $this->itemId          = (int)$data['UF_ITEM_ID'];
        $this->user1Id         = (int)$data['UF_USER_1'];
        $this->user2Id         = (int)$data['UF_USER_2'];
        $this->opponentId      = ($this->user1Id === $currentUserId)
                                    ? $this->user2Id
                                    : $this->user1Id;
        $this->lastMessageDate = $data['UF_LAST_MESSAGE_DATE'] instanceof DateTime
                                    ? $data['UF_LAST_MESSAGE_DATE']
                                    : null;
    }
}