// Add to your resources/js/filament/edit-monitor.js
document.addEventListener("livewire:init", () => {
    // Handle browser navigation or closing tab
    window.addEventListener("beforeunload", function (event) {
        const component = Livewire.first();
        if (component) {
            // Get the current URL to pass to the handleNavigation method
            const currentPath = window.location.pathname;
            component.call("handleNavigation", currentPath);
        }
    });

    // Listen for Livewire navigation events
    document.addEventListener("livewire:navigating", (event) => {
        const component = Livewire.first();
        if (component) {
            component.call("handleNavigation", event.detail.to);
        }
    });
});
