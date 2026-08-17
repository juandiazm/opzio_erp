import { licenseState } from './state.js';

export function updateLicenseDetails(onUpdated){
    let type = $('#update-license-type').val();
    let recurrenceMonths = $('#update-license-recurrence-months').val();
    let billingDay = $('#update-license-billing-day').val();
    let daysToExpire = $('#update-license-days-to-expire').val();
    let nextBillingDate = $('#update-license-next-billing-date').val();
    let lastPayedDate = $('#update-license-last-payed-date').val();
    let flag = true;
    if(type == null || type == ''){
        $('#update-license-type').addClass('is-invalid');
        alertWarning('Debe seleccionar un tipo de licencia');
        flag = false;
    }else{
        $('#update-license-type').removeClass('is-invalid');
        if(type == '1'){
            if(recurrenceMonths == null || recurrenceMonths == ''){
                $('#update-license-monthly-frequency').addClass('is-invalid');
                alertWarning('Debe ingresar la frecuencia mensual');
                flag = false;
            }else{
                $('#update-license-monthly-frequency').removeClass('is-invalid');
            }
            if(billingDay == null || billingDay == ''){
                $('#update-license-billing-day').addClass('is-invalid');
                alertWarning('Debe ingresar el dia de facturación');
                flag = false;
            }else{
                $('#update-license-billing-day').removeClass('is-invalid');
            }
            if(daysToExpire == null || daysToExpire == ''){
                $('#update-license-days-to-expire').addClass('is-invalid');
                alertWarning('Debe ingresar los días de expiración');
                flag = false;
            }else{
                $('#update-license-days-to-expire').removeClass('is-invalid');
            }
        }
    }
    if(flag){
        $('#update-license-details-button').prop('disabled', true);
        let dataSend = {
            id: licenseState.currentLicense.id,
            type: type,
            recurrence_months: recurrenceMonths,
            billing_day: billingDay,
            days_to_expire: daysToExpire,
            next_billing_date: nextBillingDate,
            last_payed_date: lastPayedDate,
        };
        PostMethodFunction('/admin/licenses/update-details',dataSend,null, function(response){
            $('#update-license-details-button').attr('disabled', false);
            swallMessage(
                'Exito'
                , 'Detalles de licencia actualizados'
                , 'success'
                , null
                , null
                , 3000
                , null
                , null
            );
            licenseState.tabsView['nav-list-tab'] = false;
            licenseState.currentLicense = response.license;
            onUpdated();
        }, function(){$('#update-license-details-button').attr('disabled', false);});
    }
}

export function licenseTypeChange(){
    let value = $(this).val();
    if(value == '1'){
        $('#update-license-recurrence-months').attr('disabled', false);
        $('#update-license-billing-day').attr('disabled', false);
        $('#update-license-days-to-expire').attr('disabled', false);
    }else{
        $('#update-license-recurrence-months').attr('disabled', true);
        $('#update-license-billing-day').attr('disabled', true);
        $('#update-license-days-to-expire').attr('disabled', true);
    }
}