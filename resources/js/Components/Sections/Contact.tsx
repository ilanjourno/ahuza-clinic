import Avatar from "@/Assets/images/avatar.jpg";
import { whatsappUrl } from "@/Constants/contact";
import useTranslation from "@/Hooks/useTranslation";
import { ContactForm } from "@/Interfaces/Contact";
import { Transition } from "@headlessui/react";
import { useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";
import {
    Alert,
    Button,
    Card,
    Col,
    Container,
    Form,
    Row,
} from "react-bootstrap";

export default function Contact() {
    const { t } = useTranslation();
    const {
        data,
        setData,
        post,
        errors,
        processing,
        recentlySuccessful,
        wasSuccessful,
    } = useForm<ContactForm>({
        fullname: "",
        email: "",
        message: "",
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        return post(route("contact.store"), {
            preserveScroll: true,
        });
    };

    return (
        <section id="contact">
            <Container>
                <Row className="row g-lg-10 gy-5 align-items-center">
                    <Col md={6}>
                        <Card bg="light">
                            <Card.Body className="p-5">
                                <h3 className="mb-4">{t("contact.title")}</h3>
                                {wasSuccessful && (
                                    <Alert variant={"success"}>
                                        {t("contact.messageAlert")}
                                    </Alert>
                                )}
                                <Form noValidate onSubmit={submit}>
                                    <Form.Group className="mb-3">
                                        <Form.Label htmlFor="fullname">
                                            {t("contact.fullname")}
                                        </Form.Label>
                                        <Form.Control
                                            type="text"
                                            id="fullname"
                                            required
                                            isInvalid={!!errors.fullname}
                                            value={data.fullname}
                                            onChange={(e) =>
                                                setData(
                                                    "fullname",
                                                    e.target.value
                                                )
                                            }
                                        />
                                        <Form.Control.Feedback type="invalid">
                                            {errors.fullname}
                                        </Form.Control.Feedback>
                                    </Form.Group>
                                    <Form.Group className="mb-3">
                                        <Form.Label htmlFor="email">
                                            {t("contact.emailLabel")}
                                        </Form.Label>
                                        <Form.Control
                                            type="email"
                                            className="form-control"
                                            id="email"
                                            required
                                            isInvalid={!!errors.email}
                                            value={data.email}
                                            onChange={(e) =>
                                                setData("email", e.target.value)
                                            }
                                        />
                                        <Form.Control.Feedback type="invalid">
                                            {errors.email}
                                        </Form.Control.Feedback>
                                    </Form.Group>
                                    <Form.Group className="mb-3">
                                        <Form.Label
                                            htmlFor="messageTextarea"
                                            className="form-label"
                                        >
                                            {t("contact.message")}
                                        </Form.Label>
                                        <Form.Control
                                            as="textarea"
                                            id="messageTextarea"
                                            rows={4}
                                            required
                                            isInvalid={!!errors.message}
                                            value={data.message}
                                            onChange={(e) =>
                                                setData(
                                                    "message",
                                                    e.target.value
                                                )
                                            }
                                        ></Form.Control>
                                        <Form.Control.Feedback type="invalid">
                                            {errors.message}
                                        </Form.Control.Feedback>
                                    </Form.Group>
                                    <Button
                                        variant="primary"
                                        type="submit"
                                        disabled={processing}
                                    >
                                        {t("contact.send")}
                                    </Button>
                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-gray-600">
                                            Saved.
                                        </p>
                                    </Transition>
                                </Form>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={6}>
                        <div className="mb-7 text-center text-lg-start">
                            <div className="mb-3">
                                <img
                                    src={Avatar}
                                    alt="avatar"
                                    className="avatar avatar-xl rounded-circle"
                                />
                            </div>

                            <h3 className="mb-0">Dr. Claude Allouche</h3>
                            <small>{t("contact.job")}</small>
                        </div>
                        <div className="d-flex mb-4">
                            <div>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    fill="currentColor"
                                    className="bi bi-geo-alt-fill"
                                    viewBox="0 0 16 16"
                                >
                                    <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z" />
                                </svg>
                            </div>
                            <div className="ms-2">
                                <h5 className="mb-0">{t("contact.address")}</h5>
                                <small className="d-block">
                                    {t("contact.addressRaanana")}
                                </small>
                                <small className="d-block">
                                    {t("contact.addressNetanya")}
                                </small>
                            </div>
                        </div>
                        <div className="d-flex mb-4">
                            <div>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    fill="currentColor"
                                    className="bi bi-telephone"
                                    viewBox="0 0 16 16"
                                >
                                    <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z" />
                                </svg>
                            </div>
                            <div className="ms-2">
                                <h5 className="mb-0">{t("contact.phone")}</h5>
                                <small>
                                    <a
                                        href={whatsappUrl(t("whatsapp.message"))}
                                    >
                                        058-726-0264
                                    </a>
                                </small>
                            </div>
                        </div>
                        <div className="d-flex mb-4">
                            <div>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    fill="currentColor"
                                    className="bi bi-envelope-check"
                                    viewBox="0 0 16 16"
                                >
                                    <path d="M2 2a2 2 0 0 0-2 2v8.01A2 2 0 0 0 2 14h5.5a.5.5 0 0 0 0-1H2a1 1 0 0 1-.966-.741l5.64-3.471L8 9.583l7-4.2V8.5a.5.5 0 0 0 1 0V4a2 2 0 0 0-2-2H2Zm3.708 6.208L1 11.105V5.383l4.708 2.825ZM1 4.217V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v.217l-7 4.2-7-4.2Z" />
                                    <path d="M16 12.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Zm-1.993-1.679a.5.5 0 0 0-.686.172l-1.17 1.95-.547-.547a.5.5 0 0 0-.708.708l.774.773a.75.75 0 0 0 1.174-.144l1.335-2.226a.5.5 0 0 0-.172-.686Z" />
                                </svg>
                            </div>
                            <div className="ms-2">
                                <h5 className="mb-0">{t("contact.email")}</h5>

                                <small>
                                    <a
                                        href="mailto:drallouchegyneco@gmail.com"
                                        className="text-reset"
                                    >
                                        drallouchegyneco@gmail.com
                                    </a>
                                </small>
                            </div>
                        </div>
                        <div className="d-flex gap-2">
                            <a
                                href="https://www.facebook.com/DrAllouche2020"
                                className="btn btn-facebook btn-icon"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    fill="currentColor"
                                    className="bi bi-facebook"
                                    viewBox="0 0 16 16"
                                >
                                    <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z" />
                                </svg>
                            </a>
                            <a
                                href="https://www.linkedin.com/in/claude-allouche-776ba719b/"
                                target="_blank"
                                className="text-reset btn btn-linkedin btn-icon"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    fill="currentColor"
                                    className="bi bi-linkedin"
                                    viewBox="0 0 16 16"
                                >
                                    <path d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854V1.146zm4.943 12.248V6.169H2.542v7.225h2.401zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248-.822 0-1.359.54-1.359 1.248 0 .694.521 1.248 1.327 1.248h.016zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016a5.54 5.54 0 0 1 .016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225h2.4z" />
                                </svg>
                            </a>
                        </div>
                    </Col>
                </Row>
            </Container>
        </section>
    );
}
