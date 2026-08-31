@extends('app')

@section('content')

<div class="container">

    <div class="mb-4">

        <h2 class="fw-bold">
            Add Database Connection
        </h2>

        <p class="text-muted">
            Tambahkan database yang ingin dimonitor.
        </p>

    </div>


    @if($errors->any())

        <div class="alert alert-danger">

            <ul class="mb-0">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif


    <div class="card shadow-sm border-0">

        <div class="card-body">

            <form
                method="POST"
                action="{{ route(
                    'database-connections.store'
                ) }}"
            >

                @csrf


                <div class="row g-3">

                    <div class="col-md-6">

                        <label class="form-label">
                            Connection Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            value="{{ old('name') }}"
                            placeholder="Production Database"
                            required
                        >

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Database Driver
                        </label>

                        <select
                            name="driver"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Select Driver
                            </option>

                            <option
                                value="mysql"
                                @selected(old('driver') === 'mysql')
                            >
                                MySQL
                            </option>

                            <option
                                value="pgsql"
                                @selected(old('driver') === 'pgsql')
                            >
                                PostgreSQL
                            </option>

                        </select>

                    </div>


                    <div class="col-md-8">

                        <label class="form-label">
                            Host
                        </label>

                        <input
                            type="text"
                            name="host"
                            class="form-control"
                            value="{{ old('host', '127.0.0.1') }}"
                            required
                        >

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Port
                        </label>

                        <input
                            type="number"
                            name="port"
                            class="form-control"
                            value="{{ old('port', 3306) }}"
                            required
                        >

                    </div>


                    <div class="col-md-12">

                        <label class="form-label">
                            Database
                        </label>

                        <input
                            type="text"
                            name="database"
                            class="form-control"
                            value="{{ old('database') }}"
                            required
                        >

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Username
                        </label>

                        <input
                            type="text"
                            name="username"
                            class="form-control"
                            value="{{ old('username') }}"
                            required
                        >

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Password
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                        >

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Schema
                        </label>

                        <input
                            type="text"
                            name="schema"
                            class="form-control"
                            value="{{ old('schema') }}"
                            placeholder="public"
                        >

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Description
                        </label>

                        <input
                            type="text"
                            name="description"
                            class="form-control"
                            value="{{ old('description') }}"
                        >

                    </div>

                </div>


                <hr class="my-4">


                <div class="d-flex justify-content-end gap-2">

                    <a
                        href="{{ route(
                            'database-connections.index'
                        ) }}"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Test & Save Connection
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection