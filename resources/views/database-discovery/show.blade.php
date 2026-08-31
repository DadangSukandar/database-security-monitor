@extends('app')

@section('content')

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between mb-4">

        <div>

            <h2>
                {{ $discoveredDatabase->name }}
            </h2>

            <div class="text-muted">

                {{ $discoveredDatabase->engine }}

                @if($discoveredDatabase->version)
                    · {{ $discoveredDatabase->version }}
                @endif

            </div>

        </div>


        <a
            href="{{ route(
                'database-discovery.index'
            ) }}"
            class="btn btn-outline-secondary"
        >
            ← Back
        </a>

    </div>


    <div class="card shadow-sm">

        <div class="card-header">

            <strong>
                Tables
            </strong>

        </div>


        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead>

                    <tr>

                        <th>
                            Schema
                        </th>

                        <th>
                            Table
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Rows
                        </th>

                        <th>
                            Columns
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse(
                        $discoveredDatabase->tables
                        as $table
                    )

                        <tr>

                            <td>
                                {{ $table->schema_name }}
                            </td>

                            <td>

                                <strong>
                                    {{ $table->name }}
                                </strong>

                            </td>

                            <td>
                                {{ $table->type }}
                            </td>

                            <td>

                                {{ $table->estimated_rows !== null
                                    ? number_format(
                                        $table->estimated_rows
                                    )
                                    : '—'
                                }}

                            </td>

                            <td>

                                <span class="badge text-bg-primary">

                                    {{ $table->columns_count }}

                                </span>

                            </td>

                            <td>

                                <a
                                    href="{{ route(
                                        'database-discovery.table',
                                        $table
                                    ) }}"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Columns
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="text-center py-5"
                            >
                                Tidak ada table ditemukan.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection