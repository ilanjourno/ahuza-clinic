import Logo from "@/Assets/images/logo.jpg";
import { whatsappUrl } from "@/Constants/contact";
import useTranslation from "@/Hooks/useTranslation";
import { Link } from "@inertiajs/react";
import classNames from "classnames";
import { FC, Fragment, useEffect, useState } from "react";
import {
    Button,
    Container,
    Image,
    Nav,
    Navbar as NavbarBootstrap,
    Offcanvas,
} from "react-bootstrap";
import FlagSelect from "../molecules/FlagSelect";

export interface NavbarProps {
    isClone?: boolean;
    variant?: "light" | "dark";
    transparent?: boolean;
    isGuest?: boolean;
}

const Navbar: FC<NavbarProps> = ({
    isClone,
    variant = "dark",
    transparent,
    isGuest,
}) => {
    const { t, locale } = useTranslation();
    const [navbarSticky, setNavbarSticky] = useState<boolean>(false);

    const handleScroll = () => {
        if (!isClone) return;

        if (window.scrollY > 400) {
            setNavbarSticky(true);
        } else {
            setNavbarSticky(false);
        }
    };

    useEffect(() => {
        window.addEventListener("scroll", handleScroll);
        return () => {
            window.removeEventListener("scroll", handleScroll);
        };
    }, []);

    return (
        <header>
            <NavbarBootstrap
                expand="lg"
                variant={variant}
                className={classNames("w-100 fixed", {
                    "transparent navbar-transparent": transparent,
                    "navbar-stick": navbarSticky,
                    "navbar-unstick": !navbarSticky,
                    "navbar-clone": isClone,
                })}
            >
                <Container>
                    <NavbarBootstrap.Offcanvas
                        id="offcanvasNavbar"
                        aria-labelledby="offcanvasNavbarLabel"
                        placement="start"
                    >
                        <Offcanvas.Header closeButton>
                            <Offcanvas.Title id="offcanvasNavbarLabel">
                                <Link href={route("welcome", { locale })}>
                                    <Image src={Logo} alt="Logo" width={175} />
                                </Link>
                            </Offcanvas.Title>
                        </Offcanvas.Header>
                        <Offcanvas.Body>
                            <div className="d-flex flex-column mt-3 mt-lg-0 align-items-center">
                                <Button
                                    href={route("welcome", { locale }) + "#contact"}
                                    variant="light"
                                    size="sm"
                                    className="mx-2"
                                >
                                    {t("takeAppointment")}
                                </Button>
                            </div>
                            <Nav className="align-items-lg-center mx-auto">
                                <Nav.Item>
                                    <Nav.Link
                                        as={Link}
                                        href={route("welcome", { locale }) + "#"}
                                    >
                                        {t("navigation.home")}
                                    </Nav.Link>
                                </Nav.Item>
                                <Nav.Item>
                                    <Nav.Link
                                        as={Link}
                                        href={route("welcome", { locale }) + "#about"}
                                    >
                                        {t("navigation.about")}
                                    </Nav.Link>
                                </Nav.Item>
                                <Nav.Item>
                                    <Nav.Link
                                        as={Link}
                                        href={route("articles.index", {
                                            locale,
                                        })}
                                    >
                                        {t("navigation.articles")}
                                    </Nav.Link>
                                </Nav.Item>
                                <Nav.Item>
                                    <Nav.Link
                                        as={Link}
                                        href={route("welcome", { locale }) + "#faq"}
                                    >
                                        {t("navigation.faq")}
                                    </Nav.Link>
                                </Nav.Item>
                                <Nav.Item>
                                    <Nav.Link
                                        as={Link}
                                        href={route("welcome", { locale }) + "#contact"}
                                    >
                                        {t("navigation.contact")}
                                    </Nav.Link>
                                </Nav.Item>
                                {isGuest && (
                                    <Nav.Item>
                                        <FlagSelect />
                                    </Nav.Item>
                                )}
                            </Nav>
                        </Offcanvas.Body>
                    </NavbarBootstrap.Offcanvas>
                    <a
                        href={whatsappUrl(t("whatsapp.message"))}
                        className={classNames("mb-0", {
                            "text-white": variant === "dark" && transparent,
                            "text-dark": variant === "light" || !transparent,
                        })}
                    >
                        058-726-0264
                    </a>
                    <NavbarBootstrap.Toggle aria-controls="offcanvasNavbar" />
                </Container>
            </NavbarBootstrap>
        </header>
    );
};

export const Navigation: FC<{
    transparent?: NavbarProps["transparent"];
    isGuest?: boolean;
    variant?: NavbarProps["variant"];
}> = ({ transparent = true, isGuest = true, variant = "dark" }) => {
    return (
        <Fragment>
            <Navbar
                variant={variant}
                transparent={transparent}
                isGuest={isGuest}
            />
            <Navbar
                isClone
                variant="light"
                transparent={transparent}
                isGuest={isGuest}
            />
        </Fragment>
    );
};

export default Navigation;
