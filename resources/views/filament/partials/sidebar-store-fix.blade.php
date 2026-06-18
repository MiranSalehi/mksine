<script>
    document.addEventListener('alpine:init', () => {
        const normalizeSidebarStore = () => {
            const store = window.Alpine?.store('sidebar');

            if (! store) {
                return;
            }

            if (! Array.isArray(store.collapsedGroups)) {
                store.collapsedGroups = [];
            }
        };

        normalizeSidebarStore();
        window.Alpine.nextTick(normalizeSidebarStore);
    });
</script>
