<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        @yield('title', 'Database Security Center')
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fa;
        }

        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: #0f172a;
            color: white;
            padding: 20px 14px;
            flex-shrink: 0;
        }

        .brand {
            font-size: 20px;
            font-weight: 700;
            padding: 10px 12px 25px;
        }

        .brand small {
            display: block;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 400;
            margin-top: 5px;
        }

        .menu-title {
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            padding: 15px 12px 7px;
            text-transform: uppercase;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #cbd5e1;
            text-decoration: none;
            padding: 11px 12px;
            border-radius: 7px;
            margin-bottom: 3px;
            font-size: 14px;
        }

        .nav-item:hover {
            background: #1e293b;
            color: white;
        }

        .nav-item.active {
            background: #2563eb;
            color: white;
        }

        .nav-icon {
            width: 22px;
            text-align: center;
        }

        .main-content {
            flex: 1;
            min-width: 0;
        }

        .topbar {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 15px 25px;
        }

    </style>

    @stack('styles')

</head>

<body>

<div class="d-flex">

    @include('partials.sidebar-navigation')


    {{-- MAIN --}}
    <main class="main-content">

        <div class="topbar">

            <div class="d-flex justify-content-between align-items-center">

                <div>
                    <strong>
                        Guardium Security Center
                    </strong>
                </div>

                <div class="text-muted">
                    <span style="color: #42be65;">●</span>
                    System operational
                </div>

            </div>

        </div>


        <div class="p-4">

            {{-- SUCCESS --}}
            @if(session('success'))

                <div class="alert alert-success">

                    {{ session('success') }}

                </div>

            @endif


            {{-- ERROR --}}
            @if($errors->any())

                <div class="alert alert-danger">

                    <strong>
                        Terjadi kesalahan:
                    </strong>

                    <ul class="mb-0 mt-2">

                        @foreach($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            {{-- PAGE CONTENT --}}
            @yield('content')

        </div>

    </main>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

@stack('scripts')

</body>

</html>
