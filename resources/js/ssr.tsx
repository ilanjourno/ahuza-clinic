import { createInertiaApp } from "@inertiajs/react";
import createServer from "@inertiajs/react/server";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import ReactDOMServer from "react-dom/server";
import { Provider } from "react-redux";
import { RouteName } from "ziggy-js";
import { route } from "../../vendor/tightenco/ziggy";
import { store } from "./store";

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => title,
        resolve: (name) =>
            resolvePageComponent(
                `./Pages/${name}.tsx`,
                import.meta.glob("./Pages/**/*.tsx")
            ),
        setup: ({ App, props }) => {
            global.route<RouteName> = (name, params, absolute) =>
                route(name, params as any, absolute, {
                    // @ts-expect-error
                    ...page.props.ziggy,
                    // @ts-expect-error
                    location: new URL(page.props.ziggy.location),
                });

            return (
                <Provider store={store}>
                    <App {...props} />
                </Provider>
            );
        },
    })
);
