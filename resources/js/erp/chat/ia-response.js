import { chatState } from './state.js';

export function changeIAResponse(event){
    event.stopPropagation();
    let iaResponse = $(event.currentTarget).prop('checked') ? 1 : 0;
    PostMethodFunction('/admin/chat/change-ia-response', {chat_id: chatState.currentChat.id, ia_response: iaResponse}, null, function(){
        chatState.currentChat.ia_response = iaResponse;
    }, null);
}