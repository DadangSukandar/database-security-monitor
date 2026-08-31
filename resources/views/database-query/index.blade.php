@extends('app')

@section('content')

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                SQL Query Console
            </h2>

            <div class="text-muted">
                Jalankan query read-only pada database yang terhubung.
            </div>

        </div>

    </div>


    @if($errors->any())

        <div class="alert alert-danger">

            <strong>
                Query tidak dapat dijalankan.
            </strong>

            <ul class="mb-0 mt-2">

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- SQL EDITOR --}}

    <div class="card shadow-sm mb-4">

        <div class="card-header">

            <strong>
                SQL Editor
            </strong>

        </div>


        <div class="card-body">

            <form
                method="POST"
                action="{{ route(
                    'database-query.execute'
                ) }}"
            >

                @csrf


                <div class="mb-3">

                    <label class="form-label">
                        Database Connection
                    </label>

                    <select
                        name="connection_id"
                        class="form-select"
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
                                value="{{ $connection->id }}"
                                @selected(
                                    old(
                                        'connection_id'
                                    ) == $connection->id
                                )
                            >

                                {{ $connection->name }}

                                —
                                {{ strtoupper(
                                    $connection->driver
                                ) }}

                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        SQL Query
                    </label>

                    <textarea
                        name="sql"
                        class="form-control font-monospace"
                        rows="10"
                        placeholder="SELECT * FROM users LIMIT 20"
                        required
                    >{{ old('sql') }}</textarea>

                </div>


                <div class="alert alert-warning">

                    <strong>
                        Read Only
                    </strong>

                    <div class="small mt-1">

                        Query Console saat ini hanya menerima
                        SELECT dan WITH.

                        INSERT, UPDATE, DELETE, DROP,
                        ALTER, TRUNCATE, dan operasi perubahan
                        data lainnya diblokir.

                    </div>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    ▶ Execute Query
                </button>

            </form>

        </div>

    </div>


    {{-- QUERY HISTORY --}}

    <div class="card shadow-sm">

        <div class="card-header">

            <strong>
                Query History
            </strong>

        </div>


        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead>

                    <tr>

                        <th>
                            Time
                        </th>

                        <th>
                            Connection
                        </th>

                        <th>
                            Query
                        </th>

                        <th>
                            Execution
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse(
                        $history
                        as $item
                    )

                        <tr>

                            <td>

                                <small>
                                    {{
                                        optional(
                                            $item->executed_at
                                        )->format(
                                            'Y-m-d H:i:s'
                                        )
                                    }}
                                </small>

                            </td>


                            <td>

                                {{
                                    $item
                                        ->databaseConnection
                                        ->name
                                    ?? '—'
                                }}

                            </td>


                            <td
                                style="max-width:500px;"
                            >

                                <code
                                    class="d-block text-truncate"
                                >

                                    {{ $item->query }}

                                </code>

                            </td>


                            <td>

                                {{
                                    $item->execution_time_ms
                                    ?? '—'
                                }}

                                @if(
                                    $item->execution_time_ms !== null
                                )
                                    ms
                                @endif

                            </td>


                            <td>

                                @if(
                                    $item->status === 'success'
                                )

                                    <span
                                        class="badge text-bg-success"
                                    >
                                        SUCCESS
                                    </span>

                                @else

                                    <span
                                        class="badge text-bg-danger"
                                    >
                                        FAILED
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="text-center py-5"
                            >

                                Belum ada query history.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div class="card-footer">

            {{ $history->links() }}

        </div>

    </div>

</div>

@endsection