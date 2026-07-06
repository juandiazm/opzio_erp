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

/***/ "./resources/js/client/layouts/app.js":
/*!********************************************!*\
  !*** ./resources/js/client/layouts/app.js ***!
  \********************************************/
/***/ (() => {

eval("$(document).on('click', '#close-session', closeSession);\n//event when window size change\n$(window).resize(function () {\n  //get size of header menu\n  var headerHeight = $('#menu-nav').height();\n  var windowHeight = $(window).height();\n  //set left menu height\n  $('#client-app-sidebar').css('height', windowHeight - headerHeight).css('max-height', windowHeight - headerHeight);\n  //set app content height\n  $('#client-app-content').css('height', windowHeight - headerHeight).css('max-height', windowHeight - headerHeight);\n});\n$(document).ready(function () {\n  $(window).resize();\n});\nfunction closeSession() {\n  PostMethodFunction('/client/profile/close-session', {}, null, function () {\n    window.location.href = \"/\";\n  }, null);\n}\n//on image load error\nfunction onImageError(image) {\n  image.src = '/images/no-image.png';\n}//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyIkIiwiZG9jdW1lbnQiLCJvbiIsImNsb3NlU2Vzc2lvbiIsIndpbmRvdyIsInJlc2l6ZSIsImhlYWRlckhlaWdodCIsImhlaWdodCIsIndpbmRvd0hlaWdodCIsImNzcyIsInJlYWR5IiwiUG9zdE1ldGhvZEZ1bmN0aW9uIiwibG9jYXRpb24iLCJocmVmIiwib25JbWFnZUVycm9yIiwiaW1hZ2UiLCJzcmMiXSwic291cmNlcyI6WyJ3ZWJwYWNrOi8vLy4vcmVzb3VyY2VzL2pzL2NsaWVudC9sYXlvdXRzL2FwcC5qcz9hODZjIl0sInNvdXJjZXNDb250ZW50IjpbIiQoZG9jdW1lbnQpLm9uKCdjbGljaycsICcjY2xvc2Utc2Vzc2lvbicsIGNsb3NlU2Vzc2lvbik7XHJcbi8vZXZlbnQgd2hlbiB3aW5kb3cgc2l6ZSBjaGFuZ2VcclxuJCh3aW5kb3cpLnJlc2l6ZShmdW5jdGlvbigpe1xyXG4gICAgLy9nZXQgc2l6ZSBvZiBoZWFkZXIgbWVudVxyXG4gICAgdmFyIGhlYWRlckhlaWdodCA9ICQoJyNtZW51LW5hdicpLmhlaWdodCgpO1xyXG4gICAgdmFyIHdpbmRvd0hlaWdodCA9ICQod2luZG93KS5oZWlnaHQoKTtcclxuICAgIC8vc2V0IGxlZnQgbWVudSBoZWlnaHRcclxuICAgICQoJyNjbGllbnQtYXBwLXNpZGViYXInKS5jc3MoJ2hlaWdodCcsIHdpbmRvd0hlaWdodCAtIGhlYWRlckhlaWdodCkuY3NzKCdtYXgtaGVpZ2h0Jywgd2luZG93SGVpZ2h0IC0gaGVhZGVySGVpZ2h0KTtcclxuICAgIC8vc2V0IGFwcCBjb250ZW50IGhlaWdodFxyXG4gICAgJCgnI2NsaWVudC1hcHAtY29udGVudCcpLmNzcygnaGVpZ2h0Jywgd2luZG93SGVpZ2h0IC0gaGVhZGVySGVpZ2h0KS5jc3MoJ21heC1oZWlnaHQnLCB3aW5kb3dIZWlnaHQgLSBoZWFkZXJIZWlnaHQpO1xyXG59KTtcclxuJChkb2N1bWVudCkucmVhZHkoZnVuY3Rpb24oKXtcclxuICAgICQod2luZG93KS5yZXNpemUoKTtcclxufSk7XHJcbmZ1bmN0aW9uIGNsb3NlU2Vzc2lvbigpe1xyXG4gICAgUG9zdE1ldGhvZEZ1bmN0aW9uKCcvY2xpZW50L3Byb2ZpbGUvY2xvc2Utc2Vzc2lvbicse30sbnVsbCxmdW5jdGlvbigpe1xyXG4gICAgICAgIHdpbmRvdy5sb2NhdGlvbi5ocmVmID0gXCIvXCI7XHJcbiAgICB9LG51bGwpO1xyXG59XHJcbi8vb24gaW1hZ2UgbG9hZCBlcnJvclxyXG5mdW5jdGlvbiBvbkltYWdlRXJyb3IoaW1hZ2Upe1xyXG4gICAgaW1hZ2Uuc3JjID0gJy9pbWFnZXMvbm8taW1hZ2UucG5nJztcclxufVxyXG4iXSwibWFwcGluZ3MiOiJBQUFBQSxDQUFDLENBQUNDLFFBQVEsQ0FBQyxDQUFDQyxFQUFFLENBQUMsT0FBTyxFQUFFLGdCQUFnQixFQUFFQyxZQUFZLENBQUM7QUFDdkQ7QUFDQUgsQ0FBQyxDQUFDSSxNQUFNLENBQUMsQ0FBQ0MsTUFBTSxDQUFDLFlBQVU7RUFDdkI7RUFDQSxJQUFJQyxZQUFZLEdBQUdOLENBQUMsQ0FBQyxXQUFXLENBQUMsQ0FBQ08sTUFBTSxDQUFDLENBQUM7RUFDMUMsSUFBSUMsWUFBWSxHQUFHUixDQUFDLENBQUNJLE1BQU0sQ0FBQyxDQUFDRyxNQUFNLENBQUMsQ0FBQztFQUNyQztFQUNBUCxDQUFDLENBQUMscUJBQXFCLENBQUMsQ0FBQ1MsR0FBRyxDQUFDLFFBQVEsRUFBRUQsWUFBWSxHQUFHRixZQUFZLENBQUMsQ0FBQ0csR0FBRyxDQUFDLFlBQVksRUFBRUQsWUFBWSxHQUFHRixZQUFZLENBQUM7RUFDbEg7RUFDQU4sQ0FBQyxDQUFDLHFCQUFxQixDQUFDLENBQUNTLEdBQUcsQ0FBQyxRQUFRLEVBQUVELFlBQVksR0FBR0YsWUFBWSxDQUFDLENBQUNHLEdBQUcsQ0FBQyxZQUFZLEVBQUVELFlBQVksR0FBR0YsWUFBWSxDQUFDO0FBQ3RILENBQUMsQ0FBQztBQUNGTixDQUFDLENBQUNDLFFBQVEsQ0FBQyxDQUFDUyxLQUFLLENBQUMsWUFBVTtFQUN4QlYsQ0FBQyxDQUFDSSxNQUFNLENBQUMsQ0FBQ0MsTUFBTSxDQUFDLENBQUM7QUFDdEIsQ0FBQyxDQUFDO0FBQ0YsU0FBU0YsWUFBWUEsQ0FBQSxFQUFFO0VBQ25CUSxrQkFBa0IsQ0FBQywrQkFBK0IsRUFBQyxDQUFDLENBQUMsRUFBQyxJQUFJLEVBQUMsWUFBVTtJQUNqRVAsTUFBTSxDQUFDUSxRQUFRLENBQUNDLElBQUksR0FBRyxHQUFHO0VBQzlCLENBQUMsRUFBQyxJQUFJLENBQUM7QUFDWDtBQUNBO0FBQ0EsU0FBU0MsWUFBWUEsQ0FBQ0MsS0FBSyxFQUFDO0VBQ3hCQSxLQUFLLENBQUNDLEdBQUcsR0FBRyxzQkFBc0I7QUFDdEMiLCJmaWxlIjoiLi9yZXNvdXJjZXMvanMvY2xpZW50L2xheW91dHMvYXBwLmpzLmpzIiwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///./resources/js/client/layouts/app.js\n");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval-source-map devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./resources/js/client/layouts/app.js"]();
/******/ 	
/******/ })()
;