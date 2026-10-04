import OgImage from "@/Assets/images/og-image.jpg";
import Footer from "@/Components/Layouts/Footer";
import Navigation, { NavbarProps } from "@/Components/Layouts/Navbar";
import LANGUES from "@/Constants/langues";
import {
    escapeAttr,
    metaDescription,
    ogLocale,
    pageTitle,
} from "@/Helpers/seo";
import useTranslation from "@/Hooks/useTranslation";
import { PageProps } from "@/types";
import { Head, usePage } from "@inertiajs/react";
import { Fragment, PropsWithChildren } from "react";

export default function GuestLayout({
    children,
    transparent,
    variant = "dark",
    title,
    description,
    keywords,
    imageUrl,
    ogType = "website",
    alternateLocales = LANGUES.map((langue) => langue.value),
    publishedTime,
    modifiedTime,
}: PropsWithChildren<{
    transparent?: boolean;
    variant?: NavbarProps["variant"];
    description?: string;
    keywords?: string[];
    title?: string;
    imageUrl?: string;
    ogType?: "website" | "article";
    alternateLocales?: string[];
    publishedTime?: string | null;
    modifiedTime?: string | null;
}>) {
    const { t, locale } = useTranslation();
    const { ziggy } = usePage<PageProps>().props;
    const currentRoute = route().current() as string;
    const currentUrl = route(currentRoute, route().routeParams);
    const urlIn = (lang: string) =>
        route(currentRoute, { ...route().routeParams, locale: lang });
    const defaultLocale = alternateLocales.includes("fr")
        ? "fr"
        : alternateLocales[0];
    const summary = metaDescription(description || t("metaDescription"));

    // Inertia's <Head> does not escape values: every text goes through escapeAttr.
    return (
        <Fragment>
            <Head>
                <title>
                    {escapeAttr(pageTitle(title, t("name"), t("title")))}
                </title>
                <meta name="description" content={escapeAttr(summary)} />
                <meta
                    name="keywords"
                    content={escapeAttr(
                        keywords ? keywords.join(", ") : t("keywords")
                    )}
                />
                <link rel="canonical" href={currentUrl} />
                <meta
                    name="viewport"
                    content="width=device-width, initial-scale=1.0"
                />
                <meta
                    property="og:title"
                    content={escapeAttr(title ?? t("title"))}
                />
                <meta property="og:description" content={escapeAttr(summary)} />
                <meta property="og:url" content={currentUrl} />
                <meta property="og:type" content={ogType} />
                <meta
                    property="og:image"
                    content={escapeAttr(
                        imageUrl ?? new URL(OgImage, ziggy.url).href
                    )}
                />
                <meta property="og:locale" content={ogLocale(locale)} />
                {publishedTime && (
                    <meta
                        property="article:published_time"
                        content={publishedTime}
                    />
                )}
                {modifiedTime && (
                    <meta
                        property="article:modified_time"
                        content={modifiedTime}
                    />
                )}
                {alternateLocales.map((lang) => (
                    <link
                        key={lang}
                        rel="alternate"
                        hrefLang={lang}
                        href={urlIn(lang)}
                    />
                ))}
                <link
                    rel="alternate"
                    hrefLang="x-default"
                    href={urlIn(defaultLocale)}
                />
            </Head>
            <Navigation transparent={transparent} variant={variant} />
            {children}
            <Footer />
        </Fragment>
    );
}
