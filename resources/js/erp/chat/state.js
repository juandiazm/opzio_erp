export const chatState = {
    messageInput: null,
    conversationsList: null,
    conversationListElement: null,
    chats: [],
    messages: [],
    currentChat: null,
    isLoading: false,
    pagination: {
        page: 1,
        per_page: 10,
        total: 0,
    },
};

export function initializeChatState(){
    chatState.messageInput = document.getElementById('chat-message-input');
    chatState.conversationsList = document.getElementsByClassName('chat-conversations-list')[0];
    chatState.conversationListElement = document.getElementById('chat-conversations-list');
}