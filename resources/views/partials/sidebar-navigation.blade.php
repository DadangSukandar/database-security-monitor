@include('partials.guardium-theme')

@once
    <style>
        .sidebar {
            width: 72px;
            height: 100vh;
            position: sticky;
            top: 0;
            flex-shrink: 0;
            overflow-y: auto;
            padding: 14px 10px;
            background: #090f1a;
            color: #fff;
            border-right: 1px solid #26354a;
            transition: width .2s ease;
            z-index: 50;
        }

        .sidebar.is-expanded {
            width: 260px;
            box-shadow: 18px 0 40px rgba(0, 0, 0, .34);
        }

        .sidebar-toggle {
            display: flex;
            width: 44px;
            height: 40px;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            padding: 0;
            border: 1px solid transparent;
            border-radius: 2px;
            background: transparent;
            color: #cbd5e1;
            cursor: pointer;
            font-size: 22px;
            line-height: 1;
        }

        .sidebar-toggle:hover,
        .sidebar-toggle:focus-visible {
            border-color: #52657f;
            background: #1c2b40;
            color: #fff;
            outline: none;
        }

        .sidebar.is-expanded .sidebar-toggle {
            width: 100%;
            justify-content: flex-start;
            padding-left: 12px;
        }

        .sidebar .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            overflow: hidden;
            padding: 10px 11px 22px;
            font-weight: 700;
            white-space: nowrap;
        }

        .sidebar .brand-icon {
            width: 26px;
            flex: 0 0 26px;
            color: #33b1ff;
            font-size: 22px;
            text-align: center;
        }

        .sidebar .brand-copy {
            display: none;
            font-size: 18px;
        }

        .sidebar .brand small {
            display: none;
            margin-top: 5px;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 400;
        }

        .sidebar .menu-title {
            display: none;
            padding: 15px 12px 7px;
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .sidebar .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 3px;
            justify-content: center;
            min-height: 44px;
            padding: 11px;
            border-left: 3px solid transparent;
            border-radius: 2px;
            color: #cbd5e1;
            font-size: 14px;
            text-decoration: none;
            white-space: nowrap;
        }

        .sidebar .nav-item:hover {
            background: #1e293b;
            color: #fff;
        }

        .sidebar .nav-item.active {
            border-left-color: #33b1ff;
            background: #1c2b40;
            color: #fff;
            box-shadow: inset 0 0 0 1px #52657f;
        }

        .sidebar .nav-icon {
            width: 22px;
            flex-shrink: 0;
            text-align: center;
        }

        .sidebar .nav-item span:not(.nav-icon) {
            display: none;
        }

        .sidebar.is-expanded .brand small,
        .sidebar.is-expanded .brand-copy,
        .sidebar.is-expanded .menu-title,
        .sidebar.is-expanded .nav-item span:not(.nav-icon) {
            display: block;
        }

        .sidebar.is-expanded .nav-item {
            justify-content: flex-start;
        }

        .pagination {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin: 0;
            padding-left: 0;
            list-style: none;
        }

        .pagination .page-link {
            display: flex;
            min-width: 36px;
            height: 36px;
            align-items: center;
            justify-content: center;
            padding: 0 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #fff;
            color: #2563eb;
            font-size: 13px;
            text-decoration: none;
        }

        .pagination .active > .page-link {
            border-color: #2563eb;
            background: #2563eb;
            color: #fff;
        }

        .pagination .disabled > .page-link {
            color: #94a3b8;
            pointer-events: none;
        }

        .pagination svg {
            width: 16px;
            height: 16px;
        }

        @media (max-width: 760px) {
            .sidebar {
                width: 78px;
                padding: 16px 10px;
            }

            .sidebar .brand {
                justify-content: center;
                padding: 8px 6px 20px;
            }

            .sidebar .brand small,
            .sidebar .brand-copy,
            .sidebar .menu-title,
            .sidebar .nav-item span:not(.nav-icon) {
                display: none;
            }

            .sidebar .nav-item {
                justify-content: center;
                padding: 11px;
            }

            .sidebar .nav-icon {
                width: auto;
            }

            .sidebar.is-expanded {
                width: min(260px, calc(100vw - 24px));
            }

            .sidebar.is-expanded .brand {
                justify-content: flex-start;
            }

            .sidebar.is-expanded .brand small,
            .sidebar.is-expanded .brand-copy,
            .sidebar.is-expanded .menu-title,
            .sidebar.is-expanded .nav-item span:not(.nav-icon) {
                display: block;
            }

            .sidebar.is-expanded .nav-item {
                justify-content: flex-start;
            }
        }
    </style>
@endonce

