@extends('app')

@section('content')

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between mb-4">

        <div>

            <h2 class="mb-1">

                {{ $discoveredTable->name }}

            </h2>

            <div class="text-muted">

                {{ $discoveredTable->schema_name }}

            </div>

        </div>


        <a
            href="{{ route(
                'database-discovery.show',
                $discoveredTable->database
            ) }}"
            class="btn btn-outline-secondary"
        >
            ← Back
        </a>

    </div>


    <div class="card shadow-sm">

        <div class="card-header">

            <strong>
                Columns
            </strong>

        </div>


        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead>

                    <tr>

                        <th>
                            #
                        </th>

                        <th>
                            Column
                        </th>

                        <th>
                            Data Type
                        </th>

                        <th>
                            Column Type
                        </th>

                        <th>
                            Nullable
                        </th>

                        <th>
                            Default
                        </th>

                        <th>
                            Key
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @foreach(
                        $discoveredTable->columns
                        as $index => $column
                    )

                        <tr>

                            <td>
                                {{ $index + 1 }}
                            </td>

                            <td>

                                <strong>
                                    {{ $column->name }}
                                </strong>

                            </td>

                            <td>

                                <span class="badge text-bg-secondary">

                                    {{ $column->data_type }}

                                </span>

                            </td>

                            <td>

                                <code>
                                    {{ $column->column_type }}
                                </code>

                            </td>

                            <td>

                                @if(
                                    strtoupper(
                                        $column->is_nullable
                                    ) === 'YES'
                                )

                                    <span class="text-success">
                                        YES
                                    </span>

                                @else

                                    <span class="text-danger">
                                        NO
                                    </span>

                                @endif

                            </td>

                            <td>

                                {{ $column->default_value ?? '—' }}

                            </td>

                            <td>

                                @if(
                                    $column->is_primary
                                )

                                    <span class="badge text-bg-warning">
                                        PRIMARY KEY
                                    </span>

                                @else

                                    —

                                @endif

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection