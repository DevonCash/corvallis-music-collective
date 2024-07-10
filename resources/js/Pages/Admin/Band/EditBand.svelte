<script>
    import { SortableList } from "@jhubbardsf/svelte-sortablejs";
    import { createForm } from "felte";

    export let band;
    const { form, isDirty, data } = createForm({
        initialValues: band,
    });

    const removeLink = (idx) => () => {
        data.update((values) => {
            values.links.splice(idx, 1);
            return values;
        });
    };

    const addLink = () => {
        data.update((values) => {
            values.links.push({ label: "", url: "" });
            return values;
        });
    };

    const reorderLink = (idx, tgt) => {
        data.update((values) => {
            values.links.splice(tgt, 0, values.links.splice(idx, 1)[0]);
            return values;
        });
    };
</script>

{@debug $data}

<form use:form>
    <header>
        <h2>Edit Band</h2>
        <menu>
            <button disabled={!$isDirty} type="reset">Reset</button>
            <button disabled={!$isDirty} type="submit">Save</button>
        </menu>
    </header>

    <fieldset>
        <legend><h3>Public Profile</h3></legend>
        <div class="grid">
            <label>
                Name
                <input type="text" name="name" />
            </label>
            <label>
                Home City
                <input type="text" name="home_city" />
            </label>
        </div>
        <label>
            Description
            <textarea name="description"></textarea>
        </label>

        <fieldset class="links">
            <legend>Links</legend>
            <ul>
                {#each $data.links as link, idx (link.key)}
                    <li>
                        <label>
                            Label
                            <input type="text" name="links.{idx}.label" />
                        </label>
                        <label>
                            URL
                            <input type="url" name="links.{idx}.url" />
                        </label>
                        <button
                            class="secondary outline"
                            on:click={removeLink(idx)}>Remove</button
                        >
                    </li>
                {/each}
            </ul>
            <button class="outline" on:click={addLink}>Add Link</button>
        </fieldset>
    </fieldset>
</form>

<!--
{
  "band": {
    "id": 1,
    "name": "veniam iure",
    "description": "Ut suscipit quas rerum quisquam provident natus necessitatibus et earum dolore libero nobis ab est nesciunt velit deserunt natus maxime qui dolorem.",
    "links": [
      {
        "url": "https://lemke.com/a-rerum-et-nisi-est-incidunt-libero-architecto.html",
        "label": "qui"
      },
      {
        "url": "http://www.lueilwitz.com/accusamus-dolorum-cupiditate-ea-quis-laudantium.html",
        "label": "ut"
      },
      {
        "url": "http://www.lesch.com/earum-iste-cupiditate-vero",
        "label": "vero"
      }
    ],
    "created_at": "2024-07-08T01:35:17.000000Z",
    "updated_at": "2024-07-08T01:35:17.000000Z",
    "deleted_at": null,
    "published_at": "2016-09-21 09:22:49",
    "home_city": "excepturi",
    "tags": [
      "velit",
      "est",
      "blanditiis"
    ]
  }
} -->

<style>
    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    header menu {
        display: flex;
        gap: 0.66rem;
    }

    .links li {
        display: flex;
        gap: 0.66rem;
    }

    .links > button {
        width: 100%;
    }
</style>
