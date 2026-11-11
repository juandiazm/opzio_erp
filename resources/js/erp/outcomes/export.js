import { getOutcomeFilters } from './list.js';

export function exportOutcomes(){
    const query = new URLSearchParams(getOutcomeFilters());
    window.location.assign('/admin/outcomes/export?'+query.toString());
}
