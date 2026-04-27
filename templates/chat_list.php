<?php
 
/** @var array $arResult */
/** @var \App\Chat\Entity\Dialog $dialog */
?>
 
<?php if (empty($arResult['DIALOGS'])): ?>
    <div style="padding: 20px; color: #999;">У вас еще нет чатов</div>
<?php else: ?>
    <?php foreach ($arResult['DIALOGS'] as $dialog): ?>
        <div class="dialog-item" 
             data-id="<?=htmlspecialcharsbx($dialog->id)?>" 
             style="border-bottom: 1px solid #eee; padding: 10px; cursor: pointer;">
            <strong>Собеседник: <?=htmlspecialcharsbx($dialog->opponentId)?></strong><br>
            <small>Объявление: <?=htmlspecialcharsbx($dialog->itemId)?></small><br>
            <span style="font-size: 10px; color: #ccc;">
                <?=htmlspecialcharsbx($dialog->lastMessageDate instanceof \Bitrix\Main\Type\DateTime ? $dialog->lastMessageDate->toString() : '')?>
            </span>
        </div>
    <?php endforeach; ?>
<?php endif; ?>