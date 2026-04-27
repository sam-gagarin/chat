<div class="msg-unit <?= $arResult['MSG']->isOwn ? 'msg-own' : '' ?>" data-message-id="<?= $arResult['MSG']->id ?>">>
    <?= htmlspecialcharsbx($arResult['MSG']->text) ?>
</div>