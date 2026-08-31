@extends('app')

@section('content')

<style>
    .page-title {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .subtitle {
        color: #64748b;
        margin-bottom: 25px;
    }

    .card {
        background: white;
        border: 1px solid #dbe1e8;
        border-radius: 8px;
        padding: 25px;
        max-width: 900px;
    }

    .form-group {
        margin-bottom: 18px;
    }

    label {
        display: block;
        font-weight: 600;
        margin-bottom: 7px;
    }

    input,
    select,
    textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 11px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 14px;
    }

    textarea {
        min-height: 150px;
        font-family: monospace;
    }

    .row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .checkbox input {
        width: auto;
    }

    .actions {
        display: flex;
        gap: 10px;
        margin-top: 25px;
    }

    .btn {
        border: 0;
        border-radius: 6px;
        padding: 11px 18px;
        text-decoration: none;
        cursor: pointer;
    }

    .btn-primary {
        background: #1264f5;
        color: white;
    }

    .btn-secondary {
        background: #64748b;
        color: white;
    }

    .error {
        color: #dc3545;
        font-size: 13px;
        margin-top: 5px;
    }
</style>


<h1 class="page-title">
    Edit Security Policy
</h1>

<div class="subtitle">
    Perbarui aturan keamanan database.
</div>


@if($errors->any())

    <div
        style="
            background:#f8d7da;
            color:#842029;
            padding:15px;
            border-radius:7px;
            margin-bottom:20px;
        "
    >

        <strong>Terjadi kesalahan:</strong>

        <ul>

            @foreach($errors->all() as $error)

                <li>
                    {{ $error }}
                </li>

            @endforeach

        </ul>

    </div>

@endif


<div class="card">

    <form
        method="POST"
        action="{{ route(
            'security-policies.update',
            $securityPolicy
        ) }}"
    >

        @csrf

        @method('PUT')


        <div class="form-group">

            <label>
                Policy Name
            </label>

            <input
                type="text"
                name="name"
                value="{{ old(
                    'name',
                    $securityPolicy->name
                ) }}"
                required
            >

        </div>


        <div class="form-group">

            <label>
                Policy Code
            </label>

            <input
                type="text"
                name="code"
                value="{{ old(
                    'code',
                    $securityPolicy->code
                ) }}"
                required
            >

        </div>


        <div class="row">

            <div class="form-group">

                <label>
                    Rule Type
                </label>

                <select
                    name="rule_type"
                    required
                >

                    @foreach([
                        'PRIVILEGE',
                        'SENSITIVE_DATA',
                        'LOGIN',
                        'QUERY'
                    ] as $type)

                        <option
                            value="{{ $type }}"
                            @selected(
                                old(
                                    'rule_type',
                                    $securityPolicy->rule_type
                                ) === $type
                            )
                        >
                            {{ $type }}
                        </option>

                    @endforeach

                </select>

            </div>


            <div class="form-group">

                <label>
                    Severity
                </label>

                <select
                    name="severity"
                    required
                >

                    @foreach([
                        'CRITICAL',
                        'HIGH',
                        'MEDIUM',
                        'LOW'
                    ] as $severity)

                        <option
                            value="{{ $severity }}"
                            @selected(
                                old(
                                    'severity',
                                    $securityPolicy->severity
                                ) === $severity
                            )
                        >
                            {{ $severity }}
                        </option>

                    @endforeach

                </select>

            </div>

        </div>


        <div class="form-group">

            <label>
                Priority
            </label>

            <input
                type="number"
                name="priority"
                value="{{ old(
                    'priority',
                    $securityPolicy->priority
                ) }}"
                min="1"
                max="9999"
                required
            >

        </div>


        <div class="form-group">

            <label>
                Conditions
            </label>

            <textarea
                name="conditions"
            >{{ old(
                'conditions',
                $securityPolicy->conditions
                    ? json_encode(
                        $securityPolicy->conditions,
                        JSON_PRETTY_PRINT
                    )
                    : ''
            ) }}</textarea>

            @error('conditions')

                <div class="error">
                    {{ $message }}
                </div>

            @enderror

        </div>


        <div class="form-group">

            <label class="checkbox">

                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    @checked(
                        old(
                            'is_active',
                            $securityPolicy->is_active
                        )
                    )
                >

                Active Policy

            </label>

        </div>


        <div class="actions">

            <button
                type="submit"
                class="btn btn-primary"
            >
                Update Policy
            </button>


            <a
                href="{{ route('security-policies.index') }}"
                class="btn btn-secondary"
            >
                Cancel
            </a>

        </div>

    </form>

</div>

@endsection