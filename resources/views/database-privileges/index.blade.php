@extends('app')

@section('content')

<div class="container-fluid py-4">

    {{-- HEADER --}}

    <div class="d-flex justify-content-between align-items-start mb-4">

        <div>

            <h1 class="mb-1">
                Database Privileges
            </h1>

            <p class="text-muted mb-0">
                Monitor hak akses user terhadap database dan table.
            </p>

        </div>


        <div class="d-flex gap-2 flex-wrap">

            @foreach($connections as $connection)

                <form
                    method="POST"
                    action="{{ route(
                        'database-privileges.scan',
                        $connection
                    ) }}"
                >

                    @csrf

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        🔍 Scan {{ $connection->name }}
                    </button>

                </form>

            @endforeach

        </div>

    </div>


    {{-- SUCCESS --}}

    @if(session('success'))

        <div class="alert alert-success">

            {{ session('success') }}

        </div>

    @endif


    {{-- ERRORS --}}

    @if($errors->any())

        <div class="alert alert-danger">

            @foreach($errors->all() as $error)

                <div>
                    {{ $error }}
                </div>

            @endforeach

        </div>

    @endif


    {{-- STATISTICS --}}

    <div class="row g-3 mb-4">

        <div class="col-md-3">

            <div class="card shadow-sm">

                <div class="card-body">

                    <div class="text-muted">
                        TOTAL PRIVILEGES
                    </div>

                    <div class="display-6 fw-bold">
                        {{ $totalPrivileges }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card shadow-sm">

                <div class="card-body">

                    <div class="text-muted">
                        HIGH RISK
                    </div>

                    <div class="display-6 fw-bold text-danger">
                        {{ $highRisk }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card shadow-sm">

                <div class="card-body">

                    <div class="text-muted">
                        MEDIUM RISK
                    </div>

                    <div class="display-6 fw-bold text-warning">
                        {{ $mediumRisk }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card shadow-sm">

                <div class="card-body">

                    <div class="text-muted">
                        GRANTABLE
                    </div>

                    <div class="display-6 fw-bold text-danger">
                        {{ $grantable }}
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- FILTER --}}

    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <form
                method="GET"
                class="row g-2"
            >

                <div class="col-md-4">

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Search user, table, database..."
                        value="{{ request('search') }}"
                    >

                </div>


                <div class="col-md-3">

                    <select
                        name="connection"
                        class="form-select"
                    >

                        <option value="">
                            All Connections
                        </option>

                        @foreach($connections as $connection)

                            <option
                                value="{{ $connection->id }}"
                                @selected(
                                    request('connection')
                                    == $connection->id
                                )
                            >
                                {{ $connection->name }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="col-md-2">

                    <select
                        name="privilege"
                        class="form-select"
                    >

                        <option value="">
                            All Privileges
                        </option>

                        <option
                            value="SELECT"
                            @selected(
                                request('privilege')
                                === 'SELECT'
                            )
                        >
                            SELECT
                        </option>

                        <option
                            value="INSERT"
                            @selected(
                                request('privilege')
                                === 'INSERT'
                            )
                        >
                            INSERT
                        </option>

                        <option
                            value="UPDATE"
                            @selected(
                                request('privilege')
                                === 'UPDATE'
                            )
                        >
                            UPDATE
                        </option>

                        <option
                            value="DELETE"
                            @selected(
                                request('privilege')
                                === 'DELETE'
                            )
                        >
                            DELETE
                        </option>

                    </select>

                </div>


                <div class="col-md-2">

                    <select
                        name="risk"
                        class="form-select"
                    >

                        <option value="">
                            All Risk
                        </option>

                        <option
                            value="HIGH"
                            @selected(
                                request('risk')
                                === 'HIGH'
                            )
                        >
                            HIGH
                        </option>

                        <option
                            value="MEDIUM"
                            @selected(
                                request('risk')
                                === 'MEDIUM'
                            )
                        >
                            MEDIUM
                        </option>

                        <option
                            value="LOW"
                            @selected(
                                request('risk')
                                === 'LOW'
                            )
                        >
                            LOW
                        </option>

                    </select>

                </div>


                <div class="col-md-1">

                    <button
                        class="btn btn-primary w-100"
                    >
                        🔍
                    </button>

                </div>

            </form>

        </div>

    </div>


    {{-- TABLE --}}

    <div class="card shadow-sm">

        <div class="card-header">

            <strong>
                Access Control Matrix
            </strong>

        </div>


        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead>

                    <tr>

                        <th>
                            User
                        </th>

                        <th>
                            Host
                        </th>

                        <th>
                            Database
                        </th>

                        <th>
                            Schema
                        </th>

                        <th>
                            Table
                        </th>

                        <th>
                            Privilege
                        </th>

                        <th>
                            Grantable
                        </th>

                        <th>
                            Risk
                        </th>

                        <th>
                            Reason
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse(
                        $privileges as $privilege
                    )

                        <tr>

                            <td>

                                <strong>
                                    {{ $privilege->username }}
                                </strong>

                            </td>


                            <td>

                                <code>
                                    {{
                                        $privilege->host
                                        ?? '—'
                                    }}
                                </code>

                            </td>


                            <td>

                                {{ $privilege->database_name }}

                            </td>


                            <td>

                                {{
                                    $privilege->schema_name
                                    ?? '—'
                                }}

                            </td>


                            <td>

                                <strong>
                                    {{ $privilege->table_name }}
                                </strong>

                            </td>


                            <td>

                                @if(
                                    $privilege->privilege
                                    === 'SELECT'
                                )

                                    <span
                                        class="badge text-bg-primary"
                                    >
                                        SELECT
                                    </span>

                                @elseif(
                                    $privilege->privilege
                                    === 'INSERT'
                                )

                                    <span
                                        class="badge text-bg-warning"
                                    >
                                        INSERT
                                    </span>

                                @elseif(
                                    $privilege->privilege
                                    === 'UPDATE'
                                )

                                    <span
                                        class="badge text-bg-warning"
                                    >
                                        UPDATE
                                    </span>

                                @elseif(
                                    $privilege->privilege
                                    === 'DELETE'
                                )

                                    <span
                                        class="badge text-bg-danger"
                                    >
                                        DELETE
                                    </span>

                                @else

                                    <span
                                        class="badge text-bg-secondary"
                                    >
                                        {{
                                            $privilege->privilege
                                        }}
                                    </span>

                                @endif

                            </td>


                            <td>

                                @if(
                                    $privilege->is_grantable
                                )

                                    <span
                                        class="badge text-bg-danger"
                                    >
                                        YES
                                    </span>

                                @else

                                    <span
                                        class="badge text-bg-secondary"
                                    >
                                        NO
                                    </span>

                                @endif

                            </td>


                            <td>

                                @if(
                                    $privilege->risk_level
                                    === 'HIGH'
                                )

                                    <span
                                        class="badge text-bg-danger"
                                    >
                                        HIGH
                                    </span>

                                @elseif(
                                    $privilege->risk_level
                                    === 'MEDIUM'
                                )

                                    <span
                                        class="badge text-bg-warning"
                                    >
                                        MEDIUM
                                    </span>

                                @else

                                    <span
                                        class="badge text-bg-success"
                                    >
                                        LOW
                                    </span>

                                @endif

                            </td>


                            <td>

                                <small class="text-muted">

                                    {{
                                        $privilege->risk_reason
                                    }}

                                </small>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="9"
                                class="text-center py-5"
                            >

                                <div class="text-muted">

                                    Belum ada privilege
                                    yang di-scan.

                                </div>

                                <div class="mt-2">

                                    Klik tombol
                                    <strong>
                                        Scan
                                    </strong>
                                    untuk memulai.

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div class="card-footer">

            {{ $privileges->links() }}

        </div>

    </div>

</div>

@endsection