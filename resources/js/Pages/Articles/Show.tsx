import { formatDate, parseDate } from "@/Helpers/dates";
import { demoteHeadings } from "@/Helpers/seo";
import useTranslation from "@/Hooks/useTranslation";
import Article from "@/Interfaces/Article";
import GuestLayout from "@/Layouts/GuestLayout";
import { PageProps } from "@/types";
import { FC } from "react";
import { Col, Container, Row } from "react-bootstrap";

const ArticleShow: FC<
    PageProps<{
        article: Article;
        availableLocales: string[];
    }>
> = ({ article, availableLocales }) => {
    const { t, locale } = useTranslation();
    const traduction = article.traductions[0].traduction;
    const published = formatDate(article.published_at, locale);
    const updated = formatDate(article.updated_at, locale);

    return (
        <GuestLayout
            variant="light"
            transparent={false}
            title={traduction.title}
            description={traduction.description}
            keywords={article.keywords.map((keyword) => keyword.label)}
            imageUrl={traduction.illustration?.link}
            alternateLocales={availableLocales}
            ogType="article"
            publishedTime={parseDate(article.published_at)?.toISO()}
            modifiedTime={parseDate(article.updated_at)?.toISO()}
        >
            <div className="pattern-square"></div>
            <div className="py-xl-9 py-4">
                <Container>
                    <Row>
                        <Col lg={8} className="offset-lg-2">
                            <header className="mb-5">
                                <h1 className="mb-3">{traduction.title}</h1>
                                <p className="mb-0">
                                    <a
                                        href={
                                            route("welcome", { locale }) +
                                            "#about"
                                        }
                                    >
                                        {t("article.byline")}
                                    </a>
                                </p>
                                <p className="small mb-0">
                                    {published &&
                                        t("article.published", {
                                            date: published,
                                        })}
                                    {updated && updated !== published && (
                                        <>
                                            {" · "}
                                            {t("article.updated", {
                                                date: updated,
                                            })}
                                        </>
                                    )}
                                </p>
                            </header>
                            <article className="article-body"
                                dangerouslySetInnerHTML={{
                                    __html: demoteHeadings(traduction.article),
                                }}
                            />
                        </Col>
                    </Row>
                </Container>
            </div>
        </GuestLayout>
    );
};

export default ArticleShow;
