import { chatState, initializeChatState } from './state.js';
import * as conversations from './conversations.js';
import * as messages from './messages.js';
import * as iaResponse from './ia-response.js';

export { appendChatMesssages } from './messages.js';

$(document).on('click', '.ia-response-input', iaResponse.changeIAResponse);

$(document).ready(function(){
    initializeChatState();
    if(!chatState.messageInput || !chatState.conversationListElement || !chatState.conversationsList) return;
    if(!window.readyExecuted){
        window.readyExecuted = true;
        chatState.messageInput.removeEventListener('keypress', messages.sendMessageAction);
        chatState.conversationListElement.addEventListener('scroll', conversations.getChatMessagesNewPage);
        $('.empty-view-chat').show();
        $('.loading-view-chat').hide();
        $('.chat-message-sub-container').hide();
        conversations.getClientChats();
        if(!chatState.messageInput.hasEventListener){
            chatState.messageInput.addEventListener('keypress', messages.sendMessageAction);
            chatState.messageInput.hasEventListener = true;
        }
        if(!chatState.conversationsList.hasEventListener){
            $(document).on('click', '.chat-conversations-item', conversations.getChatMessageAction);
            chatState.conversationsList.hasEventListener = true;
        }
    }
});