import Avatar from "@/Assets/images/avatar.jpg";
import { formatDate, parseDate } from "@/Helpers/dates";
import useTranslation from "@/Hooks/useTranslation";
import Article from "@/Interfaces/Article";
import { Link } from "@inertiajs/react";
import classNames from "classnames";
import { FC } from "react";
import { Col, Figure, Image } from "react-bootstrap";

interface ArticleItemProps {
    article: Article;
    eager?: boolean;
}

const ArticleItem: FC<ArticleItemProps> = ({ article, eager = false }) => {
    const { locale } = useTranslation();
    const traduction = article.traductions[0].traduction;
    const href = route("articles.show", { article: article.slug, locale });

    return (
        <Col as="article" lg={4} md={6} xs={12}>
            <Figure className="mb-4 zoom-img w-100">
                <Link href={href}>
                    <Image
                        src={
                            traduction.illustration
                                ? traduction.illustration.link
                                : ""
                        }
                        width={"100%"}
                        height={400}
                        style={{ objectFit: "cover" }}
                        alt={traduction.title}
                        loading={eager ? "eager" : "lazy"}
                        decoding="async"
                        rounded
                    />
                </Link>
            </Figure>
            {article.categories.map((category, index) => (
                <span
                    key={category.id}
                    style={{
                        backgroundColor: category.background_color,
                        color: category.text_color,
                    }}
                    className={classNames("badge badge-pill", {
                        "ms-1": index > 0,
                    })}
                >
                    {category.label}
                </span>
            ))}
            <h3 className="my-3 lh-base h4">
                <Link href={href} className="text-reset">
                    {traduction.title}
                </Link>
            </h3>
            <div className="d-flex align-items-center justify-content-between mb-3 mb-md-0">
                <div className="d-flex align-items-center">
                    <Image
                        src={Avatar}
                        alt="Dr Claude Allouche"
                        className="avatar avatar-xs rounded-circle"
                    />
                    <div className="ms-2">
                        <a
                            href={route("welcome", { locale }) + "#about"}
                            className="text-reset fs-6"
                        >
                            Dr Claude Allouche
                        </a>
                    </div>
                </div>
                <div className="ms-3">
                    <time
                        className="fs-6"
                        dateTime={
                            parseDate(article.published_at)?.toISODate() ??
                            undefined
                        }
                    >
                        {formatDate(article.published_at, locale)}
                    </time>
                </div>
            </div>
        </Col>
    );
};

export default ArticleItem;
