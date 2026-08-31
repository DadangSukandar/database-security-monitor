<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        SQL Query Console
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
            padding: 28px;
        }


        /*
        |--------------------------------------------------------------------------
        | Page Header
        |--------------------------------------------------------------------------
        */

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
        | Panel
        |--------------------------------------------------------------------------
        */

        .panel {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 22px;
        }

        .panel-header {
            padding: 18px 20px;
            border-bottom: 1px solid #e2e8f0;
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
        | Form
        |--------------------------------------------------------------------------
        */

        label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        select {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            background: white;
            font-size: 14px;
        }

        textarea {
            width: 100%;
            min-height: 250px;
            padding: 15px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #0f172a;
            color: #e2e8f0;
            font-family:
                Consolas,
                Monaco,
                monospace;
            font-size: 14px;
            line-height: 1.6;
            resize: vertical;
            outline: none;
        }

        textarea:focus {
            border-color: #2563eb;
        }


        /*
        |--------------------------------------------------------------------------
        | Execute Button
        |--------------------------------------------------------------------------
        */

        .execute-area {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }

        .readonly-info {
            color: #64748b;
            font-size: 12px;
        }

        .execute-button {
            border: 0;
            background: #2563eb;
            color: white;
            padding: 11px 20px;
            border-radius: 7px;
            font-weight: 700;
            cursor: pointer;
        }

        .execute-button:hover {
            background: #1d4ed8;
        }


        /*
        |--------------------------------------------------------------------------
        | Error
        |--------------------------------------------------------------------------
        */

        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 14px;
            border-radius: 7px;
            margin-top: 20px;
            font-size: 13px;
        }


        /*
        |--------------------------------------------------------------------------
        | Result Info
        |--------------------------------------------------------------------------
        */

        .result-info {
            display: flex;
            gap: 20px;
            color: #64748b;
            font-size: 12px;
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
            text-transform: uppercase;
            text-align: left;
            padding: 12px 15px;
            white-space: nowrap;
        }

        td {
            padding: 12px 15px;
            border-top: 1px solid #f1f5f9;
            font-size: 13px;
            white-space: nowrap;
        }

        tr:hover td {
            background: #f8fafc;
        }


        /*
        |--------------------------------------------------------------------------
        | Empty
        |--------------------------------------------------------------------------
        */

        .empty {
            padding: 40px;
            text-align: center;
            color: #94a3b8;
        }


        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        @media(max-width: 800px) {

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


        <a
            href="{{ route('sql-query.index') }}"
            class="nav-item active"
        >

            <span class="nav-icon">
                💻
            </span>

            <span>
                SQL Query Console
            </span>

        </a>


        @if(Route::has('database-explorer.index'))

            <a
                href="{{ route('database-explorer.index') }}"
                class="nav-item"
            >

                <span class="nav-icon">
                    🔎
                </span>

                <span>
                    Database Explorer
                </span>

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

                <span class="nav-icon">
                    🛡️
                </span>

                <span>
                    Security Audit
                </span>

            </a>

        @endif


        @if(Route::has('database-activities.index'))

            <a
                href="{{ route('database-activities.index') }}"
                class="nav-item"
            >

                <span class="nav-icon">
                    📊
                </span>

                <span>
                    Activity Monitoring
                </span>

            </a>

        @endif


    </aside>
    @endif


    {{-- ============================================================
         MAIN
    ============================================================= --}}

    <main class="main">


        <header class="topbar">

            <div class="topbar-title">
                SQL Query Console
            </div>


            <div class="topbar-status">

                <span class="status-dot"></span>

                Read-only mode

            </div>

        </header>


        <div class="content">


            <h1 class="page-title">
                SQL Query Console
            </h1>


            <p class="page-description">
                Jalankan query read-only pada database
                yang sudah terhubung.
            </p>


            {{-- ====================================================
                 ERROR VALIDATION
            ===================================================== --}}

            @if($errors->any())

                <div class="error">

                    @foreach($errors->all() as $error)

                        <div>
                            {{ $error }}
                        </div>

                    @endforeach

                </div>

            @endif


            {{-- ====================================================
                 QUERY FORM
            ===================================================== --}}

            <div class="panel">

                <div class="panel-header">

                    <div class="panel-title">
                        Execute SQL
                    </div>

                    <div class="panel-subtitle">
                        Hanya SELECT, SHOW, DESCRIBE,
                        DESC dan EXPLAIN yang diperbolehkan.
                    </div>

                </div>


                <div class="panel-body">

                    <form
                        method="POST"
                        action="{{ route('sql-query.execute') }}"
                    >

                        @csrf


                        {{-- DATABASE --}}

                        <div style="
                            margin-bottom:20px;
                        ">

                            <label>
                                Database Connection
                            </label>


                            <select
                                name="database_connection_id"
                                required
                            >

                                <option value="">
                                    -- Pilih Database --
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
                                            isset(
                                                $selectedConnection
                                            )
                                            &&
                                            $selectedConnection->id
                                            ==
                                            $connection->id
                                        )
                                    >

                                        {{
                                            $connection->name
                                        }}

                                        -
                                        {{
                                            strtoupper(
                                                $connection->driver
                                            )
                                        }}

                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- SQL --}}

                        <div>

                            <label>
                                SQL Query
                            </label>


                            <textarea
                                name="query"
                                placeholder="SELECT * FROM users LIMIT 20;"
                                spellcheck="false"
                            >{{ $query ?? '' }}</textarea>

                        </div>


                        {{-- BUTTON --}}

                        <div class="execute-area">

                            <div class="readonly-info">

                                🔒 Read-only mode

                                <br>

                                Query perubahan data
                                dinonaktifkan.

                            </div>


                            <button
                                type="submit"
                                class="execute-button"
                            >

                                ▶ Execute Query

                            </button>

                        </div>

                    </form>

                </div>

            </div>


            {{-- ====================================================
                 ERROR DATABASE
            ===================================================== --}}

            @isset($error)

                <div class="error">

                    <strong>
                        Query gagal dijalankan
                    </strong>

                    <div style="
                        margin-top:7px;
                    ">

                        {{ $error }}

                    </div>

                </div>

            @endisset


            {{-- ====================================================
                 RESULT
            ===================================================== --}}

            @isset($rows)

                <div class="panel">

                    <div class="panel-header">

                        <div>

                            <div class="panel-title">
                                Query Result
                            </div>

                            <div class="panel-subtitle">

                                <div class="result-info">

                                    <span>

                                        Rows:
                                        <strong>
                                            {{ $resultCount }}
                                        </strong>

                                    </span>


                                    <span>

                                        Execution:
                                        <strong>
                                            {{ $executionTimeMs }} ms
                                        </strong>

                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>


                    @if(count($rows) > 0)

                        <div class="table-wrapper">

                            <table>

                                <thead>

                                    <tr>

                                        @foreach(
                                            $columns
                                            as $column
                                        )

                                            <th>
                                                {{ $column }}
                                            </th>

                                        @endforeach

                                    </tr>

                                </thead>


                                <tbody>

                                    @foreach(
                                        $rows
                                        as $row
                                    )

                                        <tr>

                                            @foreach(
                                                $columns
                                                as $column
                                            )

                                                <td>

                                                    @php

                                                        $value =
                                                            $row[$column]
                                                            ?? null;

                                                    @endphp


                                                    @if(
                                                        is_null(
                                                            $value
                                                        )
                                                    )

                                                        <span style="
                                                            color:#94a3b8;
                                                            font-style:italic;
                                                        ">
                                                            NULL
                                                        </span>

                                                    @else

                                                        {{
                                                            is_scalar(
                                                                $value
                                                            )
                                                            ? $value
                                                            : json_encode(
                                                                $value
                                                            )
                                                        }}

                                                    @endif

                                                </td>

                                            @endforeach

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    @else

                        <div class="empty">

                            Query berhasil dijalankan,
                            tetapi tidak menghasilkan row.

                        </div>

                    @endif

                </div>

            @endisset


        </div>

    </main>

</div>

</body>

</html>
