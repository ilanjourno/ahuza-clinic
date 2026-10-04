import { Link, usePage } from "@inertiajs/react";
import { Col, Container, Row } from "react-bootstrap";
import ArticleItem from "../molecules/ArticleItem";
import { PageProps } from "@/types";
import Article from "@/Interfaces/Article";
import useTranslation from "@/Hooks/useTranslation";

export default function Blog() {
    const { t, locale } = useTranslation();
    const { articles } = usePage<
        PageProps<{
            articles: Article[];
        }>
    >().props;
    return (
        <section id="blog">
            <Container>
                <Row>
                    <Col lg={6} className="offset-lg-3">
                        <div className="text-center mb-xl-7 mb-5">
                            <h2 className="h1 mb-3">{t("articles.title")}</h2>
                            <p className="mb-0">
                                {t("articles.description")} <br />
                                <Link
                                    href={route("articles.index", {
                                        locale,
                                    })}
                                    className="text-primary"
                                >
                                    {t("articles.plus")}
                                </Link>
                            </p>
                        </div>
                    </Col>
                </Row>
                <div className="table-responsive-lg">
                    <Row className="g-5 flex-nowrap pb-4 pb-lg-0 me-5 me-lg-0">
                        {articles.map((article, index) => (
                            <ArticleItem
                                key={article.id}
                                article={article}
                                eager={index < 3}
                            />
                        ))}
                    </Row>
                </div>
                <Row>
                    <Col lg={12}>
                        <div className="mt-lg-8 mt-5">
                            <Link
                                href={route("articles.index", {
                                    locale,
                                })}
                                className="icon-link icon-link-hover text-dark"
                            >
                                {t("articles.plus")}
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    fill="currentColor"
                                    className="bi bi-arrow-right"
                                    viewBox="0 0 16 16"
                                >
                                    <path
                                        fillRule="evenodd"
                                        d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"
                                    />
                                </svg>
                            </Link>
                        </div>
                    </Col>
                </Row>
            </Container>
        </section>
    );
}
