document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const sidebarCollapseButton = document.querySelector('[data-sidebar-collapse]');
    const sidebarExpandButton = document.querySelector('[data-sidebar-expand]');
    const sidebarCloseButtons = document.querySelectorAll('[data-sidebar-close]');
    const sidebarNavigationLinks = document.querySelectorAll('.sidebar-navigation .sidebar-link');
    const sidebarClock = document.querySelector('[data-sidebar-clock]');
    const sidebarClockDate = document.querySelector('[data-clock-date]');
    const sidebarClockTime = document.querySelector('[data-clock-time]');
    const desktopMedia = window.matchMedia('(min-width: 1024px)');
    const sidebarStorageKey = 'ecotrack.adminSidebar.collapsed';

    const setMobileToggleState = (expanded) => {
        sidebarToggle?.setAttribute('aria-expanded', String(expanded));
        sidebarToggle?.setAttribute('aria-label', expanded ? 'Tutup navigasi' : 'Buka navigasi');
    };

    const readDesktopCollapsed = () => {
        try {
            return window.sessionStorage.getItem(sidebarStorageKey) === 'true';
        } catch (error) {
            return document.documentElement.classList.contains('sidebar-collapsed');
        }
    };

    const setDesktopCollapsed = (collapsed, persist = true) => {
        document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
        sidebarClock?.setAttribute('tabindex', collapsed ? '0' : '-1');
        sidebarCollapseButton?.setAttribute('aria-expanded', String(!collapsed));
        sidebarCollapseButton?.setAttribute('aria-label', 'Ciutkan navigasi');
        sidebarExpandButton?.setAttribute('aria-expanded', String(!collapsed));
        sidebarExpandButton?.setAttribute('aria-label', 'Perluas navigasi');
        sidebarToggle?.setAttribute('aria-expanded', String(!collapsed));
        sidebarToggle?.setAttribute('aria-label', collapsed ? 'Perluas navigasi' : 'Ciutkan navigasi');

        if (!persist) return;

        try {
            window.sessionStorage.setItem(sidebarStorageKey, String(collapsed));
        } catch (error) {
            // The sidebar remains usable when browser storage is unavailable.
        }
    };

    const sidebarClockDateFormatter = new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'Asia/Makassar',
    });
    const sidebarClockTimeFormatter = new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hourCycle: 'h23',
        timeZone: 'Asia/Makassar',
    });

    const updateSidebarClock = () => {
        if (!sidebarClock || !sidebarClockDate || !sidebarClockTime) return;

        const now = new Date();
        const date = sidebarClockDateFormatter.format(now);
        const time = sidebarClockTimeFormatter.formatToParts(now)
            .filter((part) => ['hour', 'minute', 'second'].includes(part.type))
            .map((part) => part.value)
            .join(':');
        const formattedDate = date.charAt(0).toLocaleUpperCase('id-ID') + date.slice(1);

        sidebarClockDate.textContent = formattedDate;
        sidebarClockTime.textContent = time;
        sidebarClock.dataset.clockTooltip = `${formattedDate} · ${time}`;
        sidebarClock.setAttribute('aria-label', `Waktu Makassar: ${formattedDate}, ${time}`);
    };

    updateSidebarClock();
    window.setInterval(updateSidebarClock, 1000);

    const positionSidebarTooltip = (event) => {
        const link = event.currentTarget;
        const bounds = link.getBoundingClientRect();
        link.style.setProperty('--sidebar-tooltip-top', `${bounds.top + bounds.height / 2}px`);
    };

    sidebarNavigationLinks.forEach((link) => {
        link.addEventListener('mouseenter', positionSidebarTooltip);
        link.addEventListener('focus', positionSidebarTooltip);
    });

    document.querySelectorAll('[data-logout-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm('Apakah Anda yakin ingin keluar dari akun EcoTrack?')) {
                event.preventDefault();
            }
        });
    });

    const openMobileSidebar = () => {
        sidebar?.classList.add('is-open');
        overlay?.classList.add('is-visible');
        document.body.classList.add('overflow-hidden');
        setMobileToggleState(true);
    };

    const closeMobileSidebar = () => {
        sidebar?.classList.remove('is-open');
        overlay?.classList.remove('is-visible');
        document.body.classList.remove('overflow-hidden');
        setMobileToggleState(false);
    };

    const toggleSidebar = () => {
        if (!sidebar) return;

        if (desktopMedia.matches) {
            setDesktopCollapsed(!document.documentElement.classList.contains('sidebar-collapsed'));
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
        setDesktopCollapsed(readDesktopCollapsed(), false);
    } else {
        closeMobileSidebar();
    }

    sidebarToggle?.addEventListener('click', toggleSidebar);
    sidebarCollapseButton?.addEventListener('click', () => {
        setDesktopCollapsed(true);
        sidebarExpandButton?.focus({ preventScroll: true });
    });
    sidebarExpandButton?.addEventListener('click', () => {
        setDesktopCollapsed(false);
        sidebarCollapseButton?.focus({ preventScroll: true });
    });
    overlay?.addEventListener('click', closeMobileSidebar);
    sidebarCloseButtons.forEach((button) => {
        button.addEventListener('click', closeMobileSidebar);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !desktopMedia.matches) {
            closeMobileSidebar();
        }
    });

    // Keep the desktop preference while switching to the mobile drawer layout.
    desktopMedia.addEventListener('change', (event) => {
        if (event.matches) {
            setDesktopCollapsed(readDesktopCollapsed(), false);
        } else {
            closeMobileSidebar();
        }
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
