<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Activity Monitoring
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


        .app {
            display: flex;
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
            flex: 1;
            min-width: 0;
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
        }

        .topbar-status {
            color: #64748b;
            font-size: 13px;
        }

        .content {
            padding: 28px;
        }


        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        .page-header {
            margin-bottom: 25px;
        }

        .page-title {
            margin: 0;
            font-size: 26px;
        }

        .page-description {
            margin-top: 7px;
            color: #64748b;
            font-size: 14px;
        }


        /*
        |--------------------------------------------------------------------------
        | Stats
        |--------------------------------------------------------------------------
        */

        .stats {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
        }

        .stat-label {
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-top: 8px;
        }


        /*
        |--------------------------------------------------------------------------
        | Panel
        |--------------------------------------------------------------------------
        */

        .panel {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .panel-header {
            padding: 18px 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .panel-title {
            font-size: 15px;
            font-weight: 700;
        }

        .panel-body {
            padding: 20px;
        }


        /*
        |--------------------------------------------------------------------------
        | Filter
        |--------------------------------------------------------------------------
        */

        .filters {
            display: grid;
            grid-template-columns:
                2fr 1fr 1fr 1fr auto;
            gap: 10px;
            align-items: end;
        }

        label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        input,
        select {
            width: 100%;
            padding: 10px 11px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            background: white;
            font-size: 13px;
        }

        .btn {
            padding: 10px 16px;
            border: 0;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 700;
            font-size: 13px;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #334155;
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
            font-size: 10px;
            text-align: left;
            text-transform: uppercase;
            padding: 12px 15px;
            white-space: nowrap;
        }

        td {
            padding: 12px 15px;
            border-top: 1px solid #f1f5f9;
            font-size: 13px;
            vertical-align: top;
        }

        tr:hover td {
            background: #f8fafc;
        }


        /*
        |--------------------------------------------------------------------------
        | Query
        |--------------------------------------------------------------------------
        */

        .query {
            max-width: 400px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-family:
                Consolas,
                monospace;
            color: #475569;
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

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .action {
            background: #eff6ff;
            color: #1d4ed8;
        }


        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        .pagination {
            padding: 15px 20px;
            border-top: 1px solid #e2e8f0;
        }


        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        @media(max-width: 1000px) {

            .stats {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .filters {
                grid-template-columns:
                    1fr 1fr;
            }

        }


        @media(max-width: 700px) {

            .sidebar {
                width: 70px;
            }

            .brand {
                font-size: 0;
                text-align: center;
            }

            .brand::before {
                content: "🛡️";
                font-size: 22px;
            }

            .brand small,
            .menu-title {
                display: none;
            }

            .nav-item span:not(.nav-icon) {
                display: none;
            }

            .content {
                padding: 18px;
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
            class="nav-item"
        >

            <span class="nav-icon">
                🏠
            </span>

            <span>
                Dashboard
            </span>

        </a>


        <div class="menu-title">
            Database
        </div>


        <a
            href="{{ route('database-connections.index') }}"
            class="nav-item"
        >

            <span class="nav-icon">
                🗄️
            </span>

            <span>
                Database Connections
            </span>

        </a>


        @if(Route::has('sql-query.index'))

            <a
                href="{{ route('sql-query.index') }}"
                class="nav-item"
            >

                <span class="nav-icon">
                    💻
                </span>

                <span>
                    SQL Query Console
                </span>

            </a>

        @endif


        <div class="menu-title">
            Monitoring
        </div>


        <a
            href="{{ route('database-activities.index') }}"
            class="nav-item active"
        >

            <span class="nav-icon">
                📊
            </span>

            <span>
                Activity Monitoring
            </span>

        </a>


        @if(Route::has('security-audit.index'))

            <a
                href="{{ route('security-audit.index') }}"
                class="nav-item"
            >

                <span class="nav-icon">
                    🛡️
                </span>

                <span>
                    Security Audit
                </span>

            </a>

        @endif

    </aside>
    @endif


    {{-- MAIN --}}

    <main class="main">


        <header class="topbar">

            <div class="topbar-title">
                Activity Monitoring
            </div>

            <div class="topbar-status">
                Database Activity Monitor
            </div>

        </header>


        <div class="content">


            <div class="page-header">

                <h1 class="page-title">
                    Activity Monitoring
                </h1>

                <p class="page-description">
                    Monitor seluruh aktivitas database
                    yang tercatat oleh aplikasi.
                </p>

            </div>


            {{-- ====================================================
                 STATISTICS
            ===================================================== --}}

            <div class="stats">


                <div class="stat">

                    <div class="stat-label">
                        Total Activities
                    </div>

                    <div class="stat-value">
                        {{ number_format($totalActivities) }}
                    </div>

                </div>


                <div class="stat">

                    <div class="stat-label">
                        Successful
                    </div>

                    <div
                        class="stat-value"
                        style="color:#16a34a;"
                    >
                        {{ number_format($successfulActivities) }}
                    </div>

                </div>


                <div class="stat">

                    <div class="stat-label">
                        Failed
                    </div>

                    <div
                        class="stat-value"
                        style="color:#dc2626;"
                    >
                        {{ number_format($failedActivities) }}
                    </div>

                </div>


                <div class="stat">

                    <div class="stat-label">
                        Avg Execution
                    </div>

                    <div class="stat-value">

                        {{
                            $averageExecutionTime !== null
                                ? number_format(
                                    $averageExecutionTime,
                                    1
                                )
                                : '0'
                        }}

                        <span style="
                            font-size:13px;
                            color:#94a3b8;
                        ">
                            ms
                        </span>

                    </div>

                </div>

            </div>


            {{-- ====================================================
                 FILTERS
            ===================================================== --}}

            <div class="panel">

                <div class="panel-header">

                    <div class="panel-title">
                        Activity Filters
                    </div>

                </div>


                <div class="panel-body">

                    <form
                        method="GET"
                        action="{{ route('database-activities.index') }}"
                    >

                        <div class="filters">


                            {{-- Search --}}

                            <div>

                                <label>
                                    Search
                                </label>

                                <input
                                    type="text"
                                    name="search"
                                    value="{{ $search }}"
                                    placeholder="SQL, username, database..."
                                >

                            </div>


                            {{-- Action --}}

                            <div>

                                <label>
                                    Action
                                </label>

                                <select name="action">

                                    <option value="">
                                        All Actions
                                    </option>

                                    @foreach([
                                        'SELECT',
                                        'INSERT',
                                        'UPDATE',
                                        'DELETE',
                                        'CREATE',
                                        'ALTER',
                                        'DROP',
                                        'LOGIN',
                                        'SCAN',
                                    ] as $item)

                                        <option
                                            value="{{ $item }}"
                                            @selected(
                                                $action === $item
                                            )
                                        >
                                            {{ $item }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            {{-- Status --}}

                            <div>

                                <label>
                                    Status
                                </label>

                                <select name="status">

                                    <option value="">
                                        All Status
                                    </option>

                                    <option
                                        value="SUCCESS"
                                        @selected(
                                            $status === 'SUCCESS'
                                        )
                                    >
                                        SUCCESS
                                    </option>

                                    <option
                                        value="FAILED"
                                        @selected(
                                            $status === 'FAILED'
                                        )
                                    >
                                        FAILED
                                    </option>

                                </select>

                            </div>


                            {{-- Connection --}}

                            <div>

                                <label>
                                    Connection
                                </label>

                                <select
                                    name="database_connection_id"
                                >

                                    <option value="">
                                        All Databases
                                    </option>


                                    @foreach(
                                        $connections
                                        as $connection
                                    )

                                        <option
                                            value="{{
                                                $connection->id
                                            }}"
                                            @selected(
                                                (string)
                                                $connectionId
                                                ===
                                                (string)
                                                $connection->id
                                            )
                                        >

                                            {{
                                                $connection->name
                                            }}

                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            {{-- Buttons --}}

                            <div style="
                                display:flex;
                                gap:7px;
                            ">

                                <button
                                    type="submit"
                                    class="
                                        btn
                                        btn-primary
                                    "
                                >
                                    Filter
                                </button>


                                <a
                                    href="{{
                                        route(
                                            'database-activities.index'
                                        )
                                    }}"
                                    class="
                                        btn
                                        btn-secondary
                                    "
                                >
                                    Reset
                                </a>

                            </div>


                        </div>

                    </form>

                </div>

            </div>


            {{-- ====================================================
                 ACTIVITY TABLE
            ===================================================== --}}

            <div class="panel">

                <div class="panel-header">

                    <div class="panel-title">
                        Database Activities
                    </div>

                </div>


                @if($activities->count())


                    <div class="table-wrapper">

                        <table>

                            <thead>

                                <tr>

                                    <th>
                                        Time
                                    </th>

                                    <th>
                                        Database
                                    </th>

                                    <th>
                                        User
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                    <th>
                                        Query
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Duration
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                @foreach(
                                    $activities
                                    as $activity
                                )

                                    <tr>


                                        {{-- TIME --}}

                                        <td>

                                            <strong>

                                                {{
                                                    $activity
                                                        ->executed_at
                                                        ?->format(
                                                            'Y-m-d'
                                                        )
                                                }}

                                            </strong>

                                            <div style="
                                                color:#94a3b8;
                                                font-size:11px;
                                                margin-top:3px;
                                            ">

                                                {{
                                                    $activity
                                                        ->executed_at
                                                        ?->format(
                                                            'H:i:s'
                                                        )
                                                }}

                                            </div>

                                        </td>


                                        {{-- DATABASE --}}

                                        <td>

                                            <strong>
                                                {{
                                                    $activity
                                                        ->database_name
                                                    ?? '-'
                                                }}
                                            </strong>

                                            @if(
                                                $activity
                                                    ->schema_name
                                            )

                                                <div style="
                                                    color:#94a3b8;
                                                    font-size:11px;
                                                ">

                                                    {{
                                                        $activity
                                                            ->schema_name
                                                    }}

                                                </div>

                                            @endif

                                        </td>


                                        {{-- USER --}}

                                        <td>

                                            {{
                                                $activity
                                                    ->username
                                                ?? '-'
                                            }}

                                            @if(
                                                $activity
                                                    ->client_ip
                                            )

                                                <div style="
                                                    color:#94a3b8;
                                                    font-size:11px;
                                                ">

                                                    {{
                                                        $activity
                                                            ->client_ip
                                                    }}

                                                </div>

                                            @endif

                                        </td>


                                        {{-- ACTION --}}

                                        <td>

                                            <span class="
                                                badge
                                                action
                                            ">

                                                {{
                                                    strtoupper(
                                                        $activity
                                                            ->action
                                                        ?? '-'
                                                    )
                                                }}

                                            </span>

                                        </td>


                                        {{-- QUERY --}}

                                        <td>

                                            <div
                                                class="query"
                                                title="{{
                                                    $activity->query
                                                }}"
                                            >

                                                {{
                                                    $activity->query
                                                }}

                                            </div>

                                        </td>


                                        {{-- STATUS --}}

                                        <td>

                                            @if(
                                                strtoupper(
                                                    $activity->status
                                                )
                                                ===
                                                'SUCCESS'
                                            )

                                                <span class="
                                                    badge
                                                    success
                                                ">
                                                    SUCCESS
                                                </span>

                                            @else

                                                <span class="
                                                    badge
                                                    failed
                                                ">
                                                    FAILED
                                                </span>

                                            @endif

                                        </td>


                                        {{-- EXECUTION TIME --}}

                                        <td style="
                                            white-space:nowrap;
                                        ">

                                            {{
                                                $activity
                                                    ->execution_time_ms
                                                ?? 0
                                            }}

                                            ms

                                        </td>


                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>


                    {{-- PAGINATION --}}

                    <div class="pagination">

                        {{ $activities->links() }}

                    </div>


                @else


                    <div class="empty">

                        <div style="
                            font-size:38px;
                            margin-bottom:10px;
                        ">
                            📊
                        </div>

                        Tidak ada aktivitas database
                        yang ditemukan.

                    </div>


                @endif

            </div>


        </div>

    </main>

</div>

</body>

</html>
