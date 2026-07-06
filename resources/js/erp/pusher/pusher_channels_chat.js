import { appendChatMesssages } from '../chat/chat.js';
// Enable pusher logging - don't include this in production
//Pusher.logToConsole = true;
var audio = new Audio('/sounds/whatsapp-apple.mp3');
$(document).ready(function(){
  Service_getPusherData();
  /////////////////////
});
function Service_getPusherData(){
  var DataSend ={};
	PostMethodFunction('/admin/pusher/get', DataSend, null, Service_usePusherData, null);
}
function Service_usePusherData(data){
  var pusher = new Pusher(data.pusher.key, {
    cluster: 'us2'
  });
  //Canal de servicios
  var service_channel = pusher.subscribe('ridder-channel-chat');
  service_channel.bind('ridder-event-chat', function(data) {
    audio.play();
    appendChatMesssages([data.message]);
  });
}

