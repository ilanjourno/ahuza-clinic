import useTranslation from "@/Hooks/useTranslation";
import Icon from "../atoms/Icon";

export default function SocialNetworks() {
    const { t } = useTranslation();
    return (
        <div className="my-xl-9 my-5">
            <div className="container">
                <div className="row">
                    <div className="col-lg-10 offset-lg-1 col-12">
                        <div className="text-center mb-4 mb-lg-7">
                            <small className="text-uppercase fw-semibold ls-md">
                                {t("myNetworks")}
                            </small>
                        </div>
                        <div>
                            <div className="d-flex justify-content-center gap-5 pb-6">
                                <div>
                                    <figure className="text-center">
                                        <a
                                            href="https://www.facebook.com/DrAllouche2020"
                                            aria-label="Facebook"
                                            target="_blank"
                                        >
                                            <Icon name="facebook" />
                                        </a>
                                    </figure>
                                </div>
                                <div>
                                    <figure className="text-center">
                                        <a
                                            href="https://www.linkedin.com/in/claude-allouche-776ba719b/"
                                            aria-label="LinkedIn"
                                            target="_blank"
                                        >
                                            <Icon name="linkedin" />
                                        </a>
                                    </figure>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
