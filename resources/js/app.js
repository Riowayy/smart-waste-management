document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const sidebarCloseButtons = document.querySelectorAll('[data-sidebar-close]');
    const desktopMedia = window.matchMedia('(min-width: 1024px)');

    const setToggleState = (expanded) => {
        sidebarToggle?.setAttribute('aria-expanded', String(expanded));
        sidebarToggle?.setAttribute('aria-label', expanded ? 'Tutup navigasi' : 'Buka navigasi');
    };

    const openMobileSidebar = () => {
        sidebar?.classList.add('is-open');
        overlay?.classList.add('is-visible');
        document.body.classList.add('overflow-hidden');
        setToggleState(true);
    };

    const closeMobileSidebar = () => {
        sidebar?.classList.remove('is-open');
        overlay?.classList.remove('is-visible');
        document.body.classList.remove('overflow-hidden');
        setToggleState(false);
    };

    const toggleSidebar = () => {
        if (!sidebar) return;

        if (desktopMedia.matches) {
            // On desktop, collapse the sidebar and let the content use the full width.
            const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
            setToggleState(!isCollapsed);
            return;
        }

        // On tablet and mobile, use the sidebar as an off-canvas drawer.
        if (sidebar.classList.contains('is-open')) {
            closeMobileSidebar();
        } else {
            openMobileSidebar();
        }
    };

    if (desktopMedia.matches) {
        document.body.classList.remove('sidebar-collapsed');
        setToggleState(true);
    } else {
        closeMobileSidebar();
    }

    sidebarToggle?.addEventListener('click', toggleSidebar);
    overlay?.addEventListener('click', closeMobileSidebar);
    sidebarCloseButtons.forEach((button) => {
        button.addEventListener('click', closeMobileSidebar);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !desktopMedia.matches) {
            closeMobileSidebar();
        }
    });

    // Reset the drawer state when crossing the desktop/mobile breakpoint.
    desktopMedia.addEventListener('change', (event) => {
        document.body.classList.remove('sidebar-collapsed');
        closeMobileSidebar();
        setToggleState(event.matches);
    });

    // Client-side search and category filtering for the dashboard preview.
    const searchInput = document.querySelector('[data-report-search]');
    const categoryFilter = document.querySelector('[data-category-filter]');
    const reportRows = Array.from(document.querySelectorAll('[data-report-row]'));
    const visibleCount = document.querySelector('[data-visible-count]');
    const emptyState = document.querySelector('[data-empty-state]');

    const filterReports = () => {
        const query = (searchInput?.value || '').trim().toLocaleLowerCase('id');
        const category = categoryFilter?.value || 'all';
        let visible = 0;

        reportRows.forEach((row) => {
            const searchable = (row.dataset.search || '').toLocaleLowerCase('id');
            const rowCategory = row.dataset.category || 'all';
            const matchesQuery = !query || searchable.includes(query);
            const matchesCategory = category === 'all' || rowCategory === category;
            const show = matchesQuery && matchesCategory;

            row.hidden = !show;
            if (show) visible += 1;
        });

        if (visibleCount) visibleCount.textContent = String(visible);
        if (emptyState) emptyState.hidden = visible !== 0;
    };

    searchInput?.addEventListener('input', filterReports);
    categoryFilter?.addEventListener('change', filterReports);

    // Period buttons are visual controls until the dashboard chart is connected to live data.
    document.querySelectorAll('[data-period-button]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-period-button]').forEach((item) => {
                item.classList.remove('bg-white', 'text-slate-900', 'shadow-sm');
                item.classList.add('text-slate-500');
                item.setAttribute('aria-pressed', 'false');
            });

            button.classList.add('bg-white', 'text-slate-900', 'shadow-sm');
            button.classList.remove('text-slate-500');
            button.setAttribute('aria-pressed', 'true');
        });
    });
});
