import { chatState } from './state.js';

export function sendMessageAction(event){
    if(event.key === 'Enter'){
        let message = $(this).val();
        if(message.trim() != '') sendMessage(message);
    }
}

export function getChatMessages(chatId){
    $('.empty-view-chat').hide();
    $('.chat-message-sub-container').hide();
    $('.loading-view-chat').show();
    PostMethodFunction('/admin/chat/get-chat-messages', {chat_id: chatId}, null, showChatMessages, null);
}

function showChatMessages(response){
    $('.empty-view-chat').hide();
    $('.loading-view-chat').hide();
    $('.chat-message-sub-container').show();
    chatState.messages = response.data;
    $('.chat-messages-list').empty();
    appendChatMesssages(chatState.messages);
}

export function appendChatMesssages(messages){
    if(chatState.currentChat == null) return;
    let html = '';
    $.each(messages, function(index, message){
        if(chatState.currentChat.id == message.client_chat_id){
            html += '<li class="chat-messages-item '+(message.is_admin == 1 ? 'admin' : 'client')+'" message-id="'+message.id+'"><p class="chat-message-content">'+message.message+'</p><p class="chat-message-date">'+message.created_at_string+'</p></li>';
        }
    });
    $('.chat-messages-list').append(html);
    let chat = chatState.chats.find(chat => chat.id == chatState.currentChat.id);
    if(chat && messages.length > 0){
        chat.last_message = messages[messages.length - 1];
        $('.chat-conversations-item.active .chat-last-message').text(messages[messages.length - 1].message);
        $('.chat-conversations-item.active .chat-date-for-humans').text(messages[messages.length - 1].created_at_for_humans);
        $('.chat-conversations-item.active .chat-date').text(messages[messages.length - 1].created_at_string);
    }
    $('.chat-messages-list').scrollTop($('.chat-messages-list')[0].scrollHeight);
    $('#chat-message-input').select().focus();
}

function sendMessage(message){
    PostMethodFunction('/admin/chat/send-message', {chat_id: chatState.currentChat.id, message: message}, null, function(response){
        chatState.messages.push(response.data);
        appendChatMesssages([response.data]);
    }, null);
    setTimeout(() => { $('#chat-message-input').val('').select().focus(); }, 1);
}

export function changeIAResponse(event){
    event.stopPropagation();
    let iaResponse = $(this).prop('checked') ? 1 : 0;
    PostMethodFunction('/admin/chat/change-ia-response', {chat_id: chatState.currentChat.id, ia_response: iaResponse}, null, function(){ chatState.currentChat.ia_response = iaResponse; }, null);
}