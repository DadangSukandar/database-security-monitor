@extends('app')

@section('content')

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Sensitive Data Discovery
            </h2>

            <div class="text-muted">
                Identifikasi column yang berpotensi
                mengandung data sensitif.
            </div>

        </div>

        <form
            method="POST"
            action="{{ route('sensitive-data.scan') }}"
        >

            @csrf

            <button
                class="btn btn-primary"
                type="submit"
            >
                🔍 Scan Sensitive Data
            </button>

        </form>

    </div>


    @if(session('success'))

        <div class="alert alert-success">
            {{ session('success') }}
        </div>

    @endif


    @if(session('error'))

        <div class="alert alert-danger">
            {{ session('error') }}
        </div>

    @endif


    {{-- STATISTICS --}}

    <div class="row g-3 mb-4">

        <div class="col-md-2">

            <div class="card shadow-sm">

                <div class="card-body">

                    <small class="text-muted">
                        TOTAL
                    </small>

                    <div class="fs-3 fw-bold">
                        {{ number_format($total) }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-2">

            <div class="card shadow-sm">

                <div class="card-body">

                    <small class="text-muted">
                        CRITICAL
                    </small>

                    <div class="fs-3 fw-bold text-danger">
                        {{ number_format($critical) }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-2">

            <div class="card shadow-sm">

                <div class="card-body">

                    <small class="text-muted">
                        HIGH
                    </small>

                    <div class="fs-3 fw-bold text-danger">
                        {{ number_format($high) }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-2">

            <div class="card shadow-sm">

                <div class="card-body">

                    <small class="text-muted">
                        MEDIUM
                    </small>

                    <div class="fs-3 fw-bold text-warning">
                        {{ number_format($medium) }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-2">

            <div class="card shadow-sm">

                <div class="card-body">

                    <small class="text-muted">
                        LOW
                    </small>

                    <div class="fs-3 fw-bold text-secondary">
                        {{ number_format($low) }}
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- FINDINGS --}}

    <div class="card shadow-sm">

        <div class="card-header">

            <strong>
                Findings
            </strong>

        </div>


        <div class="table-responsive">

            <table class="table table-hover mb-0 align-middle">

                <thead>

                    <tr>

                        <th>
                            Database
                        </th>

                        <th>
                            Table
                        </th>

                        <th>
                            Column
                        </th>

                        <th>
                            Category
                        </th>

                        <th>
                            Risk
                        </th>

                        <th>
                            Rule
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($findings as $finding)

                        <tr>

                            <td>

                                {{ $finding
                                    ->column
                                    ->table
                                    ->database
                                    ->name
                                }}

                            </td>


                            <td>

                                {{ $finding
                                    ->column
                                    ->table
                                    ->name
                                }}

                            </td>


                            <td>

                                <strong>

                                    {{ $finding
                                        ->column
                                        ->name
                                    }}

                                </strong>

                            </td>


                            <td>

                                <span class="badge text-bg-secondary">

                                    {{ $finding->category }}

                                </span>

                            </td>


                            <td>

                                @php

                                    $badge = match(
                                        $finding->risk_level
                                    ) {

                                        'CRITICAL' =>
                                            'text-bg-danger',

                                        'HIGH' =>
                                            'text-bg-danger',

                                        'MEDIUM' =>
                                            'text-bg-warning',

                                        default =>
                                            'text-bg-secondary',

                                    };

                                @endphp

                                <span
                                    class="badge {{ $badge }}"
                                >
                                    {{ $finding->risk_level }}
                                </span>

                            </td>


                            <td>

                                <code>
                                    {{ $finding->rule_name }}
                                </code>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="text-center py-5"
                            >

                                <div class="fs-1">
                                    🔐
                                </div>

                                <div class="mt-2">
                                    Belum ada hasil scan.
                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div class="card-footer">

            {{ $findings->links() }}

        </div>

    </div>

</div>

@endsection