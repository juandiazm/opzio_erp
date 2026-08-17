import { reportState } from './state.js';
import * as charts from './charts.js';
import * as zoom from './zoom.js';
import * as reportExport from './export.js';

$('.report-item-date-input').on('apply.daterangepicker', function(ev, picker){
    charts.setDataOnReportItem($(this).attr('id'), picker.startDate.format('YYYY-MM-DD'), picker.endDate.format('YYYY-MM-DD'));
    charts.refreshCheckedGraphs(picker.startDate, picker.endDate, $(this).attr('id'));
});
$('#date-range-input-zoom-in').on('apply.daterangepicker', function(ev, picker){
    charts.setDataOnReportItem(reportState.zoomGraphId, picker.startDate.format('YYYY-MM-DD'), picker.endDate.format('YYYY-MM-DD'));
});
$('.report-item-canvas').on('click', zoom.openZoomInModal);
$('#zoom-in-close-icon').on('click', zoom.closeZoomInModal);
$('#export-report-excel-container').on('click', function(){
    let sheets = [];
    $('.report-item-checkbox:checked').each(function(){ sheets.push($(this).closest('.report-item').attr('value')); });
    reportExport.exportReport(sheets, this);
});
$('#zoom-in-export-report-excel-icon').on('click', function(){
    let sheets = [];
    sheets.push($('#'+reportState.zoomGraphId).closest('.report-item').attr('value'));
    reportExport.exportReport(sheets, this);
});

$(document).ready(function(){
    let startDate = moment().subtract(3, 'month');
    let endDate = moment();
    $('.report-item-date-input').daterangepicker({showDropdowns: true, startDate: startDate, endDate: endDate, maxDate: endDate});
    $('#date-range-input-zoom-in').daterangepicker({showDropdowns: true, startDate: startDate, endDate: endDate, maxDate: endDate});
    startDate = startDate.format('YYYY-MM-DD');
    endDate = endDate.format('YYYY-MM-DD');
    charts.setDataOnReportItem('date-range-input-users', startDate, endDate);
    charts.setDataOnReportItem('date-range-input-clients', startDate, endDate);
    charts.setDataOnReportItem('date-range-input-employees', startDate, endDate);
    charts.setDataOnReportItem('date-range-input-licenses', startDate, endDate);
    charts.setDataOnReportItem('date-range-input-incomes', startDate, endDate);
    charts.setDataOnReportItem('date-range-input-outcomes', startDate, endDate);
});