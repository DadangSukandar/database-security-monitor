<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Activity Detail
    </title>


    <style>

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 30px;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #2563eb;
            font-size: 13px;
        }

        .panel {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .body {
            padding: 20px;
        }

        .grid {
            display: grid;
            grid-template-columns:
                repeat(3, 1fr);
            gap: 20px;
        }

        .label {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .value {
            margin-top: 6px;
            font-size: 14px;
        }

        pre {
            background: #0f172a;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            white-space: pre-wrap;
            line-height: 1.6;
        }

        .success {
            color: #15803d;
            font-weight: 700;
        }

        .failed {
            color: #dc2626;
            font-weight: 700;
        }

    </style>

</head>


<body>

@include('partials.guardium-theme')

<div class="container">


    <a
        href="{{ route('database-activities.index') }}"
        class="back"
    >
        ← Back to Activity Monitoring
    </a>


    <div class="panel">

        <div class="header">

            <h2 style="margin:0;">
                Activity Detail
            </h2>

        </div>


        <div class="body">

            <div class="grid">


                <div>

                    <div class="label">
                        Database
                    </div>

                    <div class="value">

                        {{
                            $databaseActivity
                                ->database_name
                            ?? '-'
                        }}

                    </div>

                </div>


                <div>

                    <div class="label">
                        Username
                    </div>

                    <div class="value">

                        {{
                            $databaseActivity
                                ->username
                            ?? '-'
                        }}

                    </div>

                </div>


                <div>

                    <div class="label">
                        Client IP
                    </div>

                    <div class="value">

                        {{
                            $databaseActivity
                                ->client_ip
                            ?? '-'
                        }}

                    </div>

                </div>


                <div>

                    <div class="label">
                        Action
                    </div>

                    <div class="value">

                        {{
                            strtoupper(
                                $databaseActivity
                                    ->action
                                ?? '-'
                            )
                        }}

                    </div>

                </div>


                <div>

                    <div class="label">
                        Status
                    </div>

                    <div class="value">

                        @if(
                            strtoupper(
                                $databaseActivity->status
                            )
                            ===
                            'SUCCESS'
                        )

                            <span class="success">
                                SUCCESS
                            </span>

                        @else

                            <span class="failed">
                                FAILED
                            </span>

                        @endif

                    </div>

                </div>


                <div>

                    <div class="label">
                        Execution Time
                    </div>

                    <div class="value">

                        {{
                            $databaseActivity
                                ->execution_time_ms
                            ?? 0
                        }}

                        ms

                    </div>

                </div>


                <div>

                    <div class="label">
                        Schema
                    </div>

                    <div class="value">

                        {{
                            $databaseActivity
                                ->schema_name
                            ?? '-'
                        }}

                    </div>

                </div>


                <div>

                    <div class="label">
                        Table
                    </div>

                    <div class="value">

                        {{
                            $databaseActivity
                                ->table_name
                            ?? '-'
                        }}

                    </div>

                </div>


                <div>

                    <div class="label">
                        Executed At
                    </div>

                    <div class="value">

                        {{
                            $databaseActivity
                                ->executed_at
                                ?->format(
                                    'Y-m-d H:i:s'
                                )
                        }}

                    </div>

                </div>


            </div>

        </div>

    </div>


    {{-- QUERY --}}

    <div class="panel">

        <div class="header">

            <strong>
                SQL Query
            </strong>

        </div>


        <div class="body">

            <pre>{{
                $databaseActivity->query
            }}</pre>

        </div>

    </div>


    {{-- ERROR --}}

    @if(
        $databaseActivity->error_message
    )

        <div class="panel">

            <div class="header">

                <strong>
                    Error Message
                </strong>

            </div>


            <div class="body">

                <pre>Database operation failed. Detail teknis tersedia di log aplikasi.</pre>

            </div>

        </div>

    @endif


</div>

</body>

</html>
