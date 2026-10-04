// Import des images des drapeaux
import Flag from "../atoms/Flag";
import { usePage } from "@inertiajs/react";
import { PageProps } from "@/types";
import Article from "@/Interfaces/Article";

const FlagSelect = () => {
    const { locale, article, availableLocales } = usePage<
        PageProps<{
            article?: Article;
            availableLocales?: string[];
        }>
    >().props;

    // On an article page, only offer the translations that exist.
    const languages = [
        { code: "fr", label: "Français" },
        { code: "en", label: "English" },
        { code: "he", label: "עברית" },
    ].filter(
        (lang) => !availableLocales || availableLocales.includes(lang.code)
    );

    return (
        <div className="d-flex gap-2 align-items-center">
            {languages.map((lang) => (
                <a
                    key={lang.code}
                    href={route(route().current() as string, {
                        ...(article && { article: article.slug }),
                        locale: lang.code,
                    })}
                    className={`flag-link ${locale === lang.code ? "active" : ""}`}
                    title={lang.label}
                    style={{
                        opacity: locale === lang.code ? 1 : 0.6,
                        transition: "opacity 0.2s",
                        cursor: "pointer",
                    }}
                    onMouseEnter={(e) => (e.currentTarget.style.opacity = "1")}
                    onMouseLeave={(e) =>
                        (e.currentTarget.style.opacity =
                            locale === lang.code ? "1" : "0.6")
                    }
                >
                    <Flag lang={lang.code} />
                </a>
            ))}
        </div>
    );
};

export default FlagSelect;
