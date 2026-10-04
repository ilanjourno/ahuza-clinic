import Article from "@/Interfaces/Article";
import PaginatedData from "@/Interfaces/PaginatedData";
import { useLazyGetArticlesQuery } from "@/Services/articles";
import { PageProps } from "@/types";
import { router, usePage } from "@inertiajs/react";
import { FC, useEffect, useState } from "react";
import { Button, Col, Container, Form, Row } from "react-bootstrap";
import { useInView } from "react-intersection-observer";
import TextInput from "../Form/TextInput";
import ArticleItem from "../molecules/ArticleItem";

const ArticleTable: FC = () => {
    const { articles, filters } = usePage<
        PageProps<{
            articles: PaginatedData<Article>;
            filters: { search?: string };
        }>
    >().props;
    const [getArticlesLazy, { data }] = useLazyGetArticlesQuery();
    const [hasMore, setHasMore] = useState<boolean>(
        articles.meta.current_page !== articles.meta.last_page
    );
    const [articleList, setArticleList] = useState<Article[]>(articles.data);

    const [values, setValues] = useState({
        search: filters.search || "",
        page: articles.meta.current_page,
    });

    const { ref, inView } = useInView();

    const handleChange = (e: any) => {
        const name = e.target.name;
        const value = e.target.value;

        setValues((values) => ({
            ...values,
            [name]: value,
            page: 1,
        }));

        if (name === "search") {
            router.get(
                route(route().current() as string),
                { search: value },
                {
                    replace: true,
                    preserveState: true,
                    onSuccess: (page) => {
                        const newArticles = (page as any).props.articles.data;
                        const condition: boolean = Boolean(
                            value === "" && data
                        );
                        setArticleList([
                            ...newArticles,
                            ...(condition ? data!.data : []),
                        ]);
                        setHasMore(
                            condition
                                ? data!.meta.current_page !==
                                      data!.meta.last_page
                                : (page as any).props.articles.meta
                                      .current_page !==
                                      (page as any).props.articles.meta
                                          .last_page
                        );
                    },
                }
            );
        }
    };

    useEffect(() => {
        if (inView && hasMore) {
            getArticlesLazy({
                ...values,
                page: values.page + 1,
            })
                .unwrap()
                .then((response) => {
                    setArticleList((articleList) => [
                        ...articleList,
                        ...response.data,
                    ]);
                    setValues((values) => ({
                        ...values,
                        page: response.meta.current_page,
                    }));
                    setHasMore(
                        response.meta.current_page !== response.meta.last_page
                    );
                });
        }
    }, [inView]);

    return (
        <section className="mb-xl-9 my-4">
            <Container>
                <Row className="mb-5">
                    <Col lg={5} md={7}>
                        <Row className="g-2 g-sm-3 align-items-center">
                            <Col lg={6} md={6}>
                                <Form>
                                    <Form.Label
                                        htmlFor="searchInput"
                                        className="visually-hidden"
                                    >
                                        Search Blog
                                    </Form.Label>
                                    <TextInput
                                        size="sm"
                                        name="search"
                                        autoComplete="off"
                                        className="shadow-none"
                                        placeholder="Rechercher ..."
                                        value={values.search}
                                        onChange={handleChange}
                                    />
                                </Form>
                            </Col>
                            <Col lg={6} md={6}></Col>
                        </Row>
                    </Col>
                </Row>

                <Row className="gy-lg-7 gy-5">
                    {articleList.map((article, index) => (
                        <ArticleItem
                            key={article.id}
                            article={article}
                            eager={index < 3}
                        />
                    ))}
                </Row>

                <div ref={ref} className="mt-xl-7 mt-3">
                    {hasMore && (
                        <Button variant="outline-primary">
                            <span
                                className="spinner-border spinner-border-sm"
                                role="status"
                                aria-hidden="true"
                            ></span>
                            <span className="ms-2">Load More</span>
                        </Button>
                    )}
                </div>
            </Container>
        </section>
    );
};

export default ArticleTable;
