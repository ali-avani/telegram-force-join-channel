<?php

$token = "BOT TOKEN";
$channel_id = "CHANNEL TO JOIN WITHOUT @";
$force_join_text = "TEXT IF NOT JOINED";
$final_text = "AFTER JOINED TEXT";
$callback_text = "TEXT ON CALLBACK ALERT";


$path = "https://api.telegram.org/bot" . $token . "/";

$update = json_decode(file_get_contents("php://input"), TRUE);
$callback_query = $update['callback_query'];
$is_callback = $callback_query !== NULL;

if ($is_callback) {
    $update = $callback_query;
}
$chatId = $update["message"]["chat"]["id"];
$message = $update["message"]["text"];

function getMessageUrl($type, $chatId, $message, $buttons = NULL) {
    if (isset($buttons)) {
        $inline_keyboard = array(
            "inline_keyboard" => $buttons,
        );
        $keyboard = json_encode($inline_keyboard, true);
    }
    $url = $GLOBALS["path"] . $type . "?chat_id=" . $chatId . "&text=" . urlencode($message) . "&reply_markup=" . $keyboard;
    return $url;
}

function sendMessage($chatId, $message, $buttons = NULL) {
    $url = getMessageUrl("sendMessage", $chatId, $message, $buttons);
    file_get_contents($url);
}

function editMessage($chatId, $messageId, $message, $buttons = NULL) {
    $url = getMessageUrl("editMessageText", $chatId, $message, $buttons) . "&message_id=" . $messageId;
    file_get_contents($url);
}

function deleteMessage($chatId, $messageId) {
    $url = $GLOBALS["path"] . "deleteMessage?chat_id=" . $chatId . "&message_id=" . $messageId;
    file_get_contents($url);
}

function isMember($chatId) {
    $channel_id = $GLOBALS["channel_id"];
    $url = $GLOBALS["path"] . "getChatMember?chat_id=@$channel_id&user_id=$chatId";
    $status = json_decode(file_get_contents($url), true)["result"]["status"];
    return $status != "left" && $status != "kicked";
}

if (!$is_callback) {
    if (strpos($message, "/start") === 0) {
        if (isMember($chatId)) {
            sendMessage($chatId, $final_text);
        } else {
            sendMessage($chatId, $force_join_text, array(
                array(
                    array(
                        "text" => "عضویت در کانال",
                        "url" => "https://t.me/$channel_id",
                    )
                ),
                array(
                    array(
                        "text" => "بررسی عضویت",
                        "callback_data" => "review",
                    )
                ),
            ));
        }
    } else {
        sendMessage($chatId, "دستور نامعتبر است.");
    }
} else {
    $data = $update["data"];
    if ($data == "review") {
        if (isMember($chatId)) {
            editMessage($chatId, $update["message"]["message_id"], $final_text);
        } else {
            $url = $GLOBALS["path"] . "answerCallbackQuery?callback_query_id=" . $update["id"] . "&text=" . urlencode($callback_text);
            file_get_contents($url);
        }
    }
}
