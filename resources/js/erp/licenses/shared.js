export function verificationInputChange(){
    let container = $(this).parent();
    let value = $(this).attr('value');
    container.attr('value', value);
    container.find('.verification-input-icon').removeClass('enabled').addClass('disabled');
    $(this).addClass('enabled').removeClass('disabled');
}

export function getClients(){
    PostMethodFunction('/admin/clients/get-all',{},null, showClients, null);
}

function showClients(response){
    let appendContent = '<option value="">Selecciona un cliente</option>';
    $.each(response.data, function(index, value){
        appendContent += '<option value="'+value.id+'">'+value.name+'</option>';
    });
    $('#create-license-client').html(appendContent);
    $('#update-license-client').html(appendContent);
}

export function getEmployees(){
    PostMethodFunction('/admin/employees/get-all',{},null, showEmployees, null);
}

function showEmployees(response){
    let appendContent = '<option value="">Selecciona un empleado</option>';
    $.each(response.data, function(index, value){
        appendContent += '<option value="'+value.id+'">'+value.name+'</option>';
    });
    $('#create-license-employee').html(appendContent);
    $('#update-license-employee').html(appendContent);
}