import { providerState } from './state.js';

export function addContact(){
    let container = $(this).parent().parent();
    let name = container.find('.contact-name').val();
    let email = container.find('.contact-email').val();
    let phone = container.find('.contact-phone').val();
    let position = container.find('.contact-position').val();
    let flag = true;
    if(name == null || name == ''){ container.find('.contact-name').addClass('is-invalid'); alertWarning('Debe ingresar el nombre del contacto'); flag = false; }else{ container.find('.contact-name').removeClass('is-invalid'); }
    if(email == null || email == '' || !validateEmail(email)){ container.find('.contact-email').addClass('is-invalid'); alertWarning('Debe ingresar el correo del contacto'); flag = false; }else{ container.find('.contact-email').removeClass('is-invalid'); }
    if(flag){
        $('#add-contact').attr('disabled',true);
        PostMethodFunction('/admin/providers/contacts/add',{provider_id: providerState.currentProvider.id,name,email,phone,position},null,function(){
            $('#add-contact').attr('disabled', false);
            container.find('.contact-name,.contact-email,.contact-phone,.contact-position').val('');
            swallMessage('Exito','Contacto creado','success',null,null,3000,null,null);
            getProviderContacts();
        },null);
    }
}
export function getProviderContacts(){
    PostMethodFunction('/admin/providers/contacts/get',{provider_id: providerState.currentProvider.id},null,showServiceContacts,null);
}
function showServiceContacts(response){
    let appendContent = '';
    $.each(response.data,function(index,value){
        appendContent += '<tr contact-id='+value.id+' class="update-contact-row'+(value.deleted_at==null?'':' deleted')+'">';
        appendContent += '<td class="text-left"><p><input type="text" class="text-center form-control align-self-center contact-name" placeholder="Nombre" value="'+value.name+'"></p></td>';
        appendContent += '<td class="text-center"><p><input type="email" class="text-center form-control align-self-center contact-email" placeholder="100000" value="'+value.email+'"></p></td>';
        appendContent += '<td class="text-center"><p><input type="number" class="text-center form-control align-self-center contact-phone" placeholder="100000" value="'+value.phone+'"></p></td>';
        appendContent += '<td class="text-center"><p><input type="text" class="text-center form-control align-self-center contact-position" placeholder="100000" value="'+value.position+'"></p></td><td class="text-center action-cell">';
        if(providerState.currentProvider.deleted_at == null){
            if(value.deleted_at == null) appendContent += '<i class="fa-solid fa-pen-to-square update-contact-btn"></i><i class="fa-solid fa-trash-can delete-contact-btn"></i>';
            else appendContent += '<i class="fa-solid fa-trash-arrow-up restore-contact-btn" title="Restaurar contacto"></i><i class="fa-solid fa-trash-can force-delete-contact-btn" title="Eliminar contacto permanentemente"></i>';
        }
        appendContent += '</td></tr>';
    });
    $('#contacts-table tbody .update-contact-row').remove();
    $('#contacts-table tbody').append(appendContent);
}
export function updateContact(){
    let updateButton = $(this);
    let container = updateButton.parent().parent();
    let name = container.find('.contact-name').val();
    let email = container.find('.contact-email').val();
    if(name == null || name == ''){ container.find('.contact-name').addClass('is-invalid'); alertWarning('Debe ingresar el nombre del contacto'); return; }
    if(email == null || email == '' || !validateEmail(email)){ container.find('.contact-email').addClass('is-invalid'); alertWarning('Debe ingresar el correo del contacto'); return; }
    updateButton.attr('disabled',true);
    PostMethodFunction('/admin/providers/contacts/update',{id: container.attr('contact-id'),name,email,phone:container.find('.contact-phone').val(),position:container.find('.contact-position').val()},null,function(){
        updateButton.attr('disabled', false); swallMessage('Exito','Contacto actualizado','success',null,null,3000,null,null);
    },null);
}
export function deleteContact(){
    let container = $(this).parent().parent();
    swallMessage('¿Seguro desea eliminar este contacto?','Tenga en cuenta que aunque el contacto ya no estará disponible, todo su historial de trazabilidad en la aplicación se conservará.','error','Si, Eliminar','No, Cancelar',null,function(){
        PostMethodFunction('/admin/providers/contacts/delete',{id: container.attr('contact-id')},null,function(){swallMessage('Exito','Contacto eliminado','success',null,null,3000,null,null); getProviderContacts();},null);
    },null);
}
export function restoreContact(){
    let container = $(this).parent().parent();
    swallMessage('¿Seguro desea reactivar este contacto?','Tenga en cuenta que el contacto volverá a tener acceso a la aplicación.','warning','Si, Reactivar','No, Cancelar',null,function(){
        PostMethodFunction('/admin/providers/contacts/restore',{id: container.attr('contact-id')},null,function(){swallMessage('Contacto REACTIVADO con éxito',null,'success',null,null,3000,null,null); getProviderContacts();},null);
    },null);
}
export function forceDeleteContact(){
    let container = $(this).parent().parent();
    swallMessage('Eliminar permanentemente','Esta acción no se puede deshacer. ¿Desea eliminar este contacto de forma permanente?','error','Sí, eliminar permanentemente','No, cancelar',null,function(){
        PostMethodFunction('/admin/providers/contacts/force-delete',{id: container.attr('contact-id')},null,function(){
            swallMessage('Exito','Contacto eliminado permanentemente','success',null,null,3000,null,null);
            getProviderContacts();
        },null);
    },null);
}