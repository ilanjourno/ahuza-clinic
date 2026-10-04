import OgImage from "@/Assets/images/og-image.jpg";
import Footer from "@/Components/Layouts/Footer";
import Navigation, { NavbarProps } from "@/Components/Layouts/Navbar";
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
}: PropsWithChildren<{
    transparent?: boolean;
    variant?: NavbarProps["variant"];
    description?: string;
    keywords?: string[];
    title?: string;
    imageUrl?: string;
}>) {
    const { t, locale } = useTranslation();
    const { ziggy } = usePage<PageProps>().props;

    return (
        <Fragment>
            <Head>
                <title>
                    {title ? `${title} - ${t("name")}` : t("title")}
                </title>
                <meta
                    name="description"
                    content={description ?? t("about.first")}
                />
                <meta
                    name="keywords"
                    content={keywords ? keywords.join(", ") : t("keywords")}
                />
                <link
                    rel="canonical"
                    href={route(route().current() as string, route().params)}
                />
                <meta
                    name="viewport"
                    content="width=device-width, initial-scale=1.0"
                />
                <meta property="og:title" content={title ?? t("title")} />
                <meta
                    property="og:description"
                    content={description ?? t("mainDescription")}
                />
                <meta
                    property="og:url"
                    content={route(route().current() as string, route().params)}
                />
                <meta property="og:type" content="website" />
                <meta
                    property="og:image"
                    content={imageUrl ?? new URL(OgImage, ziggy.url).href}
                />
                <meta property="og:locale" content={locale ?? "fr_FR"} />
                <link
                    rel="alternate"
                    hrefLang="fr"
                    href={route("welcome", { locale: "fr" })}
                />
                <link
                    rel="alternate"
                    hrefLang="en"
                    href={route("welcome", { locale: "en" })}
                />
                <link
                    rel="alternate"
                    hrefLang="he"
                    href={route("welcome", { locale: "he" })}
                />
            </Head>
            <Navigation transparent={transparent} variant={variant} />
            {children}
            <Footer />
        </Fragment>
    );
}
