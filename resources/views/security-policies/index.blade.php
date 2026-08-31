@extends('app')

@section('content')

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        gap: 20px;
    }

    .page-title {
        font-size: 32px;
        font-weight: 700;
        margin: 0;
        color: #111827;
    }

    .page-subtitle {
        margin-top: 6px;
        color: #64748b;
    }

    .btn {
        display: inline-block;
        border: 0;
        border-radius: 7px;
        padding: 10px 16px;
        text-decoration: none;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-primary {
        background: #1264f5;
        color: white;
    }

    .btn-secondary {
        background: #64748b;
        color: white;
    }

    .btn-danger {
        background: #dc3545;
        color: white;
    }

    .btn-success {
        background: #198754;
        color: white;
    }

    .btn-warning {
        background: #f59e0b;
        color: white;
    }

    .stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 25px;
    }

    .stat-card {
        background: white;
        border: 1px solid #dbe1e8;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,.08);
    }

    .stat-label {
        color: #64748b;
        font-size: 14px;
        text-transform: uppercase;
    }

    .stat-value {
        font-size: 30px;
        font-weight: 700;
        margin-top: 8px;
    }

    .filter-box {
        background: white;
        border: 1px solid #dbe1e8;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 25px;
    }

    .filter-form {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr auto auto;
        gap: 10px;
    }

    .input,
    .select {
        width: 100%;
        box-sizing: border-box;
        padding: 10px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: white;
    }

    .table-box {
        background: white;
        border: 1px solid #dbe1e8;
        border-radius: 8px;
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        text-align: left;
        padding: 13px;
        background: #f8fafc;
        border-bottom: 1px solid #dbe1e8;
        font-size: 14px;
    }

    td {
        padding: 13px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .badge {
        display: inline-block;
        padding: 4px 9px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
    }

    .badge-critical {
        background: #dc3545;
        color: white;
    }

    .badge-high {
        background: #ef4444;
        color: white;
    }

    .badge-medium {
        background: #f59e0b;
        color: white;
    }

    .badge-low {
        background: #198754;
        color: white;
    }

    .badge-active {
        background: #198754;
        color: white;
    }

    .badge-inactive {
        background: #64748b;
        color: white;
    }

    .actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    .actions .btn {
        padding: 6px 9px;
        font-size: 12px;
    }

    .alert {
        padding: 13px 16px;
        border-radius: 7px;
        margin-bottom: 20px;
    }

    .alert-success {
        background: #d1e7dd;
        border: 1px solid #a3cfbb;
        color: #0f5132;
    }

    .alert-danger {
        background: #f8d7da;
        border: 1px solid #f1aeb5;
        color: #842029;
    }

    .pagination {
        padding: 15px;
    }

    @media (max-width: 900px) {
        .stats {
            grid-template-columns: repeat(2, 1fr);
        }

        .filter-form {
            grid-template-columns: 1fr;
        }

        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="page-header">

    <div>
        <h1 class="page-title">
            Security Policies
        </h1>

        <div class="page-subtitle">
            Kelola aturan keamanan database.
        </div>
    </div>

    <a
        href="{{ route('security-policies.create') }}"
        class="btn btn-primary"
    >
        + Create Policy
    </a>

</div>


@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif


@if($errors->any())
    <div class="alert alert-danger">

        <strong>Terjadi kesalahan:</strong>

        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>

    </div>
@endif


<div class="stats">

    <div class="stat-card">
        <div class="stat-label">
            Total Policies
        </div>

        <div class="stat-value">
            {{ $totalPolicies }}
        </div>
    </div>


    <div class="stat-card">
        <div class="stat-label">
            Active
        </div>

        <div class="stat-value">
            {{ $activePolicies }}
        </div>
    </div>


    <div class="stat-card">
        <div class="stat-label">
            Inactive
        </div>

        <div class="stat-value">
            {{ $inactivePolicies }}
        </div>
    </div>


    <div class="stat-card">
        <div class="stat-label">
            Critical
        </div>

        <div class="stat-value">
            {{ $criticalPolicies }}
        </div>
    </div>

</div>


<div class="filter-box">

    <form
        method="GET"
        action="{{ route('security-policies.index') }}"
        class="filter-form"
    >

        <input
            type="text"
            name="search"
            class="input"
            placeholder="Search policy..."
            value="{{ request('search') }}"
        >


        <select
            name="rule_type"
            class="select"
        >

            <option value="">
                All Rules
            </option>

            <option
                value="PRIVILEGE"
                @selected(request('rule_type') === 'PRIVILEGE')
            >
                Privilege
            </option>

            <option
                value="SENSITIVE_DATA"
                @selected(request('rule_type') === 'SENSITIVE_DATA')
            >
                Sensitive Data
            </option>

            <option
                value="LOGIN"
                @selected(request('rule_type') === 'LOGIN')
            >
                Login
            </option>

            <option
                value="QUERY"
                @selected(request('rule_type') === 'QUERY')
            >
                Query
            </option>

        </select>


        <select
            name="severity"
            class="select"
        >

            <option value="">
                All Severity
            </option>

            @foreach([
                'CRITICAL',
                'HIGH',
                'MEDIUM',
                'LOW'
            ] as $severity)

                <option
                    value="{{ $severity }}"
                    @selected(request('severity') === $severity)
                >
                    {{ $severity }}
                </option>

            @endforeach

        </select>


        <select
            name="status"
            class="select"
        >

            <option value="">
                All Status
            </option>

            <option
                value="active"
                @selected(request('status') === 'active')
            >
                Active
            </option>

            <option
                value="inactive"
                @selected(request('status') === 'inactive')
            >
                Inactive
            </option>

        </select>


        <button
            type="submit"
            class="btn btn-primary"
        >
            Filter
        </button>


        <a
            href="{{ route('security-policies.index') }}"
            class="btn btn-secondary"
        >
            Reset
        </a>

    </form>

</div>


<div class="table-box">

    <table>

        <thead>

            <tr>
                <th>Policy</th>
                <th>Rule Type</th>
                <th>Severity</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Conditions</th>
                <th>Action</th>
            </tr>

        </thead>

        <tbody>

            @forelse($policies as $policy)

                <tr>

                    <td>
                        <strong>
                            {{ $policy->name }}
                        </strong>

                        <br>

                        <small>
                            {{ $policy->code }}
                        </small>
                    </td>


                    <td>
                        {{ $policy->rule_type }}
                    </td>


                    <td>

                        @php
                            $severityClass = match($policy->severity) {
                                'CRITICAL' => 'badge-critical',
                                'HIGH' => 'badge-high',
                                'MEDIUM' => 'badge-medium',
                                default => 'badge-low',
                            };
                        @endphp

                        <span class="badge {{ $severityClass }}">
                            {{ $policy->severity }}
                        </span>

                    </td>


                    <td>
                        {{ $policy->priority }}
                    </td>


                    <td>

                        @if($policy->is_active)

                            <span class="badge badge-active">
                                ACTIVE
                            </span>

                        @else

                            <span class="badge badge-inactive">
                                INACTIVE
                            </span>

                        @endif

                    </td>


                    <td>

                        @if($policy->conditions)

                            <code>
                                {{ json_encode($policy->conditions) }}
                            </code>

                        @else

                            <span style="color:#64748b">
                                -
                            </span>

                        @endif

                    </td>


                    <td>

                        <div class="actions">

                            <a
                                href="{{ route(
                                    'security-policies.edit',
                                    $policy
                                ) }}"
                                class="btn btn-primary"
                            >
                                Edit
                            </a>


                            <form
                                method="POST"
                                action="{{ route(
                                    'security-policies.toggle',
                                    $policy
                                ) }}"
                            >

                                @csrf

                                <button
                                    type="submit"
                                    class="btn btn-warning"
                                >
                                    {{ $policy->is_active
                                        ? 'Disable'
                                        : 'Enable'
                                    }}
                                </button>

                            </form>


                            <form
                                method="POST"
                                action="{{ route(
                                    'security-policies.destroy',
                                    $policy
                                ) }}"
                                onsubmit="return confirm(
                                    'Hapus security policy ini?'
                                )"
                            >

                                @csrf

                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="btn btn-danger"
                                >
                                    Delete
                                </button>

                            </form>

                        </div>

                    </td>

                </tr>

            @empty

                <tr>

                    <td
                        colspan="7"
                        style="text-align:center;padding:50px;"
                    >

                        <div style="font-size:20px;font-weight:600;">
                            Belum ada security policy
                        </div>

                        <div
                            style="
                                margin-top:8px;
                                color:#64748b;
                            "
                        >
                            Klik tombol
                            <strong>Create Policy</strong>
                            untuk membuat policy pertama.
                        </div>

                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>


    @if($policies->hasPages())

        <div class="pagination">
            {{ $policies->links() }}
        </div>

    @endif

</div>

@endsection