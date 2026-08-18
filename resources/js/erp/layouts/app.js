import './sidebar.js';
import './header.js';

const tabQueryParameter = 'tab';
const mainTabSelector = '#nav-tab .nav-link';

function updateTabUrl(tabId){
	const url = new URL(window.location.href);

	if(tabId){
		url.searchParams.set(tabQueryParameter, tabId);
	}else{
		url.searchParams.delete(tabQueryParameter);
	}

	window.history.replaceState({}, document.title, url.pathname + url.search + url.hash);
}

function getTabFromUrl(){
	const tabId = new URLSearchParams(window.location.search).get(tabQueryParameter);
	const tab = tabId ? document.getElementById(tabId) : null;
	const tabContainer = document.getElementById('nav-tab');

	if(!tab || !tabContainer || !tabContainer.contains(tab) || !tab.matches('.nav-link')){
		return null;
	}

	return tab;
}

function showTabFromUrl(){
	const tab = getTabFromUrl();
	if(!tab) return;

	$(tab).tab('show');
	tab.click();
}

$(document).on('click', mainTabSelector, function(){
	updateTabUrl(this.id);
});

$(document).on('shown.bs.tab', mainTabSelector, function(event){
	updateTabUrl(event.target.id);
});

$(document).ready(showTabFromUrl);
window.addEventListener('popstate', showTabFromUrl);