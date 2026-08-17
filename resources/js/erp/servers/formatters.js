export const formatBytes = (value) => {
    if (value === null || value === undefined || Number.isNaN(Number(value))) return '-';
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let number = Number(value);
    let unit = 0;
    const sign = number < 0 ? '-' : '';
    number = Math.abs(number);
    while (number >= 1024 && unit < units.length - 1) {
        number /= 1024;
        unit += 1;
    }
    return `${sign}${number.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`;
};

export const formatSignedBytes = (value) => {
    if (value === null || value === undefined) return '-';
    return `${Number(value) >= 0 ? '+' : ''}${formatBytes(value)}`;
};

export const formatNumber = (value, suffix = '') => {
    if (value === null || value === undefined || Number.isNaN(Number(value))) return '-';
    return `${Number(value).toLocaleString('es-CO', { maximumFractionDigits: 2 })}${suffix}`;
};

export const formatDateTime = (value) => {
    if (!value) return '-';
    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? '-'
        : date.toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'short' });
};

export const escapeHtml = (value) => String(value ?? '-').replace(/[&<>'"]/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    "'": '&#039;',
    '"': '&quot;'
}[character]));