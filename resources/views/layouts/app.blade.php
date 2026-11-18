<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="application-name" content="{{ config('branding.app_name', 'Safeguard Monitoring System') }} (HSSE PROJECT)">
    <title>{{ $title ?? config('branding.app_name', 'Safeguard Monitoring System') }} | {{ config('branding.project_title', 'Project Manajemen Geodipa Unit Dieng 2') }}</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Flatpickr for Date Range -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Shared HSSE Theme CSS -->
    <link rel="stylesheet" href="/css/hsse-theme.css">

    @stack('styles')
</head>
<body>
    <!-- Shared Sidebar -->
    <x-sidebar :active="$activeModule ?? ''" />
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

    <!-- Main Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <!-- Shared Navbar -->
        <x-navbar />

        <!-- Main Page Content -->
        <main class="page-content">
            @yield('content')
        </main>
    </div>

    <!-- Core Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>

    <script>
        if (window.Chart && Chart.defaults && Chart.defaults.font) {
            Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
        }

        // Shared Responsive Sidebar Controller (Desktop collapse >= 992px, Offcanvas < 992px)
        (function () {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');
            const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
            const sidebarEl = document.getElementById('sidebar');
            const desktopMq = window.matchMedia('(min-width: 992px)');

            function triggerChartReflow() {
                setTimeout(function () {
                    window.dispatchEvent(new Event('resize'));
                }, 270);
            }

            function closeMobileSidebar() {
                document.body.classList.remove('sidebar-open');
            }

            function syncSidebarStateForViewport() {
                if (desktopMq.matches) {
                    document.body.classList.remove('sidebar-open');
                } else {
                    document.body.classList.remove('sidebar-collapsed');
                }
                triggerChartReflow();
            }

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function () {
                    if (desktopMq.matches) {
                        document.body.classList.toggle('sidebar-collapsed');
                        document.body.classList.remove('sidebar-open');
                        triggerChartReflow();
                    } else {
                        document.body.classList.toggle('sidebar-open');
                        document.body.classList.remove('sidebar-collapsed');
                    }
                });
            }

            if (sidebarBackdrop) {
                sidebarBackdrop.addEventListener('click', closeMobileSidebar);
            }

            if (sidebarCloseBtn) {
                sidebarCloseBtn.addEventListener('click', closeMobileSidebar);
            }

            if (sidebarEl) {
                sidebarEl.addEventListener('click', function (e) {
                    const link = e.target.closest('a[href]');
                    if (!link) return;
                    if (link.hasAttribute('data-bs-toggle') || link.getAttribute('href') === '#') return;
                    if (!desktopMq.matches) {
                        closeMobileSidebar();
                    }
                });
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
                    closeMobileSidebar();
                }
            });

            if (typeof desktopMq.addEventListener === 'function') {
                desktopMq.addEventListener('change', syncSidebarStateForViewport);
            } else if (typeof desktopMq.addListener === 'function') {
                desktopMq.addListener(syncSidebarStateForViewport);
            }
        })();
    </script>

    @stack('scripts')
</body>
</html>

