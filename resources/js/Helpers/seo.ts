const OG_LOCALES: Record<string, string> = {
    fr: "fr_FR",
    en: "en_US",
    he: "he_IL",
};

export const ogLocale = (locale: string): string =>
    OG_LOCALES[locale] ?? OG_LOCALES.fr;

// Inertia's <Head> serialises tags to HTML strings without escaping, so every
// value placed in an attribute or in <title> has to be escaped by hand.
export const escapeAttr = (value: string): string =>
    value
        .replace(/&/g, "&amp;")
        .replace(/"/g, "&quot;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");

export const pageTitle = (
    title: string | undefined,
    brand: string,
    fallback: string,
    max = 60
): string => {
    if (!title) return fallback;
    const branded = `${title} - ${brand}`;
    return branded.length <= max ? branded : title;
};

export const metaDescription = (text: string, max = 155): string => {
    const clean = text.replace(/\s+/g, " ").trim();
    if (clean.length <= max) return clean;
    const cut = clean.slice(0, max - 1);
    const lastSpace = cut.lastIndexOf(" ");
    return (lastSpace > 0 ? cut.slice(0, lastSpace) : cut) + "…";
};

// The article page renders its title as the only <h1>: headings typed in the
// editor move down one level.
export const demoteHeadings = (html: string): string =>
    html.replace(/<(\/?)h1(?=[\s>])/gi, "<$1h2");
