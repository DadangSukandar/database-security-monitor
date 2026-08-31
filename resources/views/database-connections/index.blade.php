@extends('app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                Database Connections
            </h2>

            <p class="text-muted">
                Manage monitored databases
            </p>

        </div>

        <a
            href="{{ route(
                'database-connections.create'
            ) }}"
            class="btn btn-primary"
        >
            + Add Connection
        </a>

    </div>


    @if(session('success'))

        <div class="alert alert-success">
            {{ session('success') }}
        </div>

    @endif


    @if($errors->any())

        <div class="alert alert-danger">

            @foreach($errors->all() as $error)

                <div>{{ $error }}</div>

            @endforeach

        </div>

    @endif


    <div class="card border-0 shadow-sm">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover mb-0">

                    <thead>

                        <tr>
                            <th>Name</th>
                            <th>Driver</th>
                            <th>Host</th>
                            <th>Database</th>
                            <th>Last Connected</th>
                            <th>Last Scan</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        @forelse($connections as $connection)

                            <tr>

                                <td>
                                    <strong>
                                        {{ $connection->name }}
                                    </strong>
                                </td>

                                <td>
                                    {{ strtoupper($connection->driver) }}
                                </td>

                                <td>
                                    {{ $connection->host }}:
                                    {{ $connection->port }}
                                </td>

                                <td>
                                    {{ $connection->database }}
                                </td>

                                <td>
                                    {{ $connection->last_connected_at
                                        ? $connection->last_connected_at->format('d/m/Y H:i')
                                        : '-'
                                    }}
                                </td>

                                <td>
                                    {{ $connection->last_scanned_at
                                        ? $connection->last_scanned_at->format('d/m/Y H:i')
                                        : '-'
                                    }}
                                </td>

                                <td>

                                    <a
                                        href="{{ route(
                                            'database-connections.show',
                                            $connection
                                        ) }}"
                                        class="btn btn-sm btn-primary"
                                    >
                                        Explore
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'database-connections.destroy',
                                            $connection
                                        ) }}"
                                        class="d-inline"
                                        onsubmit="return confirm(
                                            'Hapus connection ini?'
                                        )"
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            class="btn btn-sm btn-outline-danger"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="7"
                                    class="text-center py-5"
                                >
                                    Belum ada connection.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    <div class="mt-3">

        {{ $connections->links() }}

    </div>

</div>

@endsection