$(document).on('click', '.crud-input-container .crud-input-selected-container .crud-input-arrow', toggleCRUDList);
$(document).on('click', '.crud-input-container .crud-input-selected-container .crud-current-selected-input', toggleCRUDList);
$(document).on('click', '.crud-input-container .crud-list .crud-item-add-icon', addCRUDItem);
$(document).on('click', '.crud-input-container .crud-list .crud-item-update-icon', enableChangeOnItem);
$(document).on('change', '.crud-input-container .crud-list .crud-item-update .crud-item-update-input', updateCRUDItem);
$(document).on('click', '.crud-input-container .crud-list .crud-item-delete-icon', deleteCRUDItem);
$(document).on('click', '.crud-input-container .crud-list .crud-item-update .crud-item-update-input', selectCRUDItem);
$(document).on('change', '.crud-input-container .crud-input-selected-container .crud-current-selected-input', searchOnList);
var prefixList = [];
$(document).ready(function(){
    $('.crud-input-container').each(function(){
        getCRUDElements($(this), true);
    });
    //key press function on .crud-item-add-input
    $('.crud-input-container .crud-list .crud-item-add-input').keypress(function(e){
        if(e.which == 13){
            e.preventDefault();
            e.stopPropagation();
            //find the nearest .crud-item-add-icon and click it
            $(this).parent().find('.crud-item-add-icon').click();
        }
    });
});
function getCRUDElements(container, firstTime = false){
    let prefix = container.attr('prefix');
    let isGetter = container.hasClass('getter');
    if(!firstTime || prefixList.indexOf(prefix) == -1){
        prefixList.push(prefix);
        let DataSend = {
            search : container.find('crud-current-selected-input').val()
        }
        //remove undefined and null values
        DataSend = JSON.parse(JSON.stringify(DataSend));
        PostMethodFunction(prefix + 'get', DataSend, null, function(response){
            if(isGetter){
                container.find('.crud-item-add').remove();
            }
            $('.crud-input-container[prefix="'+prefix+'"] .crud-list .crud-item-update').remove();
            let appendContent = '';
            let items = response.data;
            $.each(items, function(index, item){
                appendContent += '<li class="crud-item-update justify-content-between" item-id="'+item.id+'">';
                    if(isGetter){
                        appendContent += '<input type="text" class="crud-item-update-input align-self-center" value="'+item.name+'" readonly>';
                    }else{
                        appendContent += '<input type="text" class="crud-item-update-input align-self-center" placeholder="Actualizar" value="'+item.name+'">';
                        appendContent += '<i class="crud-item-update-icon fa-solid fa-pencil align-self-center"></i>';
                        appendContent += '<i class="crud-item-delete-icon fa-solid fa-trash-can align-self-center"></i>';
                    }
                appendContent += '</li>';
            });
            $('.crud-input-container[prefix="'+prefix+'"] .crud-list').append(appendContent);
        }, null);
    }
}
function toggleCRUDList(){
    let container = $(this).closest('.crud-input-container');
    let list = container.find('.crud-list');
    if(list){
        if(list.hasClass('closed')){
            list.removeClass('closed').addClass('opened');
            list.slideDown(100);
        }else{
            list.removeClass('opened').addClass('closed');
            list.slideUp(100);
        }
        
    }
}
function addCRUDItem(e){
    e.preventDefault();
    e.stopPropagation();
    let prefix = $(this).closest('.crud-input-container').attr('prefix');
    let flag = true;
    let container = $(this).closest('.crud-input-container');
    let name = container.find('.crud-item-add-input').val();
    if(name == null || name == undefined || name == ''){
        flag = false;
        alertWarning('Debe ingresar un valor');
    }
    if(flag){
        let DataSend = {
            name : name
        }
        PostMethodFunction(prefix + 'add', DataSend, null, function(){
            container.find('.crud-item-add-input').val('').focus();
            getCRUDElements(container);
        }, null);
    }
}
function updateCRUDItem(e){
    e.preventDefault();
    e.stopPropagation();
    let item_container = $(this).closest('.crud-item-update');
    let container = item_container.closest('.crud-input-container');
    let prefix = container.attr('prefix');
    let flag = true;
    let id = item_container.attr('item-id');
    let name = item_container.find('.crud-item-update-input').val();
    if(name == null || name == undefined || name == ''){
        flag = false;
        alertWarning('Debe ingresar un valor');
    }
    if(flag){
        let DataSend = {
            id: id,
            name : name
        }
        PostMethodFunction(prefix + 'update', DataSend, 'Elemento actualizado', function(){
            getCRUDElements(container);
        }, null);
    }
}
function deleteCRUDItem(e){
    e.preventDefault();
    e.stopPropagation();
    let item_container = $(this).closest('.crud-item-update');
    let container = item_container.closest('.crud-input-container');
    let prefix = container.attr('prefix');
    let id = item_container.attr('item-id');
    swallMessage(
        '¿Seguro desea eliminar el elemento?'
        , 'Esta acción no se puede deshacer'
        , 'error'
        , 'Si, Eliminar'
        , 'No, Cancelar'
        , null
        , function(){
            let DataSend = {
                id: id
            }
            PostMethodFunction(prefix + 'delete', DataSend, null, function(){
                $('.crud-input-container[prefix="'+prefix+'"] .crud-list .crud-item-update[item-id="'+id+'"]').remove();
            }, null);
        }
        , null
        , null
    );
}
function selectCRUDItem(e){
    e.preventDefault();
    e.stopPropagation();
    let super_container = $(this).closest('.crud-input-container');
    let selected_container = super_container.find('.crud-input-selected-container');
    let container = $(this).closest('.crud-item-update');
    let item_id = container.attr('item-id');
    let item_name = container.find('.crud-item-update-input').val();
    selected_container.attr('item-id', item_id);
    selected_container.find('.crud-current-selected-input').val(item_name);
    //double click to selected_container
    selected_container.find('.crud-input-arrow').trigger('click');
}
function searchOnList(){
    let container = $(this).closest('.crud-input-container');
    let search = $(this).val();
    let list = container.find('.crud-list');
    let items = list.find('.crud-item-update');
    $.each(items, function(index, item){
        let item_name = $(item).find('.crud-item-update-input').val();
        if(item_name.toLowerCase().indexOf(search.toLowerCase()) == -1){
            $(item).css('display', 'none');
        }else{
            $(item).css('display', 'flex');
        }
    });
    if(list.hasClass('closed')){
        list.removeClass('closed').addClass('opened');
        list.slideDown(100);
    }
}
function enableChangeOnItem(e){
    e.preventDefault();
    e.stopPropagation();
    let container = $(this).closest('.crud-item-update');
    let input = container.find('.crud-item-update-input');
    input.select().focus();
}