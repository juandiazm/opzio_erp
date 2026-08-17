import { licenseState } from './state.js';

export function addnotification(){
    let container = $(this).parent().parent();
    let email = container.find('.notification-email').val();
    let phone = container.find('.notification-phone').val();
    let state = container.find('.notification-active').attr('value');
    let flag = true;
    if(email == null || email == "" || !validateEmail(email)){
        container.find('.notificatin-email').addClass('is-invalid');
        alertWarning('Debe ingresar el email del licencia');
        flag = false;
    }else{
        container.find('.notification-email').removeClass('is-invalid');
    }
    if(flag){
        $('#add-notification').attr('disabled',true);
        let dataSend = {
            license_id: licenseState.currentLicense.id,
            email: email,
            phone: phone,
            state: state,
        };
        PostMethodFunction('/admin/licenses/notifications/add',dataSend,null,function(response){
            $('#add-notification').attr('disabled', false);
            container.find('.notification-email').val('');
            container.find('.notification-phone').val('');
            container.find('.notification-active').attr('value', '1');
            container.find('.notification-active .toggle-value[value="1"]').click();
            swallMessage(
                'Exito'
                , 'Notificación agregada'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            getServiceNotifications();
        },null);
    }
}

export function getServiceNotifications(){
    let dataSend = {
        license_id: licenseState.currentLicense.id
    };
    PostMethodFunction('/admin/licenses/notifications/get',dataSend,null, showServiceNotifications,null);
}

function showServiceNotifications(response){
    let notifications = response.data;
    let appendContent = '';
    $.each(notifications,function(index,value){
        appendContent += '<tr notification-id='+value.id+' class="update-notification-row'+(value.deleted_at==null?'':' deleted')+'">';
            appendContent += '<td>';
                if(value.deleted_at == null){
                    if(index!=0){
                        appendContent += '<i class="d-block notification-position-up-buttons my-1 fa-solid fa-arrow-up"></i>';
                    }
                    if(index!=notifications.length-1){
                        appendContent += '<i class="d-block notification-position-down-buttons my-1 fa-solid fa-arrow-down"></i>';
                    }
                }
            appendContent += '</td>';
            appendContent += '<td class="columns-notification-email text-left"><input type="text" class="form-control align-self-center notification-email text-left" placeholder="license@gmail.com" value="'+value.email+'"></td>';
            appendContent += '<td class="columns-notification-phone text-left"><p><input type="number" class="form-control align-self-center notification-phone text-left" placeholder="573191425639" value="'+value.phone+'"></p></td>';
            appendContent += '<td class="columns-notification-state text-center active-col">';
                appendContent += '<div class="toggle-container row notification-active" value="'+value.active+'">';
                    appendContent += '<div class="toggle-value d-flex justify-content-center col-6" value="1">';
                        appendContent += '<p>Activo</p>';
                    appendContent += '</div>';
                    appendContent += '<div class="toggle-value d-flex justify-content-center col-6" value="0">';
                        appendContent += '<p>Inactivo</p>';
                    appendContent += '</div>';
                appendContent += ' </div>';
            appendContent += '</td>';
            appendContent += '<td class="columns-notification-actions text-center action-cell">';
                if(value.deleted_at == null){
                    appendContent += '<i class="fa-solid fa-pen-to-square update-notification-btn"></i>';
                    appendContent += '<i class="fa-solid fa-trash-can delete-notification-btn"></i>';
                }else{
                    appendContent += '<i class="fa-solid fa-trash-arrow-up restore-notification-btn" title="Restaurar notificación"></i>';
                    appendContent += '<i class="fa-solid fa-trash-can force-delete-notification-btn" title="Eliminar notificación permanentemente"></i>';
                }
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#notifications-table tbody .update-notification-row').remove();
    $('#notifications-table tbody').append(appendContent);
    $('#notifications-table tbody .update-notification-row .notification-active').each(function(){
        $(this).find('.toggle-value[value="'+$(this).attr('value')+'"]').click();
    });
    let cityFromList = $('#update-license-city-from').parent().find('.crud-list').clone();
    $('#notifications-table tbody .update-notification-row .crud-input-container').append(cityFromList);
}

export function changeNotificationPosition(container, direction){
    let notificationId = container.parent().parent().attr('notification-id');
    let data = {
        notification_id: notificationId,
        direction: direction
    };
    PostMethodFunction('/admin/licenses/notifications/change-position',data,null,function(response){
        getServiceNotifications();
    },null);
}

export function updateNotification(){
    let updateButton = $(this);
    let container = updateButton.parent().parent();
    let notificationId = container.attr('notification-id');
    let email = container.find('.notification-email').val();
    let phone = container.find('.notification-phone').val();
    let state = container.find('.notification-active').attr('value');
    let flag = true;
    if(email == null || email == "" || !validateEmail(email)){
        container.find('.notification-email').addClass('is-invalid');
        alertWarning('Debe ingresar el email del licencia');
        flag = false;
    }else{
        container.find('.notification-email').removeClass('is-invalid');
    }
    if(flag){
        let dataSend = {
            id: notificationId,
            email: email,
            phone: phone,
            state: state,
        };
        PostMethodFunction('/admin/licenses/notifications/update',dataSend,null,function(response){
            swallMessage(
                'Exito'
                , 'Notificación actualizada'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            getServiceNotifications();
        },null);
    }
}

export function deleteNotification(){
    let deleteButton = $(this);
    let container = deleteButton.parent().parent();
    let notificationId = container.attr('notification-id');
    swallMessage(
        'Eliminar'
        , '¿Seguro deseas eliminar esta notificación?'
        , 'error'
        , 'Si, Eliminar'
        , 'No, Cancelar'
        , null
        , function(){
            let dataSend = {
                id: notificationId
            };
            PostMethodFunction('/admin/licenses/notifications/delete',dataSend,null, function(response){
                swallMessage(
                    'Exito'
                    , 'Notificación eliminada'
                    , 'success'
                    , null
                    , null
                    , 3000
                    , null
                    , null
                );
                getServiceNotifications();
            },null);
        }
        , null
    );
}

export function restoreNotification(){
    let restoreButton = $(this);
    let container = restoreButton.parent().parent();
    let notificationId = container.attr('notification-id');
    swallMessage(
        'Reactivar'
        , '¿Seguro deseas reactivar esta notificación?'
        , 'warning'
        , 'Si, Reactivar'
        , 'No, Cancelar'
        , null
        , function(){
            let dataSend = {
                id: notificationId
            };
            PostMethodFunction('/admin/licenses/notifications/restore',dataSend,null,function(response){
                swallMessage(
                    'Notificación REACTIVADA con éxito'
                    , null
                    , 'success'
                    , null
                    , null
                    , 3000
                    , null
                    , null
                );
                getServiceNotifications();
            },null);
        }
        , null
    );
}
export function forceDeleteNotification(){
    let container = $(this).parent().parent();
    let notificationId = container.attr('notification-id');
    swallMessage('Eliminar permanentemente','Esta acción no se puede deshacer. ¿Deseas eliminar esta notificación de forma permanente?','error','Sí, eliminar permanentemente','No, cancelar',null,function(){
        PostMethodFunction('/admin/licenses/notifications/force-delete',{id: notificationId},null,function(){
            swallMessage('Exito','Notificación eliminada permanentemente','success',null,null,3000,null,null);
            getServiceNotifications();
        },null);
    },null);
}