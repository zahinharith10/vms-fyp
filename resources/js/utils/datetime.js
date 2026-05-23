const TIMEZONE = 'Asia/Kuala_Lumpur';

/**
 * @param {string|Date|null|undefined} value
 * @returns {Date|null}
 */
export function parseMalaysiaDate(value) {
    if (!value) {
        return null;
    }

    const date = value instanceof Date ? value : new Date(value);

    return Number.isNaN(date.getTime()) ? null : date;
}

/**
 * @param {string|Date|null|undefined} value
 * @param {{ withSeconds?: boolean }} [options]
 * @returns {string}
 */
export function formatMalaysiaTime(value, { withSeconds = false } = {}) {
    const date = parseMalaysiaDate(value);

    if (!date) {
        return '-';
    }

    return date.toLocaleTimeString('en-MY', {
        timeZone: TIMEZONE,
        hour: '2-digit',
        minute: '2-digit',
        second: withSeconds ? '2-digit' : undefined,
        hour12: true,
    });
}

/**
 * @param {string|Date|null|undefined} value
 * @returns {string}
 */
export function formatMalaysiaDate(value) {
    const date = parseMalaysiaDate(value);

    if (!date) {
        return '-';
    }

    return date.toLocaleDateString('en-MY', {
        timeZone: TIMEZONE,
        year: 'numeric',
        month: 'numeric',
        day: 'numeric',
    });
}

/**
 * @param {string|Date|null|undefined} value
 * @param {Intl.DateTimeFormatOptions} [options]
 * @returns {string}
 */
export function formatMalaysiaDateTime(value, options = {}) {
    const date = parseMalaysiaDate(value);

    if (!date) {
        return '-';
    }

    return date.toLocaleString('en-MY', {
        timeZone: TIMEZONE,
        day: '2-digit',
        month: '2-digit',
        year: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        ...options,
    });
}
