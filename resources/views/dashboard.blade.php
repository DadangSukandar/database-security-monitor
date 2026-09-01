<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Database Security Center
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }

        a {
            text-decoration: none;
        }

        /*
        |--------------------------------------------------------------------------
        | Layout
        |--------------------------------------------------------------------------
        */

        html,
        body {
            width: 100%;
            min-width: 100%;
            min-height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            overflow-x: hidden;
        }

        .app {
            display: flex;
            width: 100%;
            min-width: 100%;
            min-height: 100vh;
        }

        /*
        |--------------------------------------------------------------------------
        | Sidebar
        |--------------------------------------------------------------------------
        */

        .sidebar {
            width: 250px;
            background: #0f172a;
            color: white;
            padding: 20px 14px;
            flex-shrink: 0;
        }

        .brand {
            font-size: 20px;
            font-weight: 700;
            padding: 10px 12px 25px;
        }

        .brand small {
            display: block;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 400;
            margin-top: 5px;
        }

        .menu-title {
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            padding: 15px 12px 7px;
            text-transform: uppercase;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #cbd5e1;
            padding: 11px 12px;
            border-radius: 7px;
            margin-bottom: 3px;
            font-size: 14px;
        }

        .nav-item:hover {
            background: #1e293b;
            color: white;
        }

        .nav-item.active {
            background: #2563eb;
            color: white;
        }

        .nav-icon {
            width: 22px;
            text-align: center;
        }

        /*
        |--------------------------------------------------------------------------
        | Main
        |--------------------------------------------------------------------------
        */

        .main {
            flex: 1 1 auto;
            min-width: 0;
            width: calc(100% - 250px);
            min-height: 100vh;
        }

        .topbar {
            height: 65px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
        }

        .topbar-title {
            font-weight: 600;
            font-size: 16px;
        }

        .topbar-status {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            font-size: 13px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
        }

        .content {
            width: 100%;
            max-width: none;
            padding: 28px;
            margin: 0;
        }

        /*
        |--------------------------------------------------------------------------
        | Page Header
        |--------------------------------------------------------------------------
        */

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
        }

        .page-title {
            margin: 0;
            font-size: 26px;
        }

        .page-description {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        /*
        |--------------------------------------------------------------------------
        | Buttons
        |--------------------------------------------------------------------------
        */

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 15px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        /*
        |--------------------------------------------------------------------------
        | Cards
        |--------------------------------------------------------------------------
        */

        .cards {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
        }

        .card-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .card-value {
            font-size: 34px;
            font-weight: 700;
            margin-top: 10px;
        }

        .card-description {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 5px;
        }

        /*
        |--------------------------------------------------------------------------
        | Security Score
        |--------------------------------------------------------------------------
        */

        .score-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 25px;
            align-items: center;
        }

        .score-circle {
            width: 190px;
            height: 190px;
            border-radius: 50%;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            background: #eff6ff;
            border: 12px solid #3b82f6;
        }

        .score-number {
            font-size: 42px;
            font-weight: 700;
        }

        .score-max {
            color: #64748b;
            font-size: 13px;
        }

        .score-level {
            font-size: 13px;
            font-weight: 700;
            margin-top: 5px;
        }

        /*
        |--------------------------------------------------------------------------
        | Risk Grid
        |--------------------------------------------------------------------------
        */

        .risk-grid {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .risk-card {
            padding: 18px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .risk-card .number {
            font-size: 30px;
            font-weight: 700;
            margin-top: 8px;
        }

        .risk-critical {
            background: #fef2f2;
        }

        .risk-high {
            background: #fff1f2;
        }

        .risk-medium {
            background: #fffbeb;
        }

        .risk-low {
            background: #f0fdf4;
        }

        .risk-name {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        /*
        |--------------------------------------------------------------------------
        | Main Grid
        |--------------------------------------------------------------------------
        */

        .grid-2 {
            display: grid;
            grid-template-columns:
                minmax(0, 2fr)
                minmax(300px, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .panel {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .panel-header {
            padding: 18px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .panel-title {
            font-weight: 700;
            font-size: 15px;
        }

        .panel-subtitle {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 4px;
        }

        .panel-body {
            padding: 20px;
        }

        /*
        |--------------------------------------------------------------------------
        | Table
        |--------------------------------------------------------------------------
        */

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            text-align: left;
            text-transform: uppercase;
            padding: 12px 15px;
            white-space: nowrap;
        }

        td {
            padding: 13px 15px;
            border-top: 1px solid #f1f5f9;
            font-size: 13px;
        }

        /*
        |--------------------------------------------------------------------------
        | Badges
        |--------------------------------------------------------------------------
        */

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 700;
        }

        .badge-critical {
            background: #991b1b;
            color: white;
        }

        .badge-high {
            background: #dc2626;
            color: white;
        }

        .badge-medium {
            background: #f59e0b;
            color: #111827;
        }

        .badge-low {
            background: #16a34a;
            color: white;
        }

        .badge-active {
            background: #dcfce7;
            color: #166534;
        }

        /*
        |--------------------------------------------------------------------------
        | Empty
        |--------------------------------------------------------------------------
        */

        .empty {
            padding: 40px 20px;
            text-align: center;
            color: #94a3b8;
        }

        .empty-icon {
            font-size: 38px;
            margin-bottom: 10px;
        }

        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1100px) {

            .cards {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .grid-2 {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 800px) {

            .sidebar {
                width: 70px;
            }

            .main {
                width: calc(100% - 70px);
            }

            .brand {
                font-size: 0;
                text-align: center;
            }

            .brand::before {
                content: "🛡️";
                font-size: 22px;
            }

            .brand small {
                display: none;
            }

            .nav-item span:not(.nav-icon) {
                display: none;
            }

            .menu-title {
                display: none;
            }

            .content {
                padding: 18px;
            }

            .score-layout {
                grid-template-columns: 1fr;
            }

            .risk-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

        }

        @media (max-width: 550px) {

            .cards {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
            }

            .topbar {
                padding: 0 15px;
            }

        }

    </style>

</head>


<body>

<div class="app">

    @include('partials.sidebar-navigation')

    {{-- Legacy navigation retained temporarily as a non-rendered reference. --}}
    @if(false)
    <aside class="sidebar">

        <div class="brand">

            🛡️ Guardium Center

            <small>
                Database Security Platform
            </small>

        </div>


        <div class="menu-title">
            Main
        </div>


        <a
            href="{{ route('dashboard') }}"
            class="nav-item active"
        >
            <span class="nav-icon">🏠</span>
            <span>Dashboard</span>
        </a>


        <div class="menu-title">
            Database
        </div>


        <a
            href="{{ route('database-connections.index') }}"
            class="nav-item"
        >
            <span class="nav-icon">🗄️</span>
            <span>Database Connections</span>
        </a>


        @if(Route::has('database-explorer.index'))

            <a
                href="{{ route('database-explorer.index') }}"
                class="nav-item"
            >
                <span class="nav-icon">🔎</span>
                <span>Database Explorer</span>
            </a>

        @endif


        @if(Route::has('database-users.index'))

            <a
                href="{{ route('database-users.index') }}"
                class="nav-item"
            >
                <span class="nav-icon">👤</span>
                <span>Database Users</span>
            </a>

        @endif


        @if(Route::has('database-privileges.index'))

            <a
                href="{{ route('database-privileges.index') }}"
                class="nav-item"
            >
                <span class="nav-icon">🔐</span>
                <span>Privileges</span>
            </a>

        @endif


        @if(Route::has('sensitive-data.index'))

            <a
                href="{{ route('sensitive-data.index') }}"
                class="nav-item"
            >
                <span class="nav-icon">🕵️</span>
                <span>Sensitive Data</span>
            </a>

        @endif


        <div class="menu-title">
            Security
        </div>


        @if(Route::has('security-audit.index'))

            <a
                href="{{ route('security-audit.index') }}"
                class="nav-item"
            >
                <span class="nav-icon">🛡️</span>
                <span>Security Audit</span>
            </a>

        @endif


        @if(Route::has('database-activities.index'))

            <a
                href="{{ route('database-activities.index') }}"
                class="nav-item"
            >
                <span class="nav-icon">📊</span>
                <span>Activity Monitoring</span>
            </a>

        @endif


    </aside>
    @endif


    {{-- ============================================================
         MAIN
    ============================================================= --}}

    <main class="main">


        {{-- TOPBAR --}}

        <header class="topbar">

            <div class="topbar-title">
                Database Security Center
            </div>


            <div class="topbar-status">

                <span class="status-dot"></span>

                System Online

            </div>

        </header>


        {{-- CONTENT --}}

        <div class="content dashboard-page">


            {{-- PAGE HEADER --}}

            <div class="page-header">

                <div>

                    <h1 class="page-title">
                        Security Dashboard
                    </h1>

                    <p class="page-description">
                        Database security monitoring and
                        risk management center.
                    </p>

                </div>


                @if(Route::has('security-audit.index'))

                    <a
                        href="{{ route('security-audit.index') }}"
                        class="btn btn-primary"
                    >
                        🔍 Security Audit
                    </a>

                @endif

            </div>


            {{-- ====================================================
                 STATISTICS
            ===================================================== --}}

            <div class="cards">


                {{-- Security Score --}}

                <div class="card">

                    <div class="card-label">
                        Security Score
                    </div>

                    <div class="card-value">

                        {{ $securityScore['score'] }}

                        <span style="
                            font-size:15px;
                            color:#94a3b8;
                        ">
                            / 100
                        </span>

                    </div>

                    <div class="card-description">

                        {{ $securityScore['level'] }}

                    </div>

                </div>


                {{-- Critical --}}

                <div class="card">

                    <div class="card-label">
                        Critical Risk
                    </div>

                    <div
                        class="card-value"
                        style="color:#991b1b;"
                    >
                        {{ $criticalFindings }}
                    </div>

                    <div class="card-description">
                        Critical findings
                    </div>

                </div>


                {{-- High --}}

                <div class="card">

                    <div class="card-label">
                        High Risk
                    </div>

                    <div
                        class="card-value"
                        style="color:#dc2626;"
                    >
                        {{ $highFindings }}
                    </div>

                    <div class="card-description">
                        High severity findings
                    </div>

                </div>


                {{-- Connections --}}

                <div class="card">

                    <div class="card-label">
                        Active Connections
                    </div>

                    <div class="card-value">

                        {{ $activeConnections }}

                    </div>

                    <div class="card-description">

                        {{ $totalConnections }}
                        total connections

                    </div>

                </div>


            </div>


            {{-- ====================================================
                 SECURITY SCORE
            ===================================================== --}}

            <div class="panel">

                <div class="panel-header">

                    <div>

                        <div class="panel-title">
                            Database Security Score
                        </div>

                        <div class="panel-subtitle">
                            Current security posture
                        </div>

                    </div>

                </div>


                <div class="panel-body">

                    <div class="score-layout">


                        {{-- SCORE CIRCLE --}}

                        <div>

                            <div
                                class="score-circle"
                                style="
                                    border-color:
                                    @if($securityScore['score'] >= 90)
                                        #16a34a
                                    @elseif($securityScore['score'] >= 75)
                                        #2563eb
                                    @elseif($securityScore['score'] >= 50)
                                        #f59e0b
                                    @else
                                        #dc2626
                                    @endif
                                "
                            >

                                <div class="score-number">

                                    {{ $securityScore['score'] }}

                                </div>

                                <div class="score-max">
                                    / 100
                                </div>

                                <div class="score-level">

                                    {{ $securityScore['level'] }}

                                </div>

                            </div>

                        </div>


                        {{-- RISK BREAKDOWN --}}

                        <div>

                            <div class="risk-grid">


                                <div class="
                                    risk-card
                                    risk-critical
                                ">

                                    <div
                                        class="risk-name"
                                        style="color:#991b1b;"
                                    >
                                        Critical
                                    </div>

                                    <div class="number">
                                        {{ $criticalFindings }}
                                    </div>

                                </div>


                                <div class="
                                    risk-card
                                    risk-high
                                ">

                                    <div
                                        class="risk-name"
                                        style="color:#dc2626;"
                                    >
                                        High
                                    </div>

                                    <div class="number">
                                        {{ $highFindings }}
                                    </div>

                                </div>


                                <div class="
                                    risk-card
                                    risk-medium
                                ">

                                    <div
                                        class="risk-name"
                                        style="color:#b45309;"
                                    >
                                        Medium
                                    </div>

                                    <div class="number">
                                        {{ $mediumFindings }}
                                    </div>

                                </div>


                                <div class="
                                    risk-card
                                    risk-low
                                ">

                                    <div
                                        class="risk-name"
                                        style="color:#15803d;"
                                    >
                                        Low
                                    </div>

                                    <div class="number">
                                        {{ $lowFindings }}
                                    </div>

                                </div>


                            </div>


                            <div style="
                                margin-top:20px;
                                color:#64748b;
                                font-size:13px;
                                line-height:1.7;
                            ">

                                Saat ini terdapat

                                <strong>
                                    {{ $totalFindings }}
                                </strong>

                                security finding yang
                                masih berstatus

                                <strong>
                                    OPEN
                                </strong>.

                                <br>

                                Segera periksa finding dengan
                                severity Critical dan High.

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ====================================================
                 FINDINGS + CONNECTIONS
            ===================================================== --}}

            <div class="grid-2">


                {{-- SECURITY FINDINGS --}}

                <div class="panel">

                    <div class="panel-header">

                        <div>

                            <div class="panel-title">
                                Security Findings
                            </div>

                            <div class="panel-subtitle">
                                Latest open security findings
                            </div>

                        </div>


                        @if(Route::has('security-audit.index'))

                            <a
                                href="{{ route('security-audit.index') }}"
                                style="
                                    color:#2563eb;
                                    font-size:12px;
                                    font-weight:600;
                                "
                            >
                                View All →
                            </a>

                        @endif

                    </div>


                    @if($recentSecurityFindings->count())

                        <div class="table-wrapper">

                            <table>

                                <thead>

                                    <tr>

                                        <th>
                                            Severity
                                        </th>

                                        <th>
                                            Finding
                                        </th>

                                        <th>
                                            Object
                                        </th>

                                        <th>
                                            Detected
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    @foreach(
                                        $recentSecurityFindings
                                        as $finding
                                    )

                                        @php

                                            $severity =
                                                strtoupper(
                                                    $finding->severity
                                                );

                                            $badgeClass =
                                                match($severity) {

                                                    'CRITICAL' =>
                                                        'badge-critical',

                                                    'HIGH' =>
                                                        'badge-high',

                                                    'MEDIUM' =>
                                                        'badge-medium',

                                                    default =>
                                                        'badge-low',

                                                };

                                        @endphp


                                        <tr>

                                            <td>

                                                <span class="
                                                    badge
                                                    {{ $badgeClass }}
                                                ">
                                                    {{ $severity }}
                                                </span>

                                            </td>


                                            <td>

                                                <strong>
                                                    {{ $finding->title }}
                                                </strong>

                                            </td>


                                            <td>

                                                {{ $finding->object_name ?? '-' }}

                                            </td>


                                            <td style="white-space: nowrap;">
                                                {{ $finding->detected_at ? $finding->detected_at->
                                                format('Y-m-d H:i') : '-' }}
                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    @else

                        <div class="empty">

                            <div class="empty-icon">
                                🛡️
                            </div>

                            <div>
                                No open security findings.
                            </div>

                        </div>

                    @endif

                </div>


                {{-- DATABASE CONNECTIONS --}}

                <div class="panel">

                    <div class="panel-header">

                        <div>

                            <div class="panel-title">
                                Database Connections
                            </div>

                            <div class="panel-subtitle">
                                Connected database servers
                            </div>

                        </div>


                        @if(
                            Route::has(
                                'database-connections.index'
                            )
                        )

                            <a
                                href="{{
                                    route(
                                        'database-connections.index'
                                    )
                                }}"
                                style="
                                    color:#2563eb;
                                    font-size:12px;
                                    font-weight:600;
                                "
                            >
                                View All →
                            </a>

                        @endif

                    </div>


                    @if($databaseConnections->count())

                        <div class="table-wrapper">

                            <table>

                                <thead>

                                    <tr>

                                        <th>
                                            Database
                                        </th>

                                        <th>
                                            Driver
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    @foreach(
                                        $databaseConnections
                                        as $connection
                                    )

                                        <tr>

                                            <td>

                                                <strong>
                                                    {{ $connection->name }}
                                                </strong>

                                                <div style="
                                                    color:#94a3b8;
                                                    font-size:11px;
                                                    margin-top:3px;
                                                ">

                                                    {{
                                                        $connection->database
                                                    }}

                                                </div>

                                            </td>


                                            <td>

                                                {{
                                                    strtoupper(
                                                        $connection->driver
                                                    )
                                                }}

                                            </td>


                                            <td>

                                                @if(
                                                    $connection->is_active
                                                )

                                                    <span class="
                                                        badge
                                                        badge-active
                                                    ">
                                                        ACTIVE
                                                    </span>

                                                @else

                                                    <span
                                                        class="badge"
                                                        style="
                                                            background:#f1f5f9;
                                                            color:#64748b;
                                                        "
                                                    >
                                                        INACTIVE
                                                    </span>

                                                @endif

                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    @else

                        <div class="empty">

                            <div class="empty-icon">
                                🗄️
                            </div>

                            <div>
                                Belum ada database connection.
                            </div>

                        </div>

                    @endif

                </div>

            </div>


            {{-- ====================================================
                 SECURITY SUMMARY
            ===================================================== --}}

            <div
                class="panel"
                style="margin-top:20px;"
            >

                <div class="panel-header">

                    <div>

                        <div class="panel-title">
                            Security Overview
                        </div>

                        <div class="panel-subtitle">
                            Current database security posture
                        </div>

                    </div>

                </div>


                <div class="panel-body">

                    <div style="
                        display:grid;
                        grid-template-columns:
                            repeat(3, 1fr);
                        gap:20px;
                    ">


                        <div>

                            <div style="
                                color:#64748b;
                                font-size:12px;
                            ">
                                Total Findings
                            </div>

                            <div style="
                                font-size:28px;
                                font-weight:700;
                                margin-top:5px;
                            ">
                                {{ $totalFindings }}
                            </div>

                        </div>


                        <div>

                            <div style="
                                color:#64748b;
                                font-size:12px;
                            ">
                                Open Findings
                            </div>

                            <div style="
                                font-size:28px;
                                font-weight:700;
                                margin-top:5px;
                            ">
                                {{ $totalFindings }}
                            </div>

                        </div>


                        <div>

                            <div style="
                                color:#64748b;
                                font-size:12px;
                            ">
                                Active Databases
                            </div>

                            <div style="
                                font-size:28px;
                                font-weight:700;
                                margin-top:5px;
                            ">
                                {{ $activeConnections }}
                            </div>

                        </div>


                    </div>

                </div>

            </div>


        </div>

    </main>

</div>

</body>

</html>
