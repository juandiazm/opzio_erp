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

/***/ "./resources/js/client/layouts/menu.js":
/*!*********************************************!*\
  !*** ./resources/js/client/layouts/menu.js ***!
  \*********************************************/
/***/ (() => {

eval("$(document).on('click', '#burger-menu', openResponsiveEmergentMenu);\n$(document).on('click', '#responsive-close-opt', closeResponsiveEmergentMenu);\n$(document).on('click', '#responsive-menu .nav-list', closeResponsiveEmergentMenu);\n$(document).ready(function () {});\nfunction closeResponsiveEmergentMenu() {\n  $('#responsive-menu').animate({\n    left: '-100%'\n  }, 500, function () {\n    $('#responsive-menu').css('display', 'none');\n  });\n}\nfunction openResponsiveEmergentMenu() {\n  $('#responsive-menu').css('display', 'block').css('left', '-100%');\n  $('#responsive-menu').animate({\n    left: '0%'\n  }, 500);\n}//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyIkIiwiZG9jdW1lbnQiLCJvbiIsIm9wZW5SZXNwb25zaXZlRW1lcmdlbnRNZW51IiwiY2xvc2VSZXNwb25zaXZlRW1lcmdlbnRNZW51IiwicmVhZHkiLCJhbmltYXRlIiwibGVmdCIsImNzcyJdLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9yZXNvdXJjZXMvanMvY2xpZW50L2xheW91dHMvbWVudS5qcz8xOGUzIl0sInNvdXJjZXNDb250ZW50IjpbIiQoZG9jdW1lbnQpLm9uKCdjbGljaycsICcjYnVyZ2VyLW1lbnUnLCBvcGVuUmVzcG9uc2l2ZUVtZXJnZW50TWVudSk7XHJcbiQoZG9jdW1lbnQpLm9uKCdjbGljaycsICcjcmVzcG9uc2l2ZS1jbG9zZS1vcHQnLCBjbG9zZVJlc3BvbnNpdmVFbWVyZ2VudE1lbnUpO1xyXG4kKGRvY3VtZW50KS5vbignY2xpY2snLCAnI3Jlc3BvbnNpdmUtbWVudSAubmF2LWxpc3QnLCBjbG9zZVJlc3BvbnNpdmVFbWVyZ2VudE1lbnUpO1xyXG4kKGRvY3VtZW50KS5yZWFkeShmdW5jdGlvbigpe1xyXG59KTtcclxuZnVuY3Rpb24gY2xvc2VSZXNwb25zaXZlRW1lcmdlbnRNZW51KCl7XHJcbiAgICAkKCcjcmVzcG9uc2l2ZS1tZW51JykuYW5pbWF0ZSh7XHJcbiAgICAgICAgbGVmdDogJy0xMDAlJ1xyXG4gICAgfSwgNTAwLCBmdW5jdGlvbigpe1xyXG4gICAgICAgICQoJyNyZXNwb25zaXZlLW1lbnUnKS5jc3MoJ2Rpc3BsYXknLCAnbm9uZScpO1xyXG4gICAgfSk7XHJcbn1cclxuZnVuY3Rpb24gb3BlblJlc3BvbnNpdmVFbWVyZ2VudE1lbnUoKXtcclxuICAgICQoJyNyZXNwb25zaXZlLW1lbnUnKS5jc3MoJ2Rpc3BsYXknLCAnYmxvY2snKS5jc3MoJ2xlZnQnLCAnLTEwMCUnKTtcclxuICAgICQoJyNyZXNwb25zaXZlLW1lbnUnKS5hbmltYXRlKHtcclxuICAgICAgICBsZWZ0OiAnMCUnXHJcbiAgICB9LCA1MDApO1xyXG59Il0sIm1hcHBpbmdzIjoiQUFBQUEsQ0FBQyxDQUFDQyxRQUFRLENBQUMsQ0FBQ0MsRUFBRSxDQUFDLE9BQU8sRUFBRSxjQUFjLEVBQUVDLDBCQUEwQixDQUFDO0FBQ25FSCxDQUFDLENBQUNDLFFBQVEsQ0FBQyxDQUFDQyxFQUFFLENBQUMsT0FBTyxFQUFFLHVCQUF1QixFQUFFRSwyQkFBMkIsQ0FBQztBQUM3RUosQ0FBQyxDQUFDQyxRQUFRLENBQUMsQ0FBQ0MsRUFBRSxDQUFDLE9BQU8sRUFBRSw0QkFBNEIsRUFBRUUsMkJBQTJCLENBQUM7QUFDbEZKLENBQUMsQ0FBQ0MsUUFBUSxDQUFDLENBQUNJLEtBQUssQ0FBQyxZQUFVLENBQzVCLENBQUMsQ0FBQztBQUNGLFNBQVNELDJCQUEyQkEsQ0FBQSxFQUFFO0VBQ2xDSixDQUFDLENBQUMsa0JBQWtCLENBQUMsQ0FBQ00sT0FBTyxDQUFDO0lBQzFCQyxJQUFJLEVBQUU7RUFDVixDQUFDLEVBQUUsR0FBRyxFQUFFLFlBQVU7SUFDZFAsQ0FBQyxDQUFDLGtCQUFrQixDQUFDLENBQUNRLEdBQUcsQ0FBQyxTQUFTLEVBQUUsTUFBTSxDQUFDO0VBQ2hELENBQUMsQ0FBQztBQUNOO0FBQ0EsU0FBU0wsMEJBQTBCQSxDQUFBLEVBQUU7RUFDakNILENBQUMsQ0FBQyxrQkFBa0IsQ0FBQyxDQUFDUSxHQUFHLENBQUMsU0FBUyxFQUFFLE9BQU8sQ0FBQyxDQUFDQSxHQUFHLENBQUMsTUFBTSxFQUFFLE9BQU8sQ0FBQztFQUNsRVIsQ0FBQyxDQUFDLGtCQUFrQixDQUFDLENBQUNNLE9BQU8sQ0FBQztJQUMxQkMsSUFBSSxFQUFFO0VBQ1YsQ0FBQyxFQUFFLEdBQUcsQ0FBQztBQUNYIiwiZmlsZSI6Ii4vcmVzb3VyY2VzL2pzL2NsaWVudC9sYXlvdXRzL21lbnUuanMuanMiLCJzb3VyY2VSb290IjoiIn0=\n//# sourceURL=webpack-internal:///./resources/js/client/layouts/menu.js\n");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval-source-map devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./resources/js/client/layouts/menu.js"]();
/******/ 	
/******/ })()
;