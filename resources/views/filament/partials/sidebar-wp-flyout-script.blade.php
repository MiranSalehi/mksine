<script>
    (() => {
        const HIDE_DELAY_MS = 200
        const ANIM_MS = 160
        let hideTimer = null
        let animTimer = null
        let activeGroup = null
        let flyoutEl = null
        let bridgeEl = null
        let flyoutGeneration = 0

        function clearHideTimer() {
            if (hideTimer) {
                clearTimeout(hideTimer)
                hideTimer = null
            }
        }

        function clearAnimTimer() {
            if (animTimer) {
                clearTimeout(animTimer)
                animTimer = null
            }
        }

        function scheduleHide() {
            clearHideTimer()
            hideTimer = setTimeout(() => hideFlyout(), HIDE_DELAY_MS)
        }

        function isFiltering() {
            return Boolean(document.querySelector('#fi-main-sidebar .mksine-sidebar-is-filtering'))
        }

        function isOpenDesktopSidebar() {
            const sidebar = document.querySelector('#fi-main-sidebar')

            return Boolean(
                sidebar
                && window.matchMedia('(min-width: 1024px)').matches
                && sidebar.classList.contains('fi-sidebar-open'),
            )
        }

        function isRtl() {
            const rootDir = document.documentElement.getAttribute('dir')
                || document.documentElement.dir
                || ''

            if (rootDir.toLowerCase() === 'rtl') {
                return true
            }

            if (rootDir.toLowerCase() === 'ltr') {
                return false
            }

            const sidebar = document.querySelector('#fi-main-sidebar')

            if (sidebar) {
                const direction = getComputedStyle(sidebar).direction

                if (direction === 'rtl' || direction === 'ltr') {
                    return direction === 'rtl'
                }
            }

            const lang = (document.documentElement.lang || '').toLowerCase()

            return lang.startsWith('fa') || lang.startsWith('ar') || lang.startsWith('ku')
        }

        function groupFromTarget(target) {
            if (! (target instanceof Element)) {
                return null
            }

            return target.closest('#fi-main-sidebar .fi-sidebar-group.fi-collapsible')
        }

        function groupItems(group) {
            return group.querySelector(':scope > .fi-sidebar-group-items')
        }

        function groupTrigger(group) {
            return group.querySelector(':scope > .fi-sidebar-group-btn')
        }

        function visibleChildItems(items) {
            return [...items.querySelectorAll(':scope > .fi-sidebar-item')].filter(
                (item) => ! item.classList.contains('mksine-sidebar-item--filter-hidden'),
            )
        }

        function childLink(item) {
            return item.querySelector(
                ':scope > a.fi-sidebar-item-btn, :scope > .fi-sidebar-item-btn[href], :scope > a[href]',
            )
        }

        function childLabel(item, link) {
            const label = item.querySelector('.fi-sidebar-item-label')

            return (label?.textContent || link?.textContent || '').trim()
        }

        function cloneChildIcon(group, href, item) {
            const sidebarIcon = item.querySelector(
                ':scope > .fi-sidebar-item-btn .fi-icon, :scope > .fi-sidebar-item-btn svg',
            )

            if (sidebarIcon) {
                return sidebarIcon.cloneNode(true)
            }

            // Open-sidebar strips item icons when the group has an icon; collapsed dropdown still has them.
            const dropdownLinks = group.querySelectorAll('.fi-dropdown a[href]')

            for (const link of dropdownLinks) {
                if (link.getAttribute('href') !== href) {
                    continue
                }

                const icon = link.querySelector('.fi-icon, svg')

                if (icon) {
                    return icon.cloneNode(true)
                }
            }

            return null
        }

        function ensureCollapsed(group) {
            group.classList.add('fi-collapsed')

            const label = group.getAttribute('data-group-label')

            if (! label) {
                return
            }

            try {
                const store = window.Alpine?.store?.('sidebar')

                if (store && typeof store.collapseGroup === 'function') {
                    store.collapseGroup(label)
                }
            } catch (error) {
                // Alpine sidebar store may not be ready yet.
            }
        }

        function classifyGroup(group) {
            const items = groupItems(group)
            const trigger = groupTrigger(group)

            group.classList.remove('mksine-nav-parent', 'mksine-nav-solo')

            // Ungrouped leaves (Dashboard, Plugins, Settings, …) live in a label-less
            // Filament group — never treat them as WP flyout parents.
            if (! items || ! trigger || isFiltering() || ! isOpenDesktopSidebar()) {
                return 'none'
            }

            const count = visibleChildItems(items).length

            if (count >= 2) {
                group.classList.add('mksine-nav-parent')
                group.dataset.mksineDir = isRtl() ? 'rtl' : 'ltr'
                ensureCollapsed(group)

                return 'parent'
            }

            if (count === 1) {
                group.classList.add('mksine-nav-solo')
                delete group.dataset.mksineDir
                ensureCollapsed(group)

                return 'solo'
            }

            delete group.dataset.mksineDir

            return 'none'
        }

        function classifyAll() {
            document
                .querySelectorAll('#fi-main-sidebar .fi-sidebar-group.fi-collapsible')
                .forEach((group) => classifyGroup(group))
        }

        function ensureFlyoutShell() {
            if (flyoutEl && bridgeEl) {
                return
            }

            flyoutEl = document.createElement('div')
            flyoutEl.className = 'mksine-wp-nav-flyout'
            flyoutEl.hidden = true
            flyoutEl.setAttribute('role', 'menu')
            flyoutEl.setAttribute('aria-hidden', 'true')

            const list = document.createElement('ul')
            list.className = 'mksine-wp-nav-flyout-list'
            flyoutEl.appendChild(list)

            bridgeEl = document.createElement('div')
            bridgeEl.className = 'mksine-wp-nav-flyout-bridge'
            bridgeEl.hidden = true

            document.body.appendChild(bridgeEl)
            document.body.appendChild(flyoutEl)

            flyoutEl.addEventListener('pointerenter', () => clearHideTimer())
            flyoutEl.addEventListener('pointerleave', (event) => {
                const related = event.relatedTarget

                if (related instanceof Node && (activeGroup?.contains(related) || bridgeEl.contains(related))) {
                    return
                }

                scheduleHide()
            })

            bridgeEl.addEventListener('pointerenter', () => clearHideTimer())
            bridgeEl.addEventListener('pointerleave', (event) => {
                const related = event.relatedTarget

                if (related instanceof Node && (activeGroup?.contains(related) || flyoutEl.contains(related))) {
                    return
                }

                scheduleHide()
            })
        }

        function hideFlyout() {
            clearHideTimer()
            clearAnimTimer()
            ensureFlyoutShell()

            const generation = ++flyoutGeneration

            flyoutEl.classList.remove('is-open')
            flyoutEl.setAttribute('aria-hidden', 'true')
            bridgeEl.hidden = true

            document
                .querySelectorAll('.fi-sidebar-group.mksine-nav-flyout-open')
                .forEach((group) => group.classList.remove('mksine-nav-flyout-open'))

            activeGroup = null

            animTimer = setTimeout(() => {
                if (generation !== flyoutGeneration) {
                    return
                }

                flyoutEl.hidden = true
                flyoutEl.querySelector('ul')?.replaceChildren()
            }, ANIM_MS)
        }

        function positionFlyout(trigger) {
            ensureFlyoutShell()

            const rect = trigger.getBoundingClientRect()
            const gap = 6
            const width = Math.max(220, Math.min(288, window.innerWidth * 0.28))

            flyoutEl.style.width = `${width}px`
            flyoutEl.style.minWidth = `${width}px`
            flyoutEl.hidden = false
            bridgeEl.hidden = false

            const panelHeight = Math.max(flyoutEl.getBoundingClientRect().height, 8)
            let top = Math.max(8, rect.top)

            if (top + panelHeight > window.innerHeight - 8) {
                top = Math.max(8, window.innerHeight - panelHeight - 8)
            }

            flyoutEl.style.top = `${top}px`
            bridgeEl.style.top = `${Math.min(top, rect.top)}px`
            bridgeEl.style.height = `${Math.max(panelHeight, rect.height) + Math.abs(top - rect.top)}px`

            if (isRtl()) {
                const right = Math.max(8, window.innerWidth - rect.left + gap)
                flyoutEl.style.right = `${right}px`
                flyoutEl.style.left = 'auto'
                bridgeEl.style.right = `${Math.max(0, window.innerWidth - rect.left)}px`
                bridgeEl.style.left = 'auto'
                bridgeEl.style.width = `${gap + 4}px`
                flyoutEl.dataset.dir = 'rtl'
            } else {
                const left = Math.min(window.innerWidth - width - 8, rect.right + gap)
                flyoutEl.style.left = `${left}px`
                flyoutEl.style.right = 'auto'
                bridgeEl.style.left = `${rect.right}px`
                bridgeEl.style.right = 'auto'
                bridgeEl.style.width = `${Math.max(gap + 4, left - rect.right)}px`
                flyoutEl.dataset.dir = 'ltr'
            }
        }

        function populateFlyout(group) {
            ensureFlyoutShell()

            const items = groupItems(group)
            const list = flyoutEl.querySelector('ul')

            if (! items || ! list) {
                return false
            }

            const entries = visibleChildItems(items)
                .map((item) => {
                    const link = childLink(item)

                    if (! link?.getAttribute('href')) {
                        return null
                    }

                    const href = link.getAttribute('href')

                    return {
                        href,
                        label: childLabel(item, link),
                        active: item.classList.contains('fi-active'),
                        target: link.getAttribute('target'),
                        rel: link.getAttribute('rel'),
                        icon: cloneChildIcon(group, href, item),
                    }
                })
                .filter(Boolean)

            if (entries.length < 2) {
                return false
            }

            list.replaceChildren()

            for (const entry of entries) {
                const li = document.createElement('li')
                const a = document.createElement('a')

                a.href = entry.href
                a.className = 'mksine-wp-nav-flyout-link'
                a.setAttribute('role', 'menuitem')

                if (entry.icon) {
                    const iconWrap = document.createElement('span')
                    iconWrap.className = 'mksine-wp-nav-flyout-icon'
                    entry.icon.classList?.add?.('mksine-wp-nav-flyout-icon-svg')
                    iconWrap.appendChild(entry.icon)
                    a.appendChild(iconWrap)
                }

                const label = document.createElement('span')
                label.className = 'mksine-wp-nav-flyout-label'
                label.textContent = entry.label
                a.appendChild(label)

                if (entry.target) {
                    a.target = entry.target
                }

                if (entry.rel) {
                    a.rel = entry.rel
                }

                if (entry.active) {
                    a.classList.add('is-active')
                    li.classList.add('is-active')
                }

                li.appendChild(a)
                list.appendChild(li)
            }

            return true
        }

        function showFlyout(group) {
            if (isFiltering() || ! isOpenDesktopSidebar()) {
                return
            }

            const mode = classifyGroup(group)

            if (mode !== 'parent') {
                if (activeGroup === group) {
                    hideFlyout()
                }

                return
            }

            const trigger = groupTrigger(group)

            if (! trigger || trigger.offsetParent === null) {
                return
            }

            clearHideTimer()
            clearAnimTimer()
            flyoutGeneration += 1

            if (activeGroup && activeGroup !== group) {
                activeGroup.classList.remove('mksine-nav-flyout-open')
            }

            if (! populateFlyout(group)) {
                hideFlyout()

                return
            }

            activeGroup = group
            group.classList.add('mksine-nav-flyout-open')
            ensureCollapsed(group)
            positionFlyout(trigger)

            flyoutEl.setAttribute('aria-hidden', 'false')

            // Force reflow so the open transition always runs.
            void flyoutEl.offsetWidth
            flyoutEl.classList.add('is-open')
        }

        function navigateToFirstChild(group) {
            const items = groupItems(group)

            if (! items) {
                return false
            }

            const link = childLink(visibleChildItems(items)[0] ?? null)

            if (! link) {
                return false
            }

            link.click()

            return true
        }

        document.addEventListener('pointerover', (event) => {
            if (! isOpenDesktopSidebar() || isFiltering()) {
                return
            }

            const group = groupFromTarget(event.target)

            if (! group) {
                return
            }

            const mode = group.classList.contains('mksine-nav-parent')
                ? 'parent'
                : classifyGroup(group)

            if (mode !== 'parent') {
                return
            }

            if (group === activeGroup && flyoutEl && flyoutEl.classList.contains('is-open')) {
                clearHideTimer()

                return
            }

            showFlyout(group)
        }, true)

        document.addEventListener('pointerout', (event) => {
            const group = groupFromTarget(event.target)

            if (! group || group !== activeGroup) {
                return
            }

            const related = event.relatedTarget

            if (related instanceof Node) {
                if (group.contains(related)) {
                    return
                }

                if (flyoutEl?.contains(related) || bridgeEl?.contains(related)) {
                    return
                }
            }

            scheduleHide()
        }, true)

        document.addEventListener('click', (event) => {
            const target = event.target

            if (! (target instanceof Element) || isFiltering() || ! isOpenDesktopSidebar()) {
                return
            }

            const trigger = target.closest('#fi-main-sidebar .fi-sidebar-group-btn')

            if (! trigger) {
                if (
                    flyoutEl
                    && flyoutEl.classList.contains('is-open')
                    && ! flyoutEl.contains(target)
                    && ! bridgeEl?.contains(target)
                    && ! target.closest('#fi-main-sidebar .fi-sidebar-group.mksine-nav-parent')
                ) {
                    hideFlyout()
                }

                return
            }

            const group = trigger.closest('.fi-sidebar-group.fi-collapsible')

            if (! group) {
                return
            }

            const mode = classifyGroup(group)

            if (mode === 'none') {
                return
            }

            event.preventDefault()
            event.stopPropagation()
            event.stopImmediatePropagation?.()

            ensureCollapsed(group)
            hideFlyout()
            navigateToFirstChild(group)
        }, true)

        function boot() {
            hideFlyout()
            classifyAll()
        }

        document.addEventListener('DOMContentLoaded', boot)
        document.addEventListener('livewire:navigated', boot)
        document.addEventListener('livewire:init', () => {
            queueMicrotask(boot)

            window.Livewire?.hook?.('morph.updated', ({ el }) => {
                if (el?.id === 'fi-main-sidebar' || el?.closest?.('#fi-main-sidebar')) {
                    queueMicrotask(() => {
                        hideFlyout()
                        classifyAll()
                    })
                }
            })
        })

        document.addEventListener('input', (event) => {
            if (event.target?.closest?.('[x-data="mksineSidebarNavSearch"]')) {
                queueMicrotask(() => {
                    hideFlyout()
                    classifyAll()
                })
            }
        }, true)

        window.addEventListener('resize', () => {
            if (activeGroup) {
                const trigger = groupTrigger(activeGroup)

                if (trigger) {
                    positionFlyout(trigger)
                } else {
                    hideFlyout()
                }
            }

            classifyAll()
        })

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                hideFlyout()
            }
        })

        if (document.readyState !== 'loading') {
            boot()
        }
    })()
</script>
