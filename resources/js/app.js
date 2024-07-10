import "../css/app.scss";
import { createInertiaApp } from "@inertiajs/svelte";

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob("./Pages/**/*.svelte", { eager: true });
    const layouts = import.meta.glob("./Layouts/**/*.svelte", { eager: true });
    let layout = null;
    const toks = name.split("/");
    for (let i = 1; i < toks.length; i++) {
      const path = `./Layouts/${toks.slice(0, -i).join("/")}.svelte`;
      layout = layouts[path] || layout;
    }
    const page = pages[`./Pages/${name}.svelte`];
    if (layout && !page.layout) return { ...page, layout: layout.default };
    return page;
  },
  setup({ el, App, props }) {
    new App({ target: el, props });
  },
});
