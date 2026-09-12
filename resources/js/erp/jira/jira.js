import { initializeJiraConfiguration } from './configuration.js';
import { initializeJiraRelations } from './relations.js';
import { initializeJiraProductivity } from './productivity.js';
import { initializeJiraDashboard } from './dashboard.js';
import { initializeJiraReports } from './reports.js';

const root = document.querySelector('[data-jira-module]');
if (root) {
    initializeJiraConfiguration(root);
    const initializeTab = (selector, callback) => {
        const pane = root.querySelector(selector);
        if (!pane || pane.dataset.jiraInitialized === 'true') return;
        pane.dataset.jiraInitialized = 'true';
        callback(pane);
    };
    initializeTab('#jira-relations', initializeJiraRelations);
    initializeTab('#jira-productivity', initializeJiraProductivity);
    initializeTab('#jira-dashboard', initializeJiraDashboard);
    initializeTab('#jira-reports', initializeJiraReports);
}
