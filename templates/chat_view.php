<div class="chat-messages-list">
    <?php foreach ($arResult['MESSAGES'] as $msg): /** @var \App\Chat\Entity\Message $msg */ ?>
        <div class="msg-unit <?= $msg->isOwn ? 'msg-own' : '' ?>">
            <?= htmlspecialcharsbx($msg->text) ?>
        </div>
    <?php endforeach; ?>
</div>
 
<div class="chat-footer-form">
    <form id="chat-form">
        <textarea name="text" placeholder="Введите сообщение..." required></textarea>
        <button type="submit">Отправить</button>
    </form>
</div>