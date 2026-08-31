@extends('app')

@section('content')

<div class="security-audit-page" style="padding:30px;">

    <a
        href="{{ route('security-audit.index') }}"
        style="
            text-decoration:none;
            color:#2563eb;
        "
    >
        ← Back to Security Audit
    </a>


    <div style="
        background:white;
        border:1px solid #ddd;
        border-radius:8px;
        margin-top:20px;
        padding:25px;
    ">

        <div style="
            display:flex;
            justify-content:space-between;
            align-items:center;
        ">

            <h1>
                {{ $securityFinding->title }}
            </h1>

            <span class="
                severity
                severity-{{ strtolower(
                    $securityFinding->severity
                ) }}
            ">
                {{ $securityFinding->severity }}
            </span>

        </div>


        <hr>


        <h3>Description</h3>

        <p>
            {{ $securityFinding->description }}
        </p>


        <h3>Database</h3>

        <p>
            {{ $securityFinding->database_name ?? '-' }}
        </p>


        <h3>Category</h3>

        <p>
            {{ $securityFinding->category ?? '-' }}
        </p>


        <h3>Username</h3>

        <p>
            {{ $securityFinding->username ?? '-' }}
        </p>


        <h3>Object</h3>

        <p>
            {{ $securityFinding->object_name ?? '-' }}
        </p>


        <h3>Recommendation</h3>

        <div style="
            background:#f8fafc;
            padding:15px;
            border-radius:6px;
        ">
            {{ $securityFinding->recommendation ?? '-' }}
        </div>


        <div style="
            margin-top:25px;
            display:flex;
            gap:10px;
        ">

            @if($securityFinding->status === 'OPEN')

                <form
                    method="POST"
                    action="{{ route(
                        'security-audit.resolve',
                        $securityFinding
                    ) }}"
                >

                    @csrf

                    <button
                        style="
                            background:#16a34a;
                            color:white;
                            border:0;
                            padding:10px 18px;
                            border-radius:6px;
                        "
                    >
                        Mark Resolved
                    </button>

                </form>


                <form
                    method="POST"
                    action="{{ route(
                        'security-audit.ignore',
                        $securityFinding
                    ) }}"
                >

                    @csrf

                    <button
                        style="
                            background:#64748b;
                            color:white;
                            border:0;
                            padding:10px 18px;
                            border-radius:6px;
                        "
                    >
                        Ignore
                    </button>

                </form>

            @else

                <form
                    method="POST"
                    action="{{ route(
                        'security-audit.reopen',
                        $securityFinding
                    ) }}"
                >

                    @csrf

                    <button
                        style="
                            background:#2563eb;
                            color:white;
                            border:0;
                            padding:10px 18px;
                            border-radius:6px;
                        "
                    >
                        Re-open
                    </button>

                </form>

            @endif

        </div>

    </div>

</div>


<style>

.severity {
    padding:6px 12px;
    border-radius:5px;
    font-size:12px;
    font-weight:bold;
}

.severity-critical {
    background:#7f1d1d;
    color:white;
}

.severity-high {
    background:#ef4444;
    color:white;
}

.severity-medium {
    background:#f59e0b;
    color:#111;
}

.severity-low {
    background:#16a34a;
    color:white;
}

</style>

@endsection
