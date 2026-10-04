import whatsapp from "@/Assets/images/whatsapp.png";
import Youtube from "@/Assets/images/youtube.jpg";
import { whatsappUrl } from "@/Constants/contact";
import useTranslation from "@/Hooks/useTranslation";
import { Image } from "react-bootstrap";
import Icon from "../atoms/Icon";

export default function Main() {
    const { t } = useTranslation();

    return (
        <section className="bg-primary-dark pt-9 right-slant-shape">
            <div className="container">
                <div className="row align-items-center">
                    <div className="col-lg-5 col-12">
                        <div
                            className="text-center text-lg-start mb-7 mb-lg-0"
                            data-cues="slideInDown"
                        >
                            <div className="mb-4">
                                <h1 className="mb-5 display-5 text-white-stable">
                                    {t("name")}
                                    <br />
                                    <span className="text-pattern-line text-secondary">
                                        {t("jobname")}
                                    </span>
                                </h1>
                                <p className="mb-0 text-white-stable lead">
                                    {t("mainDescription")}
                                </p>
                            </div>
                            <div data-cues="slideInDown">
                                <a
                                    href="#contact"
                                    className="btn btn-primary me-2"
                                >
                                    {t("takeAppointment")}
                                </a>
                                <a
                                    href={whatsappUrl(t("whatsapp.message"))}
                                    className="btn btn-outline-secondary"
                                >
                                    058-726-0264
                                    <Image
                                        src={whatsapp}
                                        width={30}
                                        className="ms-1 mb-1"
                                    />
                                </a>
                            </div>
                        </div>
                    </div>
                    <div className="offset-lg-1 col-lg-6 col-12">
                        <div
                            className="position-relative z-1 pt-lg-9"
                            data-cue="slideInRight"
                        >
                            <div className="position-relative">
                                <img
                                    src={Youtube}
                                    alt="video"
                                    className="img-fluid rounded-3"
                                    width="837"
                                />
                                <a
                                    href="https://www.youtube.com/watch?si=KRTh4j0eSW5pJebF&v=gwCh30H7D3U&feature=youtu.be"
                                    target="_blank"
                                    className="play-btn glightbox position-absolute top-50 start-50 translate-middle icon-shape icon-xl rounded-circle text-primary"
                                >
                                    <Icon name="play" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
