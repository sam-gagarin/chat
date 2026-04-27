<?php

namespace App\Chat;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use App\Chat\Manager;

class Controller {
    protected $request;
    protected $userId;

    public function __construct() {
        $this->request = Context::getCurrent()->getRequest();
        $this->userId = $GLOBALS['USER']->GetID();
    }

    /**
     * Точка входа для всех fetch-запросов
     */
    public function execute() {
        if (!$this->userId) {
            return $this->sendJson(['error' => 'Authorize required'], 403);
        }
        if ($this->request->isPost() && !check_bitrix_sessid()) {
            return $this->sendJson(['error' => 'Invalid session'], 403);
        }

        $action = $this->request->get('action');

        switch ($action) {
            case 'get_sidebar': // Получить список чатов
                return $this->getSidebarAction();

            case 'get_dialog': // Получить окно конкретного чата
                return $this->getDialogAction();

            case 'send': // Отправить сообщение
                return $this->sendMessageAction();

            case 'poll': // Short-polling новых сообщений
                return $this->pollAction();

            default:
                return $this->sendJson(['error' => 'Unknown action'], 400);
        }
    }

    private function getSidebarAction() {
        $dialogs = Manager::getUserDialogs($this->userId);
        return $this->render('chat_list', ['DIALOGS' => $dialogs]);
    }

    private function getDialogAction() {
        $dialogId = (int)$this->request->get('id');
        
        if ($dialogId > 0) {
            if (!Manager::canUserAccessDialog($dialogId, $this->userId)) {
                return $this->sendJson(['error' => 'Access denied'], 403);
            }
            
            $messages = Manager::getDialogMessages($dialogId, $this->userId);
            return $this->render('chat_view', [
                'MESSAGES' => $messages, 
                'DIALOG_ID' => $dialogId
            ]);
        }
        return $this->render('main_empty');
    }

    private function sendMessageAction() {
        $dialogId = (int)$this->request->get('id');
        if (!Manager::canUserAccessDialog($dialogId, $this->userId)) {
            return $this->sendJson(['error' => 'Access denied'], 403);
        }
        $text = trim($this->request->get('text'));

        if ($text === '' || mb_strlen($text) > 5000) {
            return $this->sendJson(['error' => 'Invalid message'], 400);
        }

        if ($id = Manager::sendMessage($dialogId, $this->userId, $text)) {
            // Возвращаем отрендеренное одно сообщение, чтобы JS сразу добавил его в чат
            return $this->render('message', ['MSG' => Manager::getMessageById($id, $this->userId)]);
        }
    }

    /**
     * Рендерит PHP-шаблон и возвращает строку
     */
    private function render($templateName, $arResult = []) {
        $path = $_SERVER['DOCUMENT_ROOT'] . "/local/php_interface/chat/templates/{$templateName}.php";
        if (file_exists($path)) {
            ob_start();
            include $path;
            return ob_get_clean();
        }
        return "Template {$templateName} not found";
    }

    private function sendJson($data, $code = 200) {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode($data);
        die();
    }
}