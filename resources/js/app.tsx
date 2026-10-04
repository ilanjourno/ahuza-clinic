import "./bootstrap";

import { createRoot, hydrateRoot } from "react-dom/client";
import { createInertiaApp } from "@inertiajs/react";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { Provider } from "react-redux";
import { store } from "./store";
import "../scss/theme.scss";

createInertiaApp({
    title: (title) => title,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob("./Pages/**/*.tsx")
        ),
    setup({ el, App, props }) {
        const render = (
            <Provider store={store}>
                <App {...props} />
            </Provider>
        );

        if (import.meta.env.DEV) {
            createRoot(el).render(render);
            return;
        }

        hydrateRoot(el, render);
    },
    progress: {
        color: "#4B5563",
    },
});
