@extends('app')

@section('content')

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2>
                Query Result
            </h2>

            <div class="text-muted">

                {{ $connection->name }}

                ·

                {{ $executionTimeMs }} ms

                ·

                {{ number_format($totalRows) }} rows

            </div>

        </div>


        <a
            href="{{ route(
                'database-query.index'
            ) }}"
            class="btn btn-outline-secondary"
        >
            ← Query Console
        </a>

    </div>


    {{-- QUERY --}}

    <div class="card shadow-sm mb-4">

        <div class="card-header">

            <strong>
                Executed SQL
            </strong>

        </div>

        <div class="card-body">

            <pre
                class="mb-0"
                style="white-space:pre-wrap;"
            ><code>{{ $sql }}</code></pre>

        </div>

    </div>


    {{-- INFORMATION --}}

    <div class="alert alert-success">

        Query berhasil dijalankan.

        <strong>
            {{ number_format($totalRows) }}
        </strong>
        row ditemukan.

        @if($totalRows > 500)

            <div class="small mt-1">
                Hanya 500 row pertama yang ditampilkan.
            </div>

        @endif

    </div>


    {{-- RESULT --}}

    <div class="card shadow-sm">

        <div class="card-header">

            <strong>
                Result
            </strong>

        </div>


        <div class="table-responsive">

            @if(count($displayRows) > 0)

                <table class="table table-bordered table-hover mb-0">

                    <thead>

                        <tr>

                            @foreach(
                                array_keys(
                                    $displayRows[0]
                                )
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
                            $displayRows
                            as $row
                        )

                            <tr>

                                @foreach(
                                    $row
                                    as $value
                                )

                                    <td>

                                        @if(
                                            is_null($value)
                                        )

                                            <span
                                                class="text-muted"
                                            >
                                                NULL
                                            </span>

                                        @elseif(
                                            is_scalar($value)
                                        )

                                            {{ $value }}

                                        @else

                                            {{ json_encode(
                                                $value
                                            ) }}

                                        @endif

                                    </td>

                                @endforeach

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            @else

                <div class="text-center py-5">

                    Query berhasil tetapi
                    tidak menghasilkan row.

                </div>

            @endif

        </div>

    </div>

</div>

@endsection