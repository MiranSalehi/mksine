<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('mksineSidebarNavSearch', () => ({
            query: '',

            init() {
                this.$watch('query', () => this.apply())
            },

            clear() {
                this.query = ''
                this.apply()
                this.$refs.searchInput?.focus()
            },

            apply() {
                const nav = document.querySelector('.fi-main-sidebar .fi-sidebar-nav')

                if (! nav) {
                    return
                }

                const normalizedQuery = this.query.trim().toLowerCase()
                const isFiltering = normalizedQuery.length > 0

                nav.classList.toggle('mksine-sidebar-is-filtering', isFiltering)

                nav.querySelectorAll('.fi-sidebar-group').forEach((group) => {
                    const groupLabel = (
                        group.querySelector('.fi-sidebar-group-label')?.textContent ?? ''
                    )
                        .trim()
                        .toLowerCase()

                    const groupLabelMatches =
                        isFiltering && groupLabel.includes(normalizedQuery)

                    let anyItemVisible = false

                    group
                        .querySelectorAll(
                            ':scope > .fi-sidebar-group-items > .fi-sidebar-item',
                        )
                        .forEach((item) => {
                            const itemLabel = (
                                item.querySelector(
                                    ':scope > .fi-sidebar-item-btn .fi-sidebar-item-label',
                                )?.textContent ?? ''
                            )
                                .trim()
                                .toLowerCase()

                            const subItemLabels = [
                                ...item.querySelectorAll(
                                    '.fi-sidebar-sub-group-items .fi-sidebar-item-label',
                                ),
                            ].map((element) =>
                                (element.textContent ?? '').trim().toLowerCase(),
                            )

                            const matches =
                                ! isFiltering ||
                                groupLabelMatches ||
                                itemLabel.includes(normalizedQuery) ||
                                subItemLabels.some((label) =>
                                    label.includes(normalizedQuery),
                                )

                            item.classList.toggle(
                                'mksine-sidebar-item--filter-hidden',
                                ! matches,
                            )

                            if (matches) {
                                anyItemVisible = true
                            }
                        })

                    const groupVisible =
                        ! isFiltering || anyItemVisible || groupLabelMatches

                    group.classList.toggle(
                        'mksine-sidebar-group--filter-hidden',
                        ! groupVisible,
                    )
                    group.classList.toggle(
                        'mksine-sidebar-group--filter-open',
                        isFiltering && groupVisible,
                    )
                })
            },
        }))
    })
</script>
