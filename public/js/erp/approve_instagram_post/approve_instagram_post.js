/*
 * ATTENTION: An "eval-source-map" devtool has been used.
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file with attached SourceMaps in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./resources/js/erp/approve_instagram_post/approve_instagram_post.js":
/*!***************************************************************************!*\
  !*** ./resources/js/erp/approve_instagram_post/approve_instagram_post.js ***!
  \***************************************************************************/
/***/ (() => {

eval("$(document).ready(function () {\n  approvePost();\n});\nfunction approvePost() {\n  var dataSend = {\n    unique_id: unique_id\n  };\n  PostMethodFunction('/api/instagram/approve', dataSend, null, function (response) {\n    $('#approve-post-loading-icon').removeAttr('class').addClass('fa fa-check');\n    $('#approve-post-status-message').text('Post aprobado exitosamente');\n  }, function () {\n    $('#approve-post-loading-icon').removeAttr('class').addClass('fa fa-exclamation-triangle');\n    $('#approve-post-status-message').text('Error al aprobar el post');\n  });\n}//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyIkIiwiZG9jdW1lbnQiLCJyZWFkeSIsImFwcHJvdmVQb3N0IiwiZGF0YVNlbmQiLCJ1bmlxdWVfaWQiLCJQb3N0TWV0aG9kRnVuY3Rpb24iLCJyZXNwb25zZSIsInJlbW92ZUF0dHIiLCJhZGRDbGFzcyIsInRleHQiXSwic291cmNlcyI6WyJ3ZWJwYWNrOi8vLy4vcmVzb3VyY2VzL2pzL2VycC9hcHByb3ZlX2luc3RhZ3JhbV9wb3N0L2FwcHJvdmVfaW5zdGFncmFtX3Bvc3QuanM/NTg2ZSJdLCJzb3VyY2VzQ29udGVudCI6WyIkKGRvY3VtZW50KS5yZWFkeShmdW5jdGlvbigpe1xyXG4gICAgYXBwcm92ZVBvc3QoKTtcclxufSk7XHJcbmZ1bmN0aW9uIGFwcHJvdmVQb3N0KCl7XHJcbiAgICB2YXIgZGF0YVNlbmQgPSB7XHJcbiAgICAgICAgdW5pcXVlX2lkOnVuaXF1ZV9pZFxyXG4gICAgfTtcclxuICAgIFBvc3RNZXRob2RGdW5jdGlvbignL2FwaS9pbnN0YWdyYW0vYXBwcm92ZScsZGF0YVNlbmQsbnVsbCwgZnVuY3Rpb24gKHJlc3BvbnNlKSB7XHJcbiAgICAgICAgJCgnI2FwcHJvdmUtcG9zdC1sb2FkaW5nLWljb24nKS5yZW1vdmVBdHRyKCdjbGFzcycpLmFkZENsYXNzKCdmYSBmYS1jaGVjaycpO1xyXG4gICAgICAgICQoJyNhcHByb3ZlLXBvc3Qtc3RhdHVzLW1lc3NhZ2UnKS50ZXh0KCdQb3N0IGFwcm9iYWRvIGV4aXRvc2FtZW50ZScpO1xyXG4gICAgfSwgZnVuY3Rpb24oKXtcclxuICAgICAgICAkKCcjYXBwcm92ZS1wb3N0LWxvYWRpbmctaWNvbicpLnJlbW92ZUF0dHIoJ2NsYXNzJykuYWRkQ2xhc3MoJ2ZhIGZhLWV4Y2xhbWF0aW9uLXRyaWFuZ2xlJyk7XHJcbiAgICAgICAgJCgnI2FwcHJvdmUtcG9zdC1zdGF0dXMtbWVzc2FnZScpLnRleHQoJ0Vycm9yIGFsIGFwcm9iYXIgZWwgcG9zdCcpO1xyXG4gICAgfSk7XHJcbn0iXSwibWFwcGluZ3MiOiJBQUFBQSxDQUFDLENBQUNDLFFBQVEsQ0FBQyxDQUFDQyxLQUFLLENBQUMsWUFBVTtFQUN4QkMsV0FBVyxDQUFDLENBQUM7QUFDakIsQ0FBQyxDQUFDO0FBQ0YsU0FBU0EsV0FBV0EsQ0FBQSxFQUFFO0VBQ2xCLElBQUlDLFFBQVEsR0FBRztJQUNYQyxTQUFTLEVBQUNBO0VBQ2QsQ0FBQztFQUNEQyxrQkFBa0IsQ0FBQyx3QkFBd0IsRUFBQ0YsUUFBUSxFQUFDLElBQUksRUFBRSxVQUFVRyxRQUFRLEVBQUU7SUFDM0VQLENBQUMsQ0FBQyw0QkFBNEIsQ0FBQyxDQUFDUSxVQUFVLENBQUMsT0FBTyxDQUFDLENBQUNDLFFBQVEsQ0FBQyxhQUFhLENBQUM7SUFDM0VULENBQUMsQ0FBQyw4QkFBOEIsQ0FBQyxDQUFDVSxJQUFJLENBQUMsNEJBQTRCLENBQUM7RUFDeEUsQ0FBQyxFQUFFLFlBQVU7SUFDVFYsQ0FBQyxDQUFDLDRCQUE0QixDQUFDLENBQUNRLFVBQVUsQ0FBQyxPQUFPLENBQUMsQ0FBQ0MsUUFBUSxDQUFDLDRCQUE0QixDQUFDO0lBQzFGVCxDQUFDLENBQUMsOEJBQThCLENBQUMsQ0FBQ1UsSUFBSSxDQUFDLDBCQUEwQixDQUFDO0VBQ3RFLENBQUMsQ0FBQztBQUNOIiwiZmlsZSI6Ii4vcmVzb3VyY2VzL2pzL2VycC9hcHByb3ZlX2luc3RhZ3JhbV9wb3N0L2FwcHJvdmVfaW5zdGFncmFtX3Bvc3QuanMuanMiLCJzb3VyY2VSb290IjoiIn0=\n//# sourceURL=webpack-internal:///./resources/js/erp/approve_instagram_post/approve_instagram_post.js\n");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval-source-map devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./resources/js/erp/approve_instagram_post/approve_instagram_post.js"]();
/******/ 	
/******/ })()
;