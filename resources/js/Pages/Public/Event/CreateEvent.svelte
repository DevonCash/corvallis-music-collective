<script>
    import { inertia } from "@inertiajs/svelte";

    export let csrf;
    export let errors;
    let posterPreview = "";

    let message = null;

    function onChange(event) {
        if (event.target.files && event.target.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                posterPreview = e.target.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    }

    function onSubmit(ev) {
        ev.preventDefault();
        const form = ev.target;
        form.classList.add("loading");
        const formData = new FormData(form);
        console.log(formData.getAll("tags"));
        fetch("/events", {
            method: "POST",
            body: formData,
        })
            .then((res) => {
                if (res.status > 299) {
                    throw new Error(res);
                }
                form.classList.remove("loading");
                form.reset();
                posterPreview = "";
                message = {
                    type: "success",
                    text: "Event submitted successfully!",
                };
            })
            .catch((error) => {
                form.classList.remove("loading");
                message = {
                    type: "error",
                    text: "There was an error submitting your event, please try again later.",
                };
                console.error("Error:", error);
            })
            .finally(() => {
                form.classList.remove("loading");
                setTimeout(() => {
                    message = "";
                }, 5000);
            });
    }
</script>

{@debug errors}
<main>
    <article>
        <h2>Submit Community Event</h2>
        <p>
            Have an event you'd like to share? Fill out the form below to submit
            your event to our calendar!
        </p>
        {#if message}
            <div class="alert {message.type}" role="alert">
                {message.text}
            </div>
        {/if}
        <form on:submit={onSubmit}>
            <input type="hidden" name="_token" value={csrf} />
            <section class="info grid">
                <div>
                    <label>
                        Name
                        <input required name="name" type="text" />
                    </label>
                    <div class="grid">
                        <label>
                            Date
                            <input required name="start_date" type="date" />
                        </label>
                        <label>
                            Time
                            <input required name="start_time" type="time" />
                        </label>
                    </div>
                    <label>
                        Description
                        <textarea
                            rows="5"
                            name="description"
                            required
                            placeholder="Include any relevant information about your event here, including who's playing, ticket prices, venue information, and any other details you'd like to share."
                        ></textarea>
                    </label>
                </div>
                <label>
                    Poster
                    <figure style="height: 25vh; padding: .5rem;" class="grid">
                        {#if posterPreview}
                            <img
                                style="height: 100%; width: auto; margin:auto;"
                                src={posterPreview}
                                alt="Preview of poster file upload"
                            />
                        {:else}
                            <img
                                style="filter: grayscale(.8); max-width: 80%;  margin:auto; position: relative; left: 8%; opacity: .2;"
                                aria-hidden
                                alt="Corvallis Music Collective Logo Watermark"
                                src="/logo.svg"
                            />
                        {/if}
                        <figcaption style="display: none;">
                            Preview of poster file upload
                        </figcaption>
                    </figure>
                    <input on:change={onChange} name="poster" type="file" />
                </label>
            </section>
            <section>
                <fieldset
                    class="grid"
                    style="grid-template-columns: autofill(100px, 1fr)"
                >
                    <legend style="grid-column: 1 / -1">Other Info</legend>
                    <label>
                        <input type="checkbox" name="tags[]" value="Free" />
                        Free
                    </label>
                    <label>
                        <input
                            type="checkbox"
                            name="tags[]"
                            value="Age Restricted"
                        />
                        Age Restricted
                    </label>
                    <label>
                        <input
                            type="checkbox"
                            name="tags[]"
                            value="Masks Mandatory"
                        />
                        Masks Mandatory
                    </label>

                    <label>
                        <input
                            type="checkbox"
                            name="tags[]"
                            value="Alcohol Served"
                        />
                        Alcohol Served
                    </label>
                </fieldset>
                <fieldset>
                    <legend>Community Guidelines</legend>
                    <label>
                        <input type="checkbox" name="terms" required />
                        This event complies with our
                        <a href="/events/community-events">posting guidelines</a
                        >
                    </label>
                </fieldset>
            </section>

            <button type="submit">Submit Event</button>
        </form>
    </article>
</main>

<style>
    .info.grid {
        grid-template-columns: 2fr 1fr;
    }

    label:has(figure) {
        display: flex;
        flex-direction: column;
        cursor: pointer;
    }

    label figure {
        flex: auto;
        background: var(--pico-form-element-border-color);
        border-radius: var(--pico-border-radius);
    }

    label figure img {
        max-height: 100%;
    }

    .alert {
        padding: 1rem;
        margin: 1rem 0;
        border: 4px solid currentColor;
        border-radius: var(--pico-border-radius);
    }

    .alert.success {
        background: var(--pico-success-color);
        color: var(--pico-form-element-valid-active-border-color);
    }

    .alert.error {
        background: var(--pico-error-color);
        color: var(--pico-form-element-invalid-active-border-color);
    }

    @media (max-width: 1024px) {
        .info.grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 768px) {
        .info.grid {
            grid-template-columns: 1fr;
        }
    }
</style>
