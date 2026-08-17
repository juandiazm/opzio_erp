export function createServersState(elements, csrfToken){
    return {
        ...elements,
        csrfToken,
        pagination: { page: 1, per_page: 10, total: 0, totalPages: 0 },
        sortState: { key: 'name', direction: 'desc' },
        filterOptionsLoaded: false,
        healthLabels: {
            reporting: 'Reportando',
            stale: 'Sin reporte reciente',
            no_data: 'Sin datos'
        }
    };
}