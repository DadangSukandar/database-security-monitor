@extends('app')

@section('content')

<div class="container-fluid py-4">

    {{-- HEADER --}}

    <div class="d-flex justify-content-between align-items-start mb-4">

        <div>

            <h1 class="mb-1">
                Database Users
            </h1>

            <p class="text-muted mb-0">
                Monitor database accounts dan administrative privileges.
            </p>

        </div>


        <div>

            @foreach($connections as $connection)

                <form
                    method="POST"
                    action="{{ route(
                        'database-users.scan',
                        $connection
                    ) }}"
                    class="d-inline"
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
                        TOTAL USERS
                    </div>

                    <div class="display-6 fw-bold">
                        {{ $totalUsers }}
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
                        {{ $highRiskUsers }}
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
                        {{ $mediumRiskUsers }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card shadow-sm">

                <div class="card-body">

                    <div class="text-muted">
                        SUPER USERS
                    </div>

                    <div class="display-6 fw-bold text-danger">
                        {{ $superUsers }}
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
                        placeholder="Search username or host..."
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

                        @foreach(
                            $connections
                            as $connection
                        )

                            <option
                                value="{{ $connection->id }}"
                                @selected(
                                    request(
                                        'connection'
                                    )
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
                        name="risk"
                        class="form-select"
                    >

                        <option value="">
                            All Risk
                        </option>

                        <option
                            value="high"
                            @selected(
                                request('risk')
                                === 'high'
                            )
                        >
                            HIGH
                        </option>

                        <option
                            value="medium"
                            @selected(
                                request('risk')
                                === 'medium'
                            )
                        >
                            MEDIUM
                        </option>

                        <option
                            value="low"
                            @selected(
                                request('risk')
                                === 'low'
                            )
                        >
                            LOW
                        </option>

                    </select>

                </div>


                <div class="col-md-2">

                    <button
                        class="btn btn-primary w-100"
                    >
                        Filter
                    </button>

                </div>


                <div class="col-md-1">

                    <a
                        href="{{ route(
                            'database-users.index'
                        ) }}"
                        class="btn btn-outline-secondary w-100"
                    >
                        Reset
                    </a>

                </div>

            </form>

        </div>

    </div>


    {{-- USERS TABLE --}}

    <div class="card shadow-sm">

        <div class="card-header">

            <strong>
                Database Accounts
            </strong>

        </div>


        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead>

                    <tr>

                        <th>
                            Username
                        </th>

                        <th>
                            Host
                        </th>

                        <th>
                            Connection
                        </th>

                        <th>
                            Login
                        </th>

                        <th>
                            Privileges
                        </th>

                        <th>
                            Risk
                        </th>

                        <th>
                            Last Scan
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse(
                        $users as $user
                    )

                        <tr>

                            <td>

                                <strong>
                                    {{ $user->username }}
                                </strong>

                                @if(
                                    $user->is_superuser
                                )

                                    <span
                                        class="badge text-bg-danger"
                                    >
                                        SUPER
                                    </span>

                                @endif

                            </td>


                            <td>

                                <code>
                                    {{
                                        $user->host
                                        ?? '—'
                                    }}
                                </code>

                            </td>


                            <td>

                                {{
                                    optional(
                                        $user
                                            ->databaseConnection
                                    )->name
                                }}

                            </td>


                            <td>

                                @if(
                                    $user->can_login
                                )

                                    <span
                                        class="badge text-bg-success"
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

                                <div class="small">

                                    @if(
                                        $user
                                            ->can_create_database
                                    )

                                        <span
                                            class="badge text-bg-secondary"
                                        >
                                            CREATE DB
                                        </span>

                                    @endif


                                    @if(
                                        $user
                                            ->can_create_role
                                    )

                                        <span
                                            class="badge text-bg-secondary"
                                        >
                                            CREATE ROLE
                                        </span>

                                    @endif


                                    @if(
                                        $user->can_grant
                                    )

                                        <span
                                            class="badge text-bg-warning"
                                        >
                                            GRANT
                                        </span>

                                    @endif


                                    @if(
                                        $user
                                            ->is_replication
                                    )

                                        <span
                                            class="badge text-bg-secondary"
                                        >
                                            REPLICATION
                                        </span>

                                    @endif


                                    @if(
                                        $user->bypass_rls
                                    )

                                        <span
                                            class="badge text-bg-danger"
                                        >
                                            BYPASS RLS
                                        </span>

                                    @endif


                                    @if(
                                        !$user->is_superuser
                                        &&
                                        !$user->can_create_database
                                        &&
                                        !$user->can_create_role
                                        &&
                                        !$user->can_grant
                                    )

                                        <span
                                            class="text-muted"
                                        >
                                            Standard
                                        </span>

                                    @endif

                                </div>

                            </td>


                            <td>

                                @if(
                                    $user->risk_level
                                    === 'HIGH'
                                )

                                    <span
                                        class="badge text-bg-danger"
                                    >
                                        HIGH
                                    </span>

                                @elseif(
                                    $user->risk_level
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
                                        optional(
                                            $user
                                                ->last_scanned_at
                                        )->format(
                                            'Y-m-d H:i:s'
                                        )
                                        ?? '—'
                                    }}

                                </small>

                            </td>

                        </tr>


                        @if(
                            $user->risk_reasons
                        )

                            <tr>

                                <td
                                    colspan="7"
                                    class="bg-light"
                                >

                                    <small>

                                        <strong>
                                            Risk reasons:
                                        </strong>

                                        {{
                                            $user
                                                ->risk_reasons
                                        }}

                                    </small>

                                </td>

                            </tr>

                        @endif

                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="text-center py-5"
                            >

                                <div class="text-muted">

                                    Belum ada database
                                    user yang di-scan.

                                </div>

                                <div class="mt-2">

                                    Klik tombol
                                    <strong>
                                        Scan
                                    </strong>
                                    di atas.

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div class="card-footer">

            {{ $users->links() }}

        </div>

    </div>

</div>

@endsection