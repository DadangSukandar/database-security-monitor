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

    .help {
        margin-top: 5px;
        color: #64748b;
        font-size: 13px;
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
    Create Security Policy
</h1>

<div class="subtitle">
    Buat aturan keamanan database baru.
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

        <strong>Periksa input berikut:</strong>

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
        action="{{ route('security-policies.store') }}"
    >

        @csrf


        <div class="form-group">

            <label>
                Policy Name
            </label>

            <input
                type="text"
                name="name"
                value="{{ old('name') }}"
                placeholder="Contoh: Detect Superuser Account"
                required
            >

            @error('name')
                <div class="error">
                    {{ $message }}
                </div>
            @enderror

        </div>


        <div class="form-group">

            <label>
                Policy Code
            </label>

            <input
                type="text"
                name="code"
                value="{{ old('code') }}"
                placeholder="SUPERUSER_ACCOUNT"
                required
            >

            <div class="help">
                Gunakan kode unik untuk policy.
            </div>

            @error('code')
                <div class="error">
                    {{ $message }}
                </div>
            @enderror

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

                    <option value="">
                        -- Select Rule --
                    </option>

                    <option
                        value="PRIVILEGE"
                        @selected(old('rule_type') === 'PRIVILEGE')
                    >
                        Privilege
                    </option>

                    <option
                        value="SENSITIVE_DATA"
                        @selected(old('rule_type') === 'SENSITIVE_DATA')
                    >
                        Sensitive Data
                    </option>

                    <option
                        value="LOGIN"
                        @selected(old('rule_type') === 'LOGIN')
                    >
                        Login
                    </option>

                    <option
                        value="QUERY"
                        @selected(old('rule_type') === 'QUERY')
                    >
                        Query
                    </option>

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

                    <option value="CRITICAL">
                        CRITICAL
                    </option>

                    <option value="HIGH">
                        HIGH
                    </option>

                    <option
                        value="MEDIUM"
                        @selected(old('severity', 'MEDIUM') === 'MEDIUM')
                    >
                        MEDIUM
                    </option>

                    <option value="LOW">
                        LOW
                    </option>

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
                value="{{ old('priority', 100) }}"
                min="1"
                max="9999"
                required
            >

            <div class="help">
                Angka lebih kecil berarti prioritas lebih tinggi.
            </div>

        </div>


        <div class="form-group">

            <label>
                Conditions
            </label>

            <textarea
                name="conditions"
                placeholder='{
    "username": "root",
    "host": "%"
}'
            >{{ old('conditions') }}</textarea>

            <div class="help">
                Conditions harus berupa JSON valid. Boleh dikosongkan.
            </div>

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
                    @checked(old('is_active', true))
                >

                Active Policy

            </label>

        </div>


        <div class="actions">

            <button
                type="submit"
                class="btn btn-primary"
            >
                Save Policy
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