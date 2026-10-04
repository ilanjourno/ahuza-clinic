import ArticleTable from "@/Components/Table/ArticleTable";
import useTranslation from "@/Hooks/useTranslation";
import GuestLayout from "@/Layouts/GuestLayout";

export default function ArticlesView() {
    const { t } = useTranslation();

    return (
        <GuestLayout variant="light" transparent={false}>
            <div className="pattern-square"></div>
            <section className="py-5 py-lg-8">
                <div className="container">
                    <div className="row">
                        <div className="col-lg-12 col-md-12 col-12">
                            <div className="text-center">
                                <h1 className="mb-3">{t("articles.title")}</h1>
                                <p className="mb-0">
                                    {t("articles.allDescription")}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <ArticleTable />
        </GuestLayout>
    );
}
