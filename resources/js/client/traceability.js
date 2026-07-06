/////////
$(document).on('change', '.traceability-pagination-per-page', traceabilityChangePageSize);
$(document).on('click', '.traceability-pagination .page-item-number', traceabilityChangePage);
$(document).on('click', '.traceability-page-item-back', traceabilitySelectBackPage);
$(document).on('click', '.traceability-page-item-next', traceabilitySelectNextPage);
$(document).on('change', '.traceability-filter-container .input-value', traceabilityFilterChange);
//event when class active set to element
$(document).on('active', '.traceability-filter-container .input-value', traceabilityFilterChange);
/////////
var traceability_containers_list = [];
var traceability_proceced_indexes = [];
const targetElement = document.getElementsByClassName('traceability-container');
$(document).ready(function(){
    renderTraceabilityContent();
});
function renderTraceabilityContent(){
    let containers = $('.traceability-container');
    let appendContent = '';
    //resta un mes a la fecha actual
    let date_from = new Date();
    date_from.setMonth(date_from.getMonth() - 1);
    let date_to = new Date();
    $.each(containers, function(index, container){
        //add container to list
        traceability_containers_list.push(
            {
                index: index,
                url: $(container).attr('data-url'),
                pagination: {
                    page:1,
                    per_page:10,
                    total:0,
                }
            }
        );
        /////////////
        //set index as an attribute on container
        $(container).attr('data-index',index);
        ///////////////////////
        appendContent = '<div class="traceability-filter-container">';
            appendContent += '<div class="traceability-filter-input-container input-container d-flex">';
                appendContent += '<label for="traceability-filter-input" class="input-title align-self-center">Buscador: </label>';
                appendContent += '<input type="text" class="traceability-filter-search input-value form-control align-self-center" name="traceability-filter-input" placeholder="Buscar...">';
            appendContent += '</div>';
            appendContent += '<div class="traceability-filter-date-container input-container d-flex">';
                appendContent += '<label for="traceability-filter-user" class="input-title align-self-center">Usuario: </label>';
                appendContent += '<select class="traceability-filter-user input-value form-select align-self-center" name="traceability-filter-user">';
                    appendContent += '<option value="0">Todos</option>';
                appendContent += '</select>';
            appendContent += '</div>';
            appendContent += '<div class="traceability-filter-date-container input-container d-flex">';
                appendContent += '<label for="traceability-filter-date-from" class="input-title align-self-center">Desde: </label>';
                appendContent += '<input type="date" class="traceability-filter-date-from input-value form-control align-self-center" name="traceability-filter-date-from" value="'+date_from.toISOString().slice(0,10)+'">';
            appendContent += '</div>';
            appendContent += '<div class="traceability-filter-date-container input-container d-flex">';
                appendContent += '<label for="traceability-filter-date-to" class="input-title align-self-center">Hasta: </label>';
                appendContent += '<input type="date" class="traceability-filter-date-to input-value form-control align-self-center" name="traceability-filter-date-to" value="'+date_to.toISOString().slice(0,10)+'">';
            appendContent += '</div>';
        appendContent += '</div>';
        appendContent += '<div class="traceability-table-container scrollable">';
            appendContent += '<table class="traceability-table table table-hover table-sm align-middle w-100">';
                appendContent += '<thead class="traceability-table-header">';
                    appendContent += '<tr>';
                        appendContent += '<th scope="col" class="text-center traceability-col-id">ID</th>';
                        appendContent += '<th scope="col" class="text-center">Usuario</th>';
                        appendContent += '<!--<th scope="col" class="text-center">Método</th>-->';
                        appendContent += '<th scope="col" class="text-left">Dirección</th>';
                        appendContent += '<th scope="col" class="text-left traceability-col-data">Datos</th>';
                        appendContent += '<th scope="col" class="text-left">IP</th>';
                        appendContent += '<th scope="col" class="text-left">Fecha y hora</th>';
                    appendContent += '</tr>';
                appendContent += '</thead>';
                appendContent += '<tbody class="traceability-table-body">';
                    
                appendContent += '</tbody>';
            appendContent += '</table>';
        appendContent += '</div>';
        appendContent += '<ul class="traceability-pagination pagination pagination-sm justify-content-end px-0 mx-0 d-flex"></ul>';
        $(container).html(appendContent);
    });
    getAllUsers();
    var observables = [];
    let container_index;
    // Configura las opciones para el observador
    const config = { attributes: true, attributeFilter: ['class'] };
    $.each(targetElement,function(index,element){
        observables.push(
            new MutationObserver((mutationsList, observer) => {
                // Recorre las mutaciones y verifica si la clase 'active' fue agregada
                let found = false;
                let index = 0;
                do{
                    if (mutationsList[index].type === 'attributes' && mutationsList[index].attributeName === 'class') {
                        const element = mutationsList[index].target;
                        container_index = element.getAttribute('data-index');
                        if (element.classList.contains('active') && !traceability_proceced_indexes.includes(container_index)) {
                            found = true;
                            traceability_proceced_indexes.push(container_index);
                            // La clase 'active' fue agregada, aquí puedes disparar tu evento
                            getTraceability(container_index);
                        }
                    }
                    index++;
                }while(!found && index<mutationsList.length);
                
            })
        );
        
    
        // Inicia la observación del elemento target con las opciones especificadas
        observables[index].observe(element, config);
    });
}
//Traceability
function getAllUsers(){
    GetMethodFunction('/client/users/all',null,function(response){
        let appendData = '<option value="">Todos</option>';
        $.each(response.data,function(index,value){
            appendData += '<option value="'+value.id+'"'+(typeof user_id !== 'undefined' && value.id==user_id?' selected':'')+'>'+value.name+(value.lastname==null?'':' '+value.lastname)+'</option>';
        });
        $('.traceability-filter-user').html(appendData);
    },null);
}
function getTraceability(index){
    let container = $('.traceability-container[data-index="'+index+'"]');
    //check default values
    let attrValue = container.attr('user-id');
    if (typeof attrValue !== 'undefined' && attrValue !== false) {
        container.find('.traceability-filter-user').val(attrValue);
        container.removeAttr('user-id');
    }
    attrValue = container.attr('search');
    if (typeof attrValue !== 'undefined' && attrValue !== false) {
        container.find('.traceability-filter-search').val(attrValue);
        container.removeAttr('search');
    }
    //////////////////////
    let search = container.find('.traceability-filter-search').val();
    let user = container.find('.traceability-filter-user').val();
    let date_from = container.find('.traceability-filter-date-from').val();
    let date_to = container.find('.traceability-filter-date-to').val();
    let DataSend = {
        pagination: traceability_containers_list[index].pagination,
        client_user_id: user,
        search: search,
        date_from: date_from,
        date_to: date_to,
        url:traceability_containers_list[index].url
    };
    PostMethodFunction('/client/users/traceability',DataSend,null, showTraceability,null, {container:container, index:index});
    setTimeout(() => {
        traceability_proceced_indexes = traceability_proceced_indexes.filter(function(value){
            return value != index;
        });
    }, 1000);
}
function traceabilityFilterChange(){
    let container = $(this).closest('.traceability-container');
    let index = container.attr('data-index');
    traceability_containers_list[index].pagination.page = 1;
    getTraceability(index);

}
function showTraceability(response, functionData){
    traceability_containers_list[functionData.index].pagination = response.pagination;
    let appendContent = '';
    let payload;
    $.each(response.data,function(index,value){
        payload = (value.payload==null?'':value.payload).replace('{','').replace('}','').replaceAll('"','');
        complete_name = value.client_user.name+(value.client_user.lastname==null?' ':value.client_user.lastname);
        name_initials = value.client_user.name.charAt(0)+(value.client_user.lastname==null?'':value.client_user.lastname.charAt(0));
        appendContent += '<tr>';
            appendContent += '<td class="text-center traceability-col-id"><p>'+String(value.id).padStart(5, '0')+'</p></td>';
            appendContent += '<td class="text-center color-column" title="'+value.client_user.name+'"><div class="d-flex flex-column justify-content-center" style="background-color:'+value.client_user.color+'"><p class="client-user-input-color align-self-end input-value">'+name_initials+'</p></div></td>';
            //appendContent += '<td class="text-center"><p>'+value.action+'</p></td>';
            appendContent += '<td class="text-left"><p>'+value.path+'</p></td>';
            appendContent += '<td class="text-left traceability-col-data" title="'+payload.replaceAll(',','\n')+'"><p class="scrollable-transparent">'+payload.replaceAll(',','<br>')+'</p></td>';
            appendContent += '<td class="text-left"><p>'+value.ip+'</p></td>';
            appendContent += '<td class="text-left"><p>'+new Date(value.created_at).toLocaleDateString('es-CO',{ hour: '2-digit', minute: '2-digit' })+'</p></td>';
        appendContent += '</tr>';
    });
    functionData.container.find('.traceability-table .traceability-table-body').empty().append(appendContent);
    TraceabilityshowPagination(functionData);
    
}
function traceabilityChangePageSize(){
    let container = $(this).closest('.traceability-container');
    let index = container.attr('data-index');
    traceability_containers_list[index].pagination.per_page = container.find('.traceability-pagination-per-page').val();
    traceability_containers_list[index].pagination.page = 1;
    getTraceability(index);
}
function traceabilityChangePage(){
    let container = $(this).closest('.traceability-container');
    let index = container.attr('data-index');
    let selected_page = $(this).attr('title');
    if(selected_page != traceability_containers_list[index].pagination.page){
        traceability_containers_list[index].pagination.page = selected_page;
        getTraceability(index);
    }
}
function traceabilitySelectBackPage(){
    let container = $(this).closest('.traceability-container');
    let index = container.attr('data-index');
    let next_page = parseInt(traceability_containers_list[index].pagination.page)-1;
    if(next_page>0){
        traceability_containers_list[index].pagination.page = next_page;
        getTraceability(index);
    }
}
function traceabilitySelectNextPage(){
    let container = $(this).closest('.traceability-container');
    let index = container.attr('data-index');
    let next_page = parseInt(traceability_containers_list[index].pagination.page)+1;
    if(next_page<=traceability_containers_list[index].pagination.totalPages){
        traceability_containers_list[index].pagination.page = next_page;
        getTraceability(index);
    }
}
function TraceabilityshowPagination(functionData){
    let paginationContainer = functionData.container.find('.traceability-pagination');
	paginationContainer.empty();
	if(traceability_containers_list[functionData.index].pagination.totalPages > 1){
		let AppenedContent = '';
        AppenedContent += '<li class="page-item page-item-back traceability-page-item-back"><p class="page-link"><</p></li>';
		if(traceability_containers_list[functionData.index].pagination.totalPages < 10){
		  for (let index = 1; index <= traceability_containers_list[functionData.index].pagination.totalPages; index++) {
			AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==traceability_containers_list[functionData.index].pagination.page?' active':'')+'">'+index+'</p></li>';
		  }
		}else{
		  let closePage = null;
		  let showPageSize = 3;
		  let dots = {left: false, right: false};
		  for (let index = 1; index <= traceability_containers_list[functionData.index].pagination.totalPages; index++) {
			closePage = Math.abs(traceability_containers_list[functionData.index].pagination.page - index);
			if(closePage != null && closePage <= showPageSize){
			  if(String(index).length<3){
				AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==traceability_containers_list[functionData.index].pagination.page?' active':'')+'">'+index+'</p></li>';
			  }else{
				AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==traceability_containers_list[functionData.index].pagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
			  }
			}else if(index <= showPageSize){
			  if(String(index).length<3){
				AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==traceability_containers_list[functionData.index].pagination.page?' active':'')+'">'+index+'</p></li>';
			  }else{
				AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==traceability_containers_list[functionData.index].pagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
			  }
			  if(!dots.left && index == showPageSize){
				AppenedContent += '<li class="page-item" title="'+(index)+'"><p class="page-link">...</p></li>';
				dots.left = true;
			  }
			}else if(index >= traceability_containers_list[functionData.index].pagination.totalPages - 2){
			  if(!dots.right){
				AppenedContent += '<li class="page-item" title="'+(index)+'"><p class="page-link">...</p></li>';
				dots.right = true;
			  }
			  if(String(index).length<3){
				AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==traceability_containers_list[functionData.index].pagination.page?' active':'')+'">'+index+'</p></li>';
			  }else{
				AppenedContent += '<li class="page-item page-item-number" title="'+(index)+'"><p class="page-link'+(index==traceability_containers_list[functionData.index].pagination.page?' active':'')+'"><small>'+index+'</small></p></li>';
			  }
			}
		  }
		}
		AppenedContent += '<li class="page-item page-item-next traceability-page-item-next"><p class="page-link">></p></li>';
        if(traceability_containers_list[functionData.index].pagination.total>5){
            //page size
            AppenedContent += '<li class="page-item">';
                AppenedContent += "<p class='page-link'>";
                    AppenedContent += '<select class="traceability-pagination-per-page" aria-label="Default select example">';
                        if(traceability_containers_list[functionData.index].pagination.total>5){
                            AppenedContent += '<option value="5"'+((traceability_containers_list[functionData.index].pagination.per_page==5)?' selected':'')+'>5</option>';
                        }
                        if(traceability_containers_list[functionData.index].pagination.total>10){
                            AppenedContent += '<option value="10"'+((traceability_containers_list[functionData.index].pagination.per_page==10)?' selected':'')+'>10</option>';
                        }
                        if(traceability_containers_list[functionData.index].pagination.total>50){
                            AppenedContent += '<option value="50"'+((traceability_containers_list[functionData.index].pagination.per_page==50)?' selected':'')+'>50</option>';
                        }
                    AppenedContent += '</select>';
                    AppenedContent += "</p>";
            AppenedContent += '</li>';
        }
		paginationContainer.append(AppenedContent);
	}
}