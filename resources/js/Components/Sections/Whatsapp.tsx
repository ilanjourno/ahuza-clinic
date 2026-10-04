import whatsapp from "@/Assets/images/whatsapp.png";
import { whatsappUrl } from "@/Constants/contact";
import useTranslation from "@/Hooks/useTranslation";
import { Image } from "react-bootstrap";

export default function Whatsapp() {
    const { t } = useTranslation();

    return (
        <section className="my-9">
            <div className="container">
                <div className="row line-pattern bg-whatsapp-gradient rounded-3 p-7 g-0">
                    <div className="col-lg-8 offset-lg-2 z-1">
                        <div className="text-center ">
                            <Image
                                src={whatsapp}
                                width={130}
                                className="mb-5"
                            />
                            <h2 className="h1 text-white-stable text-uppercase">
                                {t("whatsapp.title")}
                            </h2>
                            <p className="text-white-50 px-lg-7 mb-5">
                                {t("whatsapp.description")}
                            </p>
                            <a
                                href={whatsappUrl(t("whatsapp.message"))}
                                className="btn btn-dark"
                            >
                                058-726-0264
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
