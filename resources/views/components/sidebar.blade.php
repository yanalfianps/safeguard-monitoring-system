@props(['active' => ''])

<aside class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="brand">
        <div class="brand-logo">
            <img src="{{ asset('images/logo/geo-dipa.png') }}" alt="Logo Geo Dipa Energi" class="brand-logo-img">
        </div>
        <div class="brand-text">
            <div class="brand-title">{{ config('branding.app_name', 'Safeguard Monitoring System') }}</div>
        </div>
        <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Tutup Menu Navigasi">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- Navigation Menu -->
    <nav class="nav-menu">
        @if(auth()->user()?->isAdminInventory())
            <!-- Khusus Role Admin Inventory: Hanya Akses Fitur Peminjaman Barang -->
            <a class="nav-link-custom active" href="{{ route('inventory.index', ['tab' => 'borrowed']) }}">
                <i class="bi bi-box-arrow-right"></i> Peminjaman Barang
            </a>
        @else
            <a class="nav-link-custom {{ $active === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <a class="nav-link-custom {{ $active === 'daily-work' ? 'active' : '' }}" href="{{ route('daily-work.index') }}">
                <i class="bi bi-calendar2-week-fill"></i> Daily Work
            </a>

            {{-- KPI Monitoring disembunyikan sementara dari navigasi sidebar --}}
            {{--
            @php
                $isKpiMonitoringActive = Str::startsWith($active, 'kpi-monitoring') || $active === 'hsse-plan';
                $navContractors = \App\Models\Contractor::getActiveForNavigation();
            @endphp

            <a class="nav-link-custom {{ $isKpiMonitoringActive ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#kpiMonitoringSubmenu" role="button" aria-expanded="{{ $isKpiMonitoringActive ? 'true' : 'false' }}">
                <i class="bi bi-graph-up-arrow"></i> KPI Monitoring
                <i class="bi bi-chevron-down nav-chevron"></i>
            </a>

            <div class="collapse {{ $isKpiMonitoringActive ? 'show' : '' }} submenu" id="kpiMonitoringSubmenu">
                <a class="nav-link-custom {{ in_array($active, ['kpi-monitoring', 'kpi-monitoring-overview', 'hsse-plan']) ? 'active' : '' }}" href="{{ route('kpi-monitoring.index') }}">
                    <i class="bi bi-speedometer2"></i> Ringkasan
                </a>
                @foreach($navContractors as $navContractor)
                    @php
                        $contractorSlug = $navContractor->slug ?: ($navContractor->code ? strtolower($navContractor->code) : 'contractor-' . $navContractor->id);
                    @endphp
                    @if(!empty($contractorSlug))
                        <a class="nav-link-custom {{ $active === 'kpi-monitoring-contractor-' . $contractorSlug ? 'active' : '' }}" href="{{ route('kpi-monitoring.contractor', ['contractor' => $contractorSlug]) }}">
                            <i class="bi bi-building-check"></i> {{ $navContractor->display_name ?: $navContractor->name }}
                        </a>
                    @endif
                @endforeach
                @adminManagement
                <a class="nav-link-custom {{ $active === 'kpi-monitoring-master' ? 'active' : '' }}" href="{{ route('kpi-monitoring.master.index') }}">
                    <i class="bi bi-sliders2"></i> Kelola Master Data
                </a>
                @endadminManagement
            </div>
            --}}

            <a class="nav-link-custom {{ $active === 'events' ? 'active' : '' }}" href="{{ route('events.index') }}">
                <i class="bi bi-calendar-event-fill"></i> Events
            </a>

            <a class="nav-link-custom {{ $active === 'inventory' ? 'active' : '' }}" href="{{ url('/inventory') }}">
                <i class="bi bi-box-seam-fill"></i> Inventory
            </a>

            <a class="nav-link-custom {{ in_array($active, ['man-hours', 'workforce']) ? 'active' : '' }}" href="{{ route('man-hours.index') }}">
                <i class="bi bi-calendar2-check"></i> Man Hours &amp; Manpower
            </a>

            <a class="nav-link-custom {{ $active === 'incident' ? 'active' : '' }}" href="{{ route('incident.index') }}">
                <i class="bi bi-exclamation-triangle"></i> Incident
            </a>

            <a class="nav-link-custom {{ $active === 'issue-follow-up' ? 'active' : '' }}" href="{{ url('/issue-follow-up') }}">
                <i class="bi bi-clipboard-check"></i> Issue &amp; Follow Up
            </a>

            <a class="nav-link-custom {{ $active === 'gas-detector' ? 'active' : '' }}" href="{{ route('gas-detector.index') }}">
                <i class="bi bi-broadcast-pin"></i> Gas Monitoring
            </a>

            <a class="nav-link-custom {{ in_array($active, ['monitoring-anggaran', 'budget-monitoring']) ? 'active' : '' }}" href="{{ route('budget-monitoring.index') }}">
                <i class="bi bi-wallet2"></i> Monitoring Anggaran
            </a>

            <!-- Environment Menu -->
            <a class="nav-link-custom {{ Str::startsWith($active, 'environment') ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#environmentSubmenu" role="button" aria-expanded="{{ Str::startsWith($active, 'environment') ? 'true' : 'false' }}">
                <i class="bi bi-tree-fill"></i> Environment
                <i class="bi bi-chevron-down nav-chevron"></i>
            </a>

            <div class="collapse {{ Str::startsWith($active, 'environment') ? 'show' : '' }} submenu" id="environmentSubmenu">
                <a class="nav-link-custom {{ $active === 'environment' || $active === 'environment-overview' ? 'active' : '' }}" href="{{ route('environment.index') }}">
                    <i class="bi bi-speedometer2"></i> Ringkasan
                </a>
                <a class="nav-link-custom {{ $active === 'environment-lb3' ? 'active' : '' }}" href="{{ route('environment.lb3.index') }}">
                    <i class="bi bi-radioactive"></i> LB3
                </a>
                <a class="nav-link-custom {{ $active === 'environment-non-lb3' ? 'active' : '' }}" href="{{ route('environment.non-lb3.index') }}">
                    <i class="bi bi-trash3"></i> Non-LB3
                </a>
                <a class="nav-link-custom {{ $active === 'environment-rkl-rpl' ? 'active' : '' }}" href="{{ route('environment.rkl-rpl.index') }}">
                    <i class="bi bi-clipboard2-pulse"></i> RKL–RPL
                </a>
                <a class="nav-link-custom {{ in_array($active, ['environment-inspeksi', 'environment-ppkh', 'environment-ppich']) ? 'active' : '' }}" href="{{ route('environment.inspeksi.index') }}">
                    <i class="bi bi-clipboard-check"></i> Inspeksi
                </a>
                <a class="nav-link-custom {{ Str::startsWith($active, 'environment-energi') ? 'active' : '' }}" href="{{ route('environment.energi.index') }}">
                    <i class="bi bi-lightning-charge"></i> Energi
                </a>
                <a class="nav-link-custom {{ in_array($active, ['environment-ews', 'environment-cuaca']) ? 'active' : '' }}" href="{{ route('environment.ews.index') }}">
                    <i class="bi bi-bell"></i> EWS &amp; Cuaca
                </a>
            </div>

            @adminManagement
            <a class="nav-link-custom {{ $active === 'users' ? 'active' : '' }}" href="{{ route('users.index') }}">
                <i class="bi bi-people-fill"></i> Manajemen Pengguna
            </a>
            @endadminManagement
        @endif
    </nav>

    <!-- Sidebar Footer Dieng -->
    <div class="sidebar-footer">
        <div class="sidebar-footer-text">
            Safety Today<br>Sustainability Tomorrow
        </div>
    </div>
</aside>

