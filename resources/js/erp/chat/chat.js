var chat_message_input = document.getElementById('chat-message-input');
var chat_conversations_list = document.getElementsByClassName('chat-conversations-list')[0];
var chatConvesationListElement = document.getElementById('chat-conversations-list'); // Replace with your element's ID
function getChatMessageAction(event){
    let chat_id = $(this).attr('chat-id');
    $('.chat-conversations-item').removeClass('active');
    $(this).addClass('active');
    current_chat = chats.find(chat => chat.id == chat_id);
    getChatMessages(chat_id);
}
// Define the event handler function
function sendMessageAction(event) {
    if (event.key === 'Enter') {
        let message = $(this).val();
        if(message.trim() != ''){
            sendMessage(message);
        }
    }
}
// Add the event listener


$(document).on('click', '.ia-response-input', changeIAResponse);
///////////////////////////////////
var chats = [];
var chat_messages = [];
var current_chat = null;
var isLoading = false;
var db_pagination = {
    page:1,
    per_page:10,
    total:0,
};
$(document).ready(function(){
    if (!chat_message_input || !chatConvesationListElement || !chat_conversations_list) {
        return;
    }
    if (!window.readyExecuted) {
        window.readyExecuted = true;
        chat_message_input.removeEventListener('keypress', sendMessageAction);
        chatConvesationListElement.addEventListener('scroll', function() {
            let scroll = Math.floor(chatConvesationListElement.scrollHeight - chatConvesationListElement.scrollTop);
            if (scroll <= chatConvesationListElement.clientHeight) {
                getChatMessagesNewPage();
            }
        });
        $('.empty-view-chat').show();
        $('.loading-view-chat').hide();
        $('.chat-message-sub-container').hide();
        getClientChats();
        if(!chat_message_input.hasEventListener){
            chat_message_input.addEventListener('keypress', sendMessageAction);
            chat_message_input.hasEventListener = true;
        }
        if(!chat_conversations_list.hasEventListener){
            $(document).on('click', '.chat-conversations-item', getChatMessageAction);
            chat_conversations_list.hasEventListener = true;
        }
    }
});
function getClientChats(){
    isLoading = true;
    let DataSend = {
        pagination:db_pagination
    };
    PostMethodFunction('/admin/chat/get-client-chats-page',DataSend,null, showClientChats, function(){isLoading = false;});
}
function showClientChats(response){
    isLoading = false;
    db_pagination = response.pagination;
    chats = response.data;
    let html = '';
    $.each(chats, function(index, chat){
        html += '<li class="chat-conversations-item" chat-id="'+chat.id+'">';
            html += '<div class="chat-main-info">';
                html += '<p class="chat-name">'+chat.chat_name+'</p>';
                html += '<p class="chat-last-message">'+(chat.last_message==null?'':chat.last_message.message)+'</p>';
                html += '<p class="chat-date-for-humans">'+chat.updated_at_for_humans+'</p>';
                html += '<p class="chat-date">'+chat.updated_at_string+'</p>';
                html += '<div class="form-check form-switch">';
                    html += '<input class="ia-response-input form-check-input" type="checkbox" role="switch" id="flexSwitchCheckDefault"'+(chat.ia_response==1?'checked':'')+'>';
                    html += '<label class="form-check-label" for="flexSwitchCheckDefault">Respuesta por IA</label>';
                html += '</div>';
            html += '</div>';
        html += '</li>';
    });
    $('.chat-conversations-list').append(html);
}

function getChatMessages(chat_id){
    $('.empty-view-chat').hide();
    $('.chat-message-sub-container').hide();
    $('.loading-view-chat').show();
    let DataSend = {chat_id: chat_id};
    PostMethodFunction('/admin/chat/get-chat-messages',DataSend,null, showChatMessages, null);
}
function showChatMessages(response){
    $('.empty-view-chat').hide();
    $('.loading-view-chat').hide();
    $('.chat-message-sub-container').show();
    chat_messages = response.data;
    $('.chat-messages-list').empty();
    appendChatMesssages(chat_messages);
}
export function appendChatMesssages(messages){
    if(current_chat == null) return;
    let html = '';
    $.each(messages, function(index, message){
        if(current_chat.id == message.client_chat_id){
            html += '<li class="chat-messages-item '+((message.is_admin==1)?'admin':'client')+'" message-id="'+message.id+'">';
                html += '<p class="chat-message-content">'+message.message+'</p>';
                html += '<p class="chat-message-date">'+message.created_at_string+'</p>';
            html += '</li>';
        }
    });
    $('.chat-messages-list').append(html);
    /*change chat last message on view*/
    let chat = chats.find(chat => chat.id == current_chat.id);
    chat.last_message = messages[messages.length-1];
    $('.chat-conversations-item.active .chat-last-message').text(messages[messages.length-1].message);
    $('.chat-conversations-item.active .chat-date-for-humans').text(messages[messages.length-1].created_at_for_humans);
    $('.chat-conversations-item.active .chat-date').text(messages[messages.length-1].created_at_string);
    /*scroll to last message*/
    $('.chat-messages-list').scrollTop($('.chat-messages-list')[0].scrollHeight);
    $('#chat-message-input').select().focus();
}
function sendMessage(message){
    let DataSend = {chat_id: current_chat.id, message: message};
    setTimeout(() => {
        $('#chat-message-input').val('').select().focus(); //clear the input
    }, 1);
    PostMethodFunction('/admin/chat/send-message',DataSend,null, function(response){
        chat_messages.push(response.data);
        appendChatMesssages([response.data]);
        
    }, null);
}
function changeIAResponse(e){
    e.stopPropagation();
    let chat_id = current_chat.id;
    let ia_response = $(this).prop('checked')?1:0;
    let DataSend = {chat_id: chat_id, ia_response: ia_response};
    PostMethodFunction('/admin/chat/change-ia-response',DataSend,null, function(response){
        current_chat.ia_response = ia_response;
    }, null);
}

function getChatMessagesNewPage() {
    if(db_pagination.totalPages == undefined || db_pagination.page >= db_pagination.totalPages) return;
    db_pagination.page++;
    getClientChats();
}