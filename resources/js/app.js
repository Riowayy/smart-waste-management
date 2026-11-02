import './user-reports';

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
    const isUserPortal = document.body.classList.contains('user-portal');
    const sidebarStorageKey = document.documentElement.dataset.sidebarStorage || 'ecotrack.adminSidebar.collapsed';

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
        if (isUserPortal && sidebar) sidebar.inert = false;
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
        if (isUserPortal && sidebar) sidebar.inert = false;
        sidebar?.classList.add('is-open');
        overlay?.classList.add('is-visible');
        document.body.classList.add('overflow-hidden');
        setMobileToggleState(true);
        if (isUserPortal) sidebarNavigationLinks[0]?.focus();
    };

    const closeMobileSidebar = () => {
        if (isUserPortal && sidebar) {
            if (sidebar.contains(document.activeElement)) sidebarToggle?.focus();
            sidebar.inert = true;
        }
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

    // Fixed tooltips live under body so card overflow cannot clip them.
    const positionTooltip = (tooltip, x, y) => {
        tooltip.hidden = false;
        const bounds = tooltip.getBoundingClientRect();
        tooltip.style.left = `${Math.max(8, Math.min(x + 12, innerWidth - bounds.width - 8))}px`;
        tooltip.style.top = `${Math.max(8, Math.min(innerHeight - bounds.height - 8, y - bounds.height - 12 < 8 ? y + 12 : y - bounds.height - 12))}px`;
    };
    const chartTooltip = document.querySelector('[data-chart-tooltip]');
    const chart = document.querySelector('[data-line-chart]');
    const points = [...document.querySelectorAll('[data-chart-point]')];
    let activePoint = null;
    if (chartTooltip) document.body.append(chartTooltip);
    const hideChartTooltip = () => {
        if (chartTooltip) chartTooltip.hidden = true;
        points.forEach((point) => point.setAttribute('r', point.dataset.radius));
        activePoint = null;
    };
    const showPoint = (point) => {
        hideChartTooltip();
        activePoint = point;
        const date = new Date(`${point.dataset.date}T12:00:00`);
        const formattedDate = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(date);
        chartTooltip.textContent = `${formattedDate}\nLaporan masuk: ${point.dataset.reports}\nSelesai atau ditolak: ${point.dataset.handled}`;
        points.filter((item) => item.dataset.date === point.dataset.date).forEach((item) => item.setAttribute('r', '6'));
        const rect = point.getBoundingClientRect();
        positionTooltip(chartTooltip, rect.x + rect.width / 2, rect.y + rect.height / 2);
    };
    points.forEach((point) => {
        point.dataset.radius = point.getAttribute('r');
        point.addEventListener('focus', () => showPoint(point));
        point.addEventListener('blur', hideChartTooltip);
        point.addEventListener('keydown', (event) => { if (event.key === 'Escape') hideChartTooltip(); });
    });
    chart?.addEventListener('pointermove', (event) => {
        const nearest = points.reduce((best, point) => {
            const rect = point.getBoundingClientRect();
            const distance = Math.abs(event.clientX - rect.x - rect.width / 2);
            return !best || distance < best.distance ? { point, distance } : best;
        }, null);
        if (nearest && activePoint !== nearest.point) showPoint(nearest.point);
    });
    chart?.addEventListener('pointerleave', hideChartTooltip);

    const segments = [...document.querySelectorAll('[data-donut-category]')];
    const total = document.querySelector('[data-donut-total]');
    const label = document.querySelector('[data-donut-label]');
    const detail = document.querySelector('[data-donut-detail]');
    if (detail) document.body.append(detail);
    const originalTotal = total?.textContent;
    let selected = null;
    const displayCategory = (segment) => {
        if (!total) return;
        total.textContent = segment ? Number(segment.dataset.total).toLocaleString('id-ID') : originalTotal;
        label.textContent = segment ? segment.dataset.label : 'Laporan berkategori';

        segments.forEach((item) => item.setAttribute('aria-pressed', String(item === selected)));
    };
    segments.forEach((segment) => {
        const showTooltip = (event) => {
            displayCategory(segment);
            detail.textContent = `${segment.dataset.label}\nJumlah laporan: ${segment.dataset.total}\nPersentase: ${segment.dataset.percent}%`;
            const rect = segment.ownerSVGElement.getBoundingClientRect();
            positionTooltip(detail, event.clientX ?? rect.right, event.clientY ?? rect.top + rect.height / 2);
        };
        segment.addEventListener('pointermove', showTooltip);
        segment.addEventListener('pointerleave', () => { detail.hidden = true; displayCategory(selected); });
        segment.addEventListener('focus', showTooltip);
        segment.addEventListener('blur', () => { detail.hidden = true; displayCategory(selected); });
        segment.addEventListener('click', () => {
            selected = selected === segment ? null : segment;
            displayCategory(selected);
        });
        segment.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); segment.dispatchEvent(new Event('click')); }
            if (event.key === 'Escape') { selected = null; detail.hidden = true; displayCategory(null); }
        });
    });
    window.addEventListener('scroll', () => { hideChartTooltip(); if (detail) detail.hidden = true; }, true);
    window.addEventListener('resize', () => { hideChartTooltip(); if (detail) detail.hidden = true; });
    document.querySelector('[data-donut-reset]')?.addEventListener('click', () => { selected = null; displayCategory(null); });
});
