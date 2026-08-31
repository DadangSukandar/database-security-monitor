<?php

namespace App\Http\Controllers;

use App\Models\DatabaseActivity;
use App\Models\DatabaseConnection;
use App\Services\DatabaseActivityLogger;
use App\Services\DatabaseConnectorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class DatabaseQueryController extends Controller
{
    public function index()
    {
        $connections = DatabaseConnection::query()
            ->orderBy('name')
            ->get();

        $history = DatabaseActivity::query()
            ->where('action', 'QUERY')
            ->with('databaseConnection')
            ->latest('executed_at')
            ->paginate(20);

        return view(
            'database-query.index',
            compact(
                'connections',
                'history'
            )
        );
    }

    public function execute(
        Request $request,
        DatabaseConnectorService $connector,
        DatabaseActivityLogger $activityLogger
    ) {
        $validator = Validator::make(
            $request->all(),
            [
                'connection_id' => [
                    'required',
                    'integer',
                    'exists:database_connections,id',
                ],

                'sql' => [
                    'required',
                    'string',
                    'max:10000',
                ],
            ]
        );

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $connection = DatabaseConnection::findOrFail(
            $request->connection_id
        );

        $sql = trim($request->sql);

        /*
        |--------------------------------------------------------------------------
        | Hapus ; di akhir query
        |--------------------------------------------------------------------------
        */

        $sql = rtrim($sql, " \t\n\r\0\x0B;");

        /*
        |--------------------------------------------------------------------------
        | Validasi query
        |--------------------------------------------------------------------------
        */

        $validationError =
            $this->validateReadOnlyQuery($sql);

        if ($validationError) {

            return back()
                ->withErrors([
                    'sql' => $validationError,
                ])
                ->withInput();
        }

        $startTime = microtime(true);

        try {

            $db = $connector->connect(
                $connection
            );

            /*
            |--------------------------------------------------------------------------
            | Jalankan SELECT
            |--------------------------------------------------------------------------
            */

            $rows = $db->select($sql);

            $executionTimeMs = (int) round(
                (microtime(true) - $startTime) * 1000
            );

            /*
            |--------------------------------------------------------------------------
            | Ubah object menjadi array
            |--------------------------------------------------------------------------
            */

            $rows = array_map(
                fn ($row) => (array) $row,
                $rows
            );

            /*
            |--------------------------------------------------------------------------
            | Batasi hasil yang ditampilkan
            |--------------------------------------------------------------------------
            */

            $totalRows = count($rows);

            $displayRows = array_slice(
                $rows,
                0,
                500
            );

            /*
            |--------------------------------------------------------------------------
            | Log aktivitas
            |--------------------------------------------------------------------------
            */

            $activityLogger->success(
                $connection,
                $sql,
                'QUERY',
                null,
                $executionTimeMs
            );

            return view(
                'database-query.result',
                compact(
                    'connection',
                    'sql',
                    'displayRows',
                    'totalRows',
                    'executionTimeMs'
                )
            );

        } catch (Throwable $e) {

            $executionTimeMs = (int) round(
                (microtime(true) - $startTime) * 1000
            );

            $activityLogger->failed(
                $connection,
                $sql,
                'QUERY',
                null,
                $e,
                $executionTimeMs
            );

            return back()
                ->withErrors([
                    'sql' =>
                        'Query gagal: ' .
                        $e->getMessage(),
                ])
                ->withInput();
        }
    }

    private function validateReadOnlyQuery(
        string $sql
    ): ?string {

        if ($sql === '') {
            return 'Query tidak boleh kosong.';
        }

        /*
        |--------------------------------------------------------------------------
        | Hanya SELECT / WITH
        |--------------------------------------------------------------------------
        */

        if (!preg_match(
            '/^(SELECT|WITH)\b/i',
            $sql
        )) {

            return
                'Query Console saat ini hanya mengizinkan SELECT atau WITH.';
        }

        /*
        |--------------------------------------------------------------------------
        | Tolak multiple statement
        |--------------------------------------------------------------------------
        */

        if (str_contains($sql, ';')) {

            return
                'Multiple SQL statement tidak diperbolehkan.';
        }

        /*
        |--------------------------------------------------------------------------
        | Keyword berbahaya
        |--------------------------------------------------------------------------
        */

        $blocked = [
            'INSERT',
            'UPDATE',
            'DELETE',
            'DROP',
            'ALTER',
            'TRUNCATE',
            'CREATE',
            'REPLACE',
            'GRANT',
            'REVOKE',
            'RENAME',
            'MERGE',
            'CALL',
            'EXEC',
            'EXECUTE',
            'VACUUM',
        ];

        foreach ($blocked as $keyword) {

            if (preg_match(
                '/\b' .
                preg_quote($keyword, '/') .
                '\b/i',
                $sql
            )) {

                return
                    "Keyword {$keyword} tidak diperbolehkan.";
            }
        }

        return null;
    }
}