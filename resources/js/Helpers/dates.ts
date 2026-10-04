import { DateTime } from "luxon";

// published_at arrives as "yyyy-MM-dd HH:mm:ss" (no cast), timestamps as ISO 8601.
export const parseDate = (
    value: string | null | undefined
): DateTime | null => {
    if (!value) return null;
    const sql = DateTime.fromSQL(value, { zone: "utc" });
    const date = sql.isValid ? sql : DateTime.fromISO(value, { zone: "utc" });
    return date.isValid ? date : null;
};

export const formatDate = (
    value: string | null | undefined,
    locale: string
): string | null =>
    parseDate(value)?.setLocale(locale).toLocaleString(DateTime.DATE_FULL) ??
    null;
