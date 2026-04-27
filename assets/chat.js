class BitrixChat {
    constructor(options) {
        this.apiId = options.apiId || '/local/php_interface/chat/api.php';
        this.mainArea = document.getElementById('chat-main-area');
        this.sidebar = document.getElementById('chat-sidebar');
        this.currentDialogId = null;
        this.pollingInterval = null;
        
        this.init();
    }

    init() {
        // Первичная загрузка списка чатов
        this.loadSidebar();

        // Вешаем один обработчик на контейнер (делегирование событий)
        document.getElementById('chat-app').addEventListener('click', (e) => {
            const dialogItem = e.target.closest('.dialog-item');
            if (dialogItem) {
                this.openDialog(dialogItem.dataset.id);
            }
        });
    }

    // Загрузка правой колонки
    async loadSidebar() {
        const response = await fetch(`${this.apiId}?action=get_sidebar`);
        this.sidebar.innerHTML = await response.text();
    }

    // Открытие конкретного чата
    async openDialog(id) {
        if (this.currentDialogId === id) return;
        
        this.currentDialogId = id;
        this.stopPolling(); // Сбрасываем старый таймер, если был

        const response = await fetch(`${this.apiId}?action=get_dialog&id=${id}`);
        this.mainArea.innerHTML = await response.text();
        
        this.scrollToBottom();
        this.startPolling();
        this.initForm(); // Инициализируем обработку отправки
    }

    initForm() {
        const form = document.getElementById('chat-form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = form.querySelector('textarea');
            const text = input.value.trim();
            
            if (!text) return;

            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('id', this.currentDialogId);
            formData.append('text', text);
            formData.append('sessid', BX.bitrix_sessid()); // Защита Битрикса

            input.value = ''; // Очищаем поле сразу для UX

            const response = await fetch(this.apiId, {
                method: 'POST',
                body: formData
            });

            const html = await response.text();
            this.appendMessage(html);
        });
    }

    appendMessage(html) {
        const list = document.querySelector('.chat-messages-list');
        if (list) {
            list.insertAdjacentHTML('beforeend', html);
            this.scrollToBottom();
        }
    }

    startPolling() {
        // Для MVP просто запрашиваем всё окно чата раз в 3 секунды
        // В идеале — запрашивать только новые сообщения по last_id
        this.pollingInterval = setInterval(async () => {
            const response = await fetch(`${this.apiId}?action=get_dialog&id=${this.currentDialogId}`);
            const html = await response.text();
            
            // Умное обновление: сравниваем длину или содержимое, чтобы не "мигало"
            if (this.mainArea.innerHTML.length !== html.length) {
                this.mainArea.innerHTML = html;
                this.initForm(); // Перевешиваем события на форму
                this.scrollToBottom();
            }
        }, 3000);
    }

    stopPolling() {
        if (this.pollingInterval) clearInterval(this.pollingInterval);
    }

    scrollToBottom() {
        const list = document.querySelector('.chat-messages-list');
        if (list) list.scrollTop = list.scrollHeight;
    }
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', () => {
    window.AppChat = new BitrixChat({});
});