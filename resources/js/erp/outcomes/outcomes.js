$(document).ready(function(){
    changeTab();
});
$(document).on('click', '#nav-tab .nav-link', changeTab);
/*create outcome*/
$(document).on('click', '#nav-create #import-btn-container', openMassImportModal);
$(document).on('click', '#nav-create #import-cancel-btn', closeMassImportModal);
$(document).on('click', '#nav-create #import-confirm-btn', confirmMassImport);
$(document).on('change', '#db-pagination-per-page', DBchangePageSize);
$(document).on('click', '#db-pagination .page-item-number', DBchangePage);
$(document).on('click', '#db-page-item-back', DBselectBackPage);
$(document).on('click', '#db-page-item-next', DBselectNextPage);
$(document).on('click', '.delete-outcome', deleteOutcome);
$(document).on('click', '.recover-outcome', recoverOutcome);
$(document).on('change', '#search-list-input', function(){
    db_pagination.page = 1;
    getOutcomesPage();
});
$('#date-from, #date-to').on('change', function() {
    db_pagination.page = 1;
    getOutcomesPage();
});
var db_pagination = {
    page:1,
    size:10,
    total:0,
};
var tabs_view = {
    'nav-list-tab': false,
    'nav-create-tab': false
}
var current_tab = null;
var current_container = null;
var currentLicencesList = [];
var outcomes = [];

function openMassImportModal(){
    $('#import-form-container').css('display', 'flex');
}
function closeMassImportModal(){
    $('#import-form-container').css('display', 'none');
}
function confirmMassImport(){
    let flag = true;
    let file = $('#import-file-input').prop('files')[0];
    if(file === undefined){
        alertWarning('Porfavor seleccione un archivo');
        flag = false;
    }
    if(flag){
        $('#nav-create #import-confirm-btn').prop('disabled', true);
        let dinamicForm = document.createElement("form");
        dinamicForm.setAttribute('id', 'temporal-form');
        dinamicForm.setAttribute('class', 'd-none');
        dinamicForm.appendChild($('#import-file-input').clone(true)[0]);

        dinamicForm.appendChild($('input[name="_token"]').clone(true)[0]);
        document.body.appendChild(dinamicForm);
        dinamicForm = $('#temporal-form');
        dinamicForm.find('#import-file-input')[0].files = $('#import-file-input')[0].files;

        $('#temporal-form').remove();
        PostMethodMultimediaFunction('/admin/outcomes/import', dinamicForm, null, function(response){
            $('#nav-create #import-confirm-btn').attr('disabled', false);
            closeMassImportModal();
            alertSuccess('Importación exitosa');
            /*go to outcomes list*/
        }, function(){$('#nav-create #import-confirm-btn').attr('disabled', false);});
    }
}

function DBshowPagination(){
    let paginationContainer = $('#db-pagination');
	paginationContainer.empty();
	
		let AppenedContent = '';
        AppenedContent += '<li class="page-item page-item-back" id="db-page-item-back"><p class="page-link"><</p></li>';
		
        let closePage = null;
        let showPageSize = 3;
        let dots = {left: false, right: false};
        for (let index = 1; index <= db_pagination.totalPages; index++) {
        closePage = Math.abs(db_pagination.page - index);
        if(closePage != null && closePage <= showPageSize){
            if(String(index).length<3){
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
        }else if(index <= showPageSize){
            if(String(index).length<3){
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
            if(!dots.left && index == showPageSize){
            AppenedContent += '<li class="page-item" title="'+(index)+'"><p class="page-link">...</p></li>';
            dots.left = true;
            }
        }else if(index >= db_pagination.totalPages - 2){
            if(!dots.right){
            AppenedContent += '<li class="page-item" title="'+(index)+'"><p class="page-link">...</p></li>';
            dots.right = true;
            }
            if(String(index).length<3){
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'">'+index+'</p></li>';
            }else{
            AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==db_pagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
            }
        }
        }
		
		AppenedContent += '<li class="page-item page-item-next" id="db-page-item-next"><p class="page-link">></p></li>';
        //page size
        if(db_pagination.total>5){
            AppenedContent += '<li class="page-item">';
                AppenedContent += "<p class='page-link'>";
                    AppenedContent += '<select id="db-pagination-per-page" aria-label="Default select example">';
                        AppenedContent += '<option value="5"'+((db_pagination.size==5)?' selected':'')+'>5</option>';
                        AppenedContent += '<option value="10"'+((db_pagination.size==10)?' selected':'')+'>10</option>';
                        AppenedContent += '<option value="50"'+((db_pagination.size==50)?' selected':'')+'>50</option>';
                    AppenedContent += '</select>';
                    AppenedContent += "</p>";
            AppenedContent += '</li>';
        }
		paginationContainer.append(AppenedContent);
	
}

function getOutcomesPage(){
    let DataSend = {
        page:  db_pagination.page,
        size:  db_pagination.size,
        search: $('#search-list-input').val().trim(),
        from: $('#date-from').val() || null,
        to: $('#date-to').val() || null
    };
    PostMethodFunction('/admin/outcomes/get',DataSend,null, showOutcomesPage,null);
}

function showOutcomesPage(response){
    //states
    db_pagination = response.pagination;
    outcomes = response.data;
    let appendContent = '';
    $.each(outcomes,function(index,value){
        //round total
        value.total = Math.round(value.total);
        appendContent += '<tr outcome-id='+value.id+' class="'+(value.deleted_at == null?'':' deleted')+'">';
            appendContent += '<td class="columns-id text-center" title="'+value.unique_id+'"><i class="fa-regular fa-copy copy-action me-1" data-clipboard-text="'+value.unique_id+'"></i>'+value.unique_id.substr(value.unique_id.length - 5)+'</td>';
            appendContent += '<td class="columns-date text-center" title="'+value.date+'"><p>'+value.date.substring(0, 10)+'</p></td>';
            let displayType = (value.type === -1) ? 'Otro' : 'Otro';
            appendContent += '<td class="columns-timely-payment text-center"><p>' + displayType + '</p></td>';
            appendContent += '<td class="columns-cutoff-date text-center"><p>'+value.name+'</p></td>';
            appendContent += '<td class="columns-bill text-start"><p>'+(value.description==null?'':value.description)+'</p></td>';
            appendContent += '<td class="columns-total text-center" title="'+value.amount+'"><p style="font-weight: bold; color: #CE7488" >$'+parseInt(value.amount).toLocaleString('es-CO')+'</p></td>';
            appendContent += '<td class="columns-actions text-center action-cell">';
            if (value.deleted_at == null) {
                appendContent += '<i class="fa fa-trash-alt delete-outcome" title="Eliminar"></i>';
            }else{
                appendContent += '<i class="fa fa-ban recover-outcome" title="Recuperar" style="color: red;"></i>';
            }
            appendContent += '</td>';
        appendContent += '</tr>';
    });
    $('#outcome-list-table #outcome-list-table-body').empty().append(appendContent);
    DBshowPagination();
}

function changeTab(){
    if($('#nav-tab .active').length === 0) return;
    current_tab = $('#nav-tab .active').attr('id');
    current_container = $($('#nav-tab .active').attr('data-bs-target'));
    currentLicencesList = [];
    if(tabs_view[current_tab]==false && current_tab == 'nav-list-tab'){
        $('#search-list-input').focus();
        getOutcomesPage();
        tabs_view['nav-create-tab'] = false;
    }else if(tabs_view[current_tab]==false && current_tab == 'nav-create-tab'){
        tabs_view['nav-list-tab'] = false;
    }
    tabs_view[current_tab] = true;    
}

function DBchangePageSize(){
    db_pagination.size = $('#db-pagination-per-page').val();
    db_pagination.page = 1;
    getOutcomesPage();
}
function DBchangePage(){
    let selected_page = $(this).attr('title');
    if(selected_page != db_pagination.page){
        db_pagination.page = selected_page;
        getOutcomesPage();
    }
}
function DBselectBackPage(){
    if(db_pagination.page>1){
        db_pagination.page = parseInt(db_pagination.page)-1;
        getOutcomesPage();
    }
}
function DBselectNextPage(){
    if(db_pagination.page<db_pagination.totalPages){
        db_pagination.page = parseInt(db_pagination.page)+1;
        getOutcomesPage();
    }
}

function deleteOutcome(){
    const $row = $(this).closest('tr');
    const id = $row.attr('outcome-id');
    if (!id) return;
    swallMessage(
        'Advertencia'
        , '¿Está seguro de eliminar este egreso?'
        , 'error'
        , 'Si, eliminar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: id,
            };
            PostMethodFunction('/admin/outcomes/delete',DataSend,null, function(response){
                alertSuccess('Egreso eliminado');
                getOutcomesPage();
            },null);
        }
        , null
    ); 
}

function recoverOutcome(){
    const $row = $(this).closest('tr');
    const id = $row.attr('outcome-id');
    if (!id) return;
    swallMessage(
        'Advertencia'
        , '¿Está seguro de recuperar este egreso?'
        , 'error'
        , 'Si, recuperar'
        , 'No'
        ,null
        ,function(){
            let DataSend = {
                id: id,
            };
            PostMethodFunction('/admin/outcomes/recover',DataSend,null, function(response){
                alertSuccess('Egreso recuperado');
                getOutcomesPage();
            },null);
        }
        , null
    ); 
}