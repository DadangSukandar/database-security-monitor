@extends('app')

@section('content')

<div class="container-fluid py-4">

    {{-- HEADER --}}

    <div class="d-flex justify-content-between align-items-start mb-4">

        <div>

            <h2 class="mb-1">
                {{ $table }}
            </h2>

            <div class="text-muted">

                {{ $databaseConnection->name }}

                ·

                {{ strtoupper(
                    $databaseConnection->driver
                ) }}

                ·

                {{ $databaseConnection->database }}

            </div>

        </div>


        <a
            href="{{ url()->previous() }}"
            class="btn btn-outline-secondary"
        >
            ← Back
        </a>

    </div>


    {{-- SENSITIVE WARNING --}}

    @if($sensitiveColumns->count() > 0)

        <div class="alert alert-warning">

            <div class="d-flex">

                <div class="fs-4 me-3">
                    ⚠️
                </div>

                <div>

                    <strong>
                        Sensitive Data Detected
                    </strong>

                    <div class="small mt-1">

                        Table ini memiliki
                        <strong>
                            {{ $sensitiveColumns->count() }}
                        </strong>
                        column yang terdeteksi
                        mengandung data sensitif.

                    </div>

                </div>

            </div>

        </div>

    @endif


    {{-- COLUMN INFORMATION --}}

    <div class="card shadow-sm mb-4">

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
                            Column
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Nullable
                        </th>

                        <th>
                            Key
                        </th>

                        <th>
                            Security
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @foreach(
                        $columns
                        as $column
                    )

                        <tr>

                            <td>

                                <strong>
                                    {{ $column['name'] }}
                                </strong>

                            </td>


                            <td>

                                <code>
                                    {{
                                        $column['data_type']
                                        ?? '—'
                                    }}
                                </code>

                            </td>


                            <td>

                                {{
                                    $column['is_nullable']
                                    ?? '—'
                                }}

                            </td>


                            <td>

                                @if(
                                    !empty(
                                        $column['key']
                                    )
                                )

                                    <span
                                        class="badge text-bg-secondary"
                                    >
                                        {{ $column['key'] }}
                                    </span>

                                @else

                                    —

                                @endif

                            </td>


                            <td>

                                @if(
                                    $column['is_sensitive']
                                )

                                    @php

                                        $risk =
                                            strtoupper(
                                                $column[
                                                    'risk_level'
                                                ] ?? 'MEDIUM'
                                            );

                                    @endphp


                                    <span
                                        class="badge text-bg-warning"
                                    >
                                        🔒 SENSITIVE
                                    </span>


                                    <span
                                        class="badge text-bg-secondary"
                                    >
                                        {{
                                            $column[
                                                'category'
                                            ]
                                        }}
                                    </span>


                                    <span
                                        class="badge
                                        @if($risk === 'CRITICAL')
                                            text-bg-danger
                                        @elseif($risk === 'HIGH')
                                            text-bg-danger
                                        @elseif($risk === 'MEDIUM')
                                            text-bg-warning
                                        @else
                                            text-bg-secondary
                                        @endif"
                                    >
                                        {{ $risk }}
                                    </span>

                                @else

                                    <span
                                        class="text-muted"
                                    >
                                        Normal
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>


    {{-- DATA --}}

    <div class="card shadow-sm">

        <div class="card-header">

            <div class="d-flex justify-content-between align-items-center">

                <strong>
                    Browse Data
                </strong>

                <span class="text-muted">

                    {{ number_format(
                        $totalRows
                    ) }}

                    rows

                </span>

            </div>

        </div>


        @if(
            $sensitiveColumns->count() > 0
        )

            <div class="px-3 pt-3">

                <div class="alert alert-info small mb-0">

                    🔒
                    Column sensitif ditampilkan
                    dalam bentuk masking untuk
                    melindungi data.

                </div>

            </div>

        @endif


        <div class="table-responsive">

            <table class="table table-bordered table-hover mb-0">

                <thead>

                    <tr>

                        @foreach(
                            $columns
                            as $column
                        )

                            <th
                                class="
                                @if(
                                    $column['is_sensitive']
                                )
                                    table-warning
                                @endif
                                "
                            >

                                <div
                                    class="text-nowrap"
                                >

                                    {{ $column['name'] }}

                                </div>


                                @if(
                                    $column['is_sensitive']
                                )

                                    <small
                                        class="text-danger"
                                    >
                                        🔒
                                        {{
                                            $column[
                                                'risk_level'
                                            ]
                                        }}
                                    </small>

                                @endif

                            </th>

                        @endforeach

                    </tr>

                </thead>


                <tbody>

                    @forelse(
                        $rows
                        as $row
                    )

                        <tr>

                            @foreach(
                                $columns
                                as $column
                            )

                                @php

                                    $columnName =
                                        $column['name'];

                                    $value =
                                        $row[
                                            $columnName
                                        ]
                                        ?? null;

                                @endphp


                                <td
                                    class="
                                    @if(
                                        $column['is_sensitive']
                                    )
                                        table-warning
                                    @endif
                                    "
                                >

                                    @if(
                                        is_null($value)
                                    )

                                        <span
                                            class="text-muted"
                                        >
                                            NULL
                                        </span>

                                    @else

                                        {{ $value }}

                                    @endif

                                </td>

                            @endforeach

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="{{
                                    count($columns)
                                }}"
                                class="text-center py-5"
                            >

                                Tidak ada data.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- PAGINATION --}}

        <div class="card-footer">

            {{ $rows->links() }}

        </div>

    </div>

</div>

@endsection