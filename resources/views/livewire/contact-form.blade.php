<div class='card card-compact sm:card-normal bg-white' x-data="{open: false}">
    <form wire:submit="create" @submit="open = true">
        <div class='card-body'>
            {{$this->form}}
        </div>
        <div class='card-actions justify-end'>
            <button type='submit' class='btn btn-primary'>
                Submit
            </button>
        </div>
    </form>
    <dialog class='confirmation modal' :class="{ 'modal-open': open}" :open="open">
        <form method='dialog' class='modal-backdrop' @click="open = false">
            <button >close</button>
        </form>

        <div class='modal-box p-0'>
            <div class='card'>
                <div class='card-body'>
                    <h2 class='card-title display'>Message Sent</h2>
                    <p class='leading-loose'>Your message has been sent!<br> We'll get back to you as soon as possible.</p>
                </div>
                <div class='card-actions justify-end'>
                    <button @click="open = false" class='btn btn-primary'>OK</button>
                </div>
            </div>
        </div>
    </dialog>
    <x-filament-actions::modals/>
</div>
