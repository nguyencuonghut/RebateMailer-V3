const numberFormatter = new Intl.NumberFormat('en-US', {
    maximumFractionDigits: 20,
});

const numericPattern = /^-?\d+(?:\.\d+)?$/;

const normalizeNumericString = (value: string): string | null => {
    const trimmed = value.trim();

    if (trimmed === '') {
        return null;
    }

    const normalized = trimmed.replace(/,/g, '');

    return numericPattern.test(normalized) ? normalized : null;
};

export const formatImportNumber = (
    value: string | number | null | undefined,
    fallback = '-',
): string => {
    if (value === null || value === undefined || value === '') {
        return fallback;
    }

    if (typeof value === 'number') {
        return Number.isFinite(value) ? numberFormatter.format(value) : fallback;
    }

    const normalized = normalizeNumericString(value);

    if (normalized === null) {
        return value;
    }

    const numericValue = Number(normalized);

    return Number.isFinite(numericValue) ? numberFormatter.format(numericValue) : value;
};
