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
  if (!window.Pusher) {
    console.warn('Pusher library is not available.');
    return;
  }
  var pusherKey = data && data.pusher ? data.pusher.key : null;
  if (!pusherKey) {
    console.warn('Pusher key is missing in /admin/pusher/get response.');
    return;
  }
  var pusherCluster = data && data.pusher && data.pusher.cluster ? data.pusher.cluster : 'us2';
  var pusher = new Pusher(pusherKey, {
    cluster: pusherCluster
  });
  //Canal de servicios
  var service_channel = pusher.subscribe('opzio-channel-chat');
  service_channel.bind('opzio-event-chat', function(data) {
    audio.play();
    appendChatMesssages([data.message]);
  });
}