<aside class="sidebar" id="main-sidebar">
    <button
        type="button"
        class="sidebar-toggle"
        aria-controls="main-sidebar"
        aria-expanded="false"
        aria-label="Buka navigasi"
        title="Buka atau tutup navigasi"
    >
        <span aria-hidden="true">&#9776;</span>
    </button>

    <div class="brand">
        <span class="brand-icon" aria-hidden="true">🛡️</span>
        <span class="brand-copy">
            Guardium Center
            <small>Database Security Platform</small>
        </span>
    </div>

    <div class="menu-title">Main</div>
    <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard', 'home') ? 'active' : '' }}">
        <span class="nav-icon">🏠</span><span>Dashboard</span>
    </a>
    <a href="{{ route('security-dashboard') }}" class="nav-item {{ request()->routeIs('security-dashboard') ? 'active' : '' }}">
        <span class="nav-icon">📈</span><span>Security Overview</span>
    </a>

    <div class="menu-title">Database</div>
    <a href="{{ route('database-connections.index') }}" class="nav-item {{ request()->routeIs('database-connections.*') ? 'active' : '' }}">
        <span class="nav-icon">🗄️</span><span>Database Connections</span>
    </a>
    <a href="{{ route('database-discovery.index') }}" class="nav-item {{ request()->routeIs('database-discovery.*', 'database-explorer.*') ? 'active' : '' }}">
        <span class="nav-icon">🔎</span><span>Database Discovery</span>
    </a>
    <a href="{{ route('sql-query.index') }}" class="nav-item {{ request()->routeIs('sql-query.*', 'database-query.*') ? 'active' : '' }}">
        <span class="nav-icon">💻</span><span>SQL Query Console</span>
    </a>
    <a href="{{ route('database-users.index') }}" class="nav-item {{ request()->routeIs('database-users.*') ? 'active' : '' }}">
        <span class="nav-icon">👤</span><span>Database Users</span>
    </a>
    <a href="{{ route('database-privileges.index') }}" class="nav-item {{ request()->routeIs('database-privileges.*') ? 'active' : '' }}">
        <span class="nav-icon">🔐</span><span>Privileges</span>
    </a>
    <a href="{{ route('sensitive-data.index') }}" class="nav-item {{ request()->routeIs('sensitive-data.*') ? 'active' : '' }}">
        <span class="nav-icon">🕵️</span><span>Sensitive Data</span>
    </a>

    <div class="menu-title">Monitoring</div>
    <a href="{{ route('database-activities.index') }}" class="nav-item {{ request()->routeIs('database-activities.*') ? 'active' : '' }}">
        <span class="nav-icon">📊</span><span>Activity Monitoring</span>
    </a>
    <a href="{{ route('query-history.index') }}" class="nav-item {{ request()->routeIs('query-history.*') ? 'active' : '' }}">
        <span class="nav-icon">📜</span><span>Query History</span>
    </a>

    <div class="menu-title">Security</div>
    <a href="{{ route('security-alerts.index') }}" class="nav-item {{ request()->routeIs('security-alerts.*') ? 'active' : '' }}">
        <span class="nav-icon">🚨</span><span>Security Alerts</span>
    </a>

    <a href="{{ route('security-incidents.index') }}"
    class="nav-item {{ request()->routeIs('security-incidents.*') ? 'active' : '' }}">
        <span class="nav-icon">🚒</span><span>Security Incidents</span>
    </a>

    <a href="{{ route('security-audit.index') }}" class="nav-item {{ request()->routeIs('security-audit.*') ? 'active' : '' }}">
        <span class="nav-icon">🛡️</span><span>Security Audit</span>
    </a>
    <a href="{{ route('security-findings.index') }}" class="nav-item {{ request()->routeIs('security-findings.*') ? 'active' : '' }}">
        <span class="nav-icon">⚠️</span><span>Security Findings</span>
    </a>
    <a href="{{ route('security-policies.index') }}" class="nav-item {{ request()->routeIs('security-policies.*') ? 'active' : '' }}">
        <span class="nav-icon">📋</span><span>Security Policies</span>
    </a>
    <a href="{{ route('vulnerability-assessments.index') }}" class="nav-item {{ request()->routeIs('vulnerability-assessments.*') ? 'active' : '' }}">
        <span class="nav-icon">🔬</span><span>Assessments</span>
    </a>
    <a href="{{ route('security-reports.index') }}" class="nav-item {{ request()->routeIs('security-reports.*') ? 'active' : '' }}">
        <span class="nav-icon">📄</span><span>Security Reports</span>
    </a>
    <a href="{{ route('security-risk.index') }}" class="nav-item {{ request()->routeIs('security-risk.*') ? 'active' : '' }}">
        <span class="nav-icon">📉</span><span>Security Risk</span>
    </a>
</aside>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('main-sidebar');
            const toggle = sidebar?.querySelector('.sidebar-toggle');

            if (! sidebar || ! toggle) {
                return;
            }

            const storageKey = 'guardium-sidebar-expanded';

            try {
                sidebar.classList.toggle('is-expanded', localStorage.getItem(storageKey) === 'true');
            } catch (error) {
                sidebar.classList.remove('is-expanded');
            }

            const updateToggleState = function () {
                const isExpanded = sidebar.classList.contains('is-expanded');

                toggle.setAttribute('aria-expanded', String(isExpanded));
                toggle.setAttribute('aria-label', isExpanded ? 'Tutup navigasi' : 'Buka navigasi');
            };

            updateToggleState();

            toggle.addEventListener('click', function () {
                sidebar.classList.toggle('is-expanded');
                updateToggleState();

                try {
                    localStorage.setItem(storageKey, String(sidebar.classList.contains('is-expanded')));
                } catch (error) {
                    // The navigation remains usable when browser storage is unavailable.
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Escape' || ! sidebar.classList.contains('is-expanded')) {
                    return;
                }

                sidebar.classList.remove('is-expanded');
                updateToggleState();
                toggle.focus();

                try {
                    localStorage.setItem(storageKey, 'false');
                } catch (error) {
                    // The navigation remains usable when browser storage is unavailable.
                }
            });
        });
    </script>
@endonce
