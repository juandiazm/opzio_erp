import { chatState } from './state.js';
import { getChatMessages } from './messages.js';

export function getChatMessageAction(){
    let chatId = $(this).attr('chat-id');
    $('.chat-conversations-item').removeClass('active');
    $(this).addClass('active');
    chatState.currentChat = chatState.chats.find(chat => chat.id == chatId);
    getChatMessages(chatId);
}

export function getClientChats(){
    chatState.isLoading = true;
    PostMethodFunction('/admin/chat/get-client-chats-page', {pagination: chatState.pagination}, null, showClientChats, function(){ chatState.isLoading = false; });
}

function showClientChats(response){
    chatState.isLoading = false;
    chatState.pagination = response.pagination;
    chatState.chats = response.data;
    let html = '';
    $.each(chatState.chats, function(index, chat){
        html += '<li class="chat-conversations-item" chat-id="'+chat.id+'"><div class="chat-main-info"><p class="chat-name">'+chat.chat_name+'</p><p class="chat-last-message">'+(chat.last_message == null ? '' : chat.last_message.message)+'</p><p class="chat-date-for-humans">'+chat.updated_at_for_humans+'</p><p class="chat-date">'+chat.updated_at_string+'</p><div class="form-check form-switch"><input class="ia-response-input form-check-input" type="checkbox" role="switch" id="flexSwitchCheckDefault"'+(chat.ia_response == 1 ? 'checked' : '')+'><label class="form-check-label" for="flexSwitchCheckDefault">Respuesta por IA</label></div></div></li>';
    });
    $('.chat-conversations-list').append(html);
}

export function getChatMessagesNewPage(){
    if(chatState.pagination.totalPages == undefined || chatState.pagination.page >= chatState.pagination.totalPages) return;
    chatState.pagination.page++;
    getClientChats();
}