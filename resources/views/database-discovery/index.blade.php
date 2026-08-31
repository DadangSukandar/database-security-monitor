@extends('app')

@section('content')

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Database Discovery
            </h2>

            <div class="text-muted">
                Database, table and column inventory
            </div>

        </div>

    </div>


    {{-- ALERT SUCCESS --}}

    @if(session('success'))

        <div class="alert alert-success">
            {{ session('success') }}
        </div>

    @endif


    {{-- ALERT ERROR --}}

    @if(session('error'))

        <div class="alert alert-danger">
            {{ session('error') }}
        </div>

    @endif


    {{-- STATISTICS --}}

    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <div class="card shadow-sm">

                <div class="card-body">

                    <div class="text-muted small">
                        DISCOVERED DATABASES
                    </div>

                    <div class="fs-2 fw-bold">
                        {{ number_format($totalDatabases) }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card shadow-sm">

                <div class="card-body">

                    <div class="text-muted small">
                        TABLES
                    </div>

                    <div class="fs-2 fw-bold">
                        {{ number_format($totalTables) }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card shadow-sm">

                <div class="card-body">

                    <div class="text-muted small">
                        COLUMNS
                    </div>

                    <div class="fs-2 fw-bold">
                        {{ number_format($totalColumns) }}
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- CONNECTIONS --}}

    <div class="card shadow-sm mb-4">

        <div class="card-header">

            <strong>
                Database Connections
            </strong>

        </div>


        <div class="table-responsive">

            <table class="table table-hover mb-0 align-middle">

                <thead>

                    <tr>

                        <th>
                            Name
                        </th>

                        <th>
                            Driver
                        </th>

                        <th>
                            Host
                        </th>

                        <th>
                            Database
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse(
                        $connections
                        as $connection
                    )

                        <tr>

                            <td>

                                <strong>
                                    {{ $connection->name }}
                                </strong>

                            </td>


                            <td>

                                <span class="badge text-bg-primary">

                                    {{ strtoupper(
                                        $connection->driver
                                    ) }}

                                </span>

                            </td>


                            <td>
                                {{ $connection->host }}
                            </td>


                            <td>
                                {{ $connection->database }}
                            </td>


                            <td>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'database-discovery.scan',
                                        $connection
                                    ) }}"
                                    class="d-inline"
                                >

                                    @csrf

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-primary"
                                    >
                                        🔍 Scan
                                    </button>

                                </form>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="text-center py-4"
                            >
                                Belum ada database connection.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- DISCOVERED DATABASES --}}

    <div class="card shadow-sm">

        <div class="card-header">

            <strong>
                Discovered Databases
            </strong>

        </div>


        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead>

                    <tr>

                        <th>
                            Database
                        </th>

                        <th>
                            Engine
                        </th>

                        <th>
                            Version
                        </th>

                        <th>
                            Tables
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse(
                        $databases
                        as $database
                    )

                        <tr>

                            <td>

                                <strong>
                                    {{ $database->name }}
                                </strong>

                            </td>


                            <td>

                                <span class="badge text-bg-dark">

                                    {{ $database->engine }}

                                </span>

                            </td>


                            <td>

                                <small>

                                    {{ $database->version }}

                                </small>

                            </td>


                            <td>

                                <span class="badge text-bg-primary">

                                    {{ number_format(
                                        $database->tables_count
                                    ) }}

                                </span>

                            </td>


                            <td>

                                <a
                                    href="{{ route(
                                        'database-discovery.show',
                                        $database
                                    ) }}"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Open
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="text-center py-5"
                            >

                                <div class="fs-3">
                                    🔍
                                </div>

                                <div class="mt-2">
                                    Belum ada hasil discovery.
                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection