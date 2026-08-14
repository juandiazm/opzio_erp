(() => {
    const rangeSelect = document.getElementById('observability-range-select');
    const hostsContainer = document.getElementById('observability-hosts');
    const statusContainer = document.getElementById('observability-status');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const formatBytes = (value) => {
        if (value === null || value === undefined) return '-';
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let number = Number(value);
        let unit = 0;
        while (number >= 1024 && unit < units.length - 1) {
            number /= 1024;
            unit += 1;
        }
        return `${number.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`;
    };

    const formatNumber = (value, suffix = '') => {
        if (value === null || value === undefined) return '-';
        return `${Number(value).toLocaleString('es-CO')}${suffix}`;
    };

    const escapeHtml = (value) => String(value ?? '-').replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#039;',
        '"': '&quot;'
    }[character]));

    const metric = (label, value, tone = '') => `
        <div class="observability-metric ${tone}">
            <span>${escapeHtml(label)}</span>
            <strong>${escapeHtml(value)}</strong>
        </div>`;

    const renderHost = (host) => {
        const sample = host.sample || {};
        const agent = host.agent || {};
        const agentTone = agent.status === 'healthy' ? 'is-healthy' : 'is-stale';
        const projectRows = (host.projects || []).map((project) => `
            <tr>
                <td><strong>${escapeHtml(project.name)}</strong><small>${escapeHtml(project.key)}</small></td>
                <td>${formatNumber(project.requests_per_minute, '/min')}</td>
                <td>${formatNumber(project.cpu_percent, '%')}</td>
                <td>${formatBytes(project.memory_rss_bytes)}</td>
                <td>${formatNumber(project.p95_ms, ' ms')}</td>
                <td>${formatNumber(project.status_5xx)}</td>
                <td>${formatNumber(project.fpm_listen_queue)}</td>
                <td><span class="observability-pill ${project.health === 'reporting' ? 'is-healthy' : 'is-stale'}">${escapeHtml(project.health)}</span></td>
            </tr>`).join('');

        return `
            <section class="observability-host">
                <header class="observability-host-header">
                    <div>
                        <p class="observability-eyebrow">${escapeHtml(host.environment)}</p>
                        <h3>${escapeHtml(host.name)}</h3>
                        <p class="observability-host-id">${escapeHtml(host.hostname || host.key)}</p>
                    </div>
                    <div class="observability-agent ${agentTone}">
                        <span class="observability-dot"></span>
                        <div><strong>Agente ${escapeHtml(agent.status || 'stale')}</strong><small>${escapeHtml(agent.version || 'sin versión')}</small></div>
                    </div>
                </header>
                <div class="observability-metrics">
                    ${metric('CPU', formatNumber(sample.cpu_percent, '%'))}
                    ${metric('RAM disponible', formatBytes(sample.memory_available_bytes))}
                    ${metric('Disco usado', formatNumber(sample.disk_used_percent, '%'))}
                    ${metric('Carga 1m', formatNumber(sample.load1))}
                    ${metric('Spool', formatBytes(agent.spool_bytes), agent.spool_bytes > 0 ? 'is-warn' : '')}
                    ${metric('Última muestra', sample.sampled_at ? new Date(sample.sampled_at).toLocaleTimeString('es-CO') : '-')}
                </div>
                <div class="observability-table-wrap">
                    <table class="observability-table">
                        <thead><tr><th>Proyecto</th><th>Tráfico</th><th>CPU</th><th>RAM RSS</th><th>p95</th><th>5xx</th><th>FPM queue</th><th>Estado</th></tr></thead>
                        <tbody>${projectRows || '<tr><td colspan="8" class="observability-empty">No hay proyectos activos</td></tr>'}</tbody>
                    </table>
                </div>
            </section>`;
    };

    const loadSummary = async () => {
        statusContainer.textContent = 'Actualizando...';
        try {
            const response = await fetch('/admin/observability/summary', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' },
                body: JSON.stringify({ minutes: Number(rangeSelect.value) })
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const payload = await response.json();
            hostsContainer.innerHTML = (payload.hosts || []).map(renderHost).join('') || '<div class="observability-empty-state">No hay hosts configurados.</div>';
            statusContainer.textContent = `Actualizado ${new Date(payload.generated_at).toLocaleTimeString('es-CO')}`;
        } catch (error) {
            statusContainer.textContent = 'No se pudo actualizar la observabilidad';
            statusContainer.classList.add('is-error');
        }
    };

    rangeSelect.addEventListener('change', loadSummary);
    loadSummary();
    window.setInterval(loadSummary, 30000);
})();
