@extends('app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                {{ $databaseConnection->name }}
            </h2>

            <div class="text-muted">

                {{ strtoupper(
                    $databaseConnection->driver
                ) }}

                ·

                {{ $databaseConnection->host }}

                :

                {{ $databaseConnection->port }}

                ·

                {{ $databaseConnection->database }}

            </div>

        </div>


        <div class="d-flex gap-2">

            <form
                method="POST"
                action="{{ route(
                    'database-connections.test',
                    $databaseConnection
                ) }}"
            >

                @csrf

                <button
                    class="btn btn-outline-success"
                >
                    Test Connection
                </button>

            </form>


            <form
                method="POST"
                action="{{ route(
                    'database-connections.scan',
                    $databaseConnection
                ) }}"
            >

                @csrf

                <button
                    class="btn btn-primary"
                >
                    Scan Database
                </button>

            </form>

        </div>

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


    <div class="row g-4">

        @forelse(
            $databaseConnection->discoveredDatabases
            as $database
        )

            <div class="col-lg-6">

                <div class="card shadow-sm border-0">

                    <div class="card-header bg-white">

                        <div class="d-flex justify-content-between">

                            <strong>
                                🗄️ {{ $database->name }}
                            </strong>

                            <span class="badge bg-dark">
                                {{ $database->engine }}
                            </span>

                        </div>

                    </div>


                    <div class="card-body">

                        @forelse(
                            $database->tables
                            as $table
                        )

                            <div class="border rounded p-3 mb-3">

                                <div class="d-flex justify-content-between">

                                    <a
                                        href="{{ route(
                                            'database-explorer.show',
                                            [
                                                'databaseConnection' => $databaseConnection,
                                                'table' => $table->name
                                            ]
                                        ) }}"
                                        class="text-decoration-none fw-bold"
                                    >
                                        📋 {{ $table->name }}
                                    </a>

                                    <small class="text-muted">
                                        {{ $table->type }}
                                    </small>

                                </div>


                                <div class="mt-3">

                                    @forelse(
                                        $table->columns
                                        as $column
                                    )

                                        <div
                                            class="d-flex justify-content-between border-bottom py-2"
                                        >

                                            <span>

                                                @if($column->is_primary)
                                                    🔑
                                                @endif

                                                {{ $column->name }}

                                            </span>

                                            <small class="text-muted">
                                                {{ $column->data_type }}
                                            </small>

                                        </div>

                                    @empty

                                        <div class="text-muted">
                                            Belum ada informasi column.
                                        </div>

                                    @endforelse

                                </div>

                            </div>

                        @empty

                            <p class="text-muted mb-0">
                                Belum ada table.
                            </p>

                        @endforelse

                    </div>

                </div>

            </div>

        @empty

            <div class="col-12">

                <div class="card border-0 shadow-sm">

                    <div class="card-body text-center py-5">

                        <h4>
                            Belum ada database ditemukan
                        </h4>

                        <p class="text-muted">
                            Klik "Scan Database" untuk membaca
                            struktur database.
                        </p>

                    </div>

                </div>

            </div>

        @endforelse

    </div>

</div>

@endsection