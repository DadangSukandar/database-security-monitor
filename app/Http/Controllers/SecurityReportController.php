<?php

namespace App\Http\Controllers;

use App\Models\VulnerabilityAssessment;
use Illuminate\Http\Request;
use Throwable;

class SecurityReportController extends Controller
{
    /**
     * Halaman daftar Security Reports.
     */
    public function index(Request $request)
    {
        $query = VulnerabilityAssessment::query()
            ->with('databaseConnection');

        /*
        * Search
        */
        $search = trim(
            (string) $request->input('search')
        );

        if ($search !== '') {

            $query->where(function ($q) use ($search) {

                $q->where(
                    'database_name',
                    'like',
                    '%' . $search . '%'
                )

                ->orWhereHas(
                    'databaseConnection',
                    function ($connectionQuery) use ($search) {

                        $connectionQuery->where(
                            'name',
                            'like',
                            '%' . $search . '%'
                        );
                    }
                );
            });
        }


        /*
        * Status filter
        */
        $status =
            strtoupper(
                (string) $request->input('status')
            );

        if (
            in_array(
                $status,
                [
                    'SCANNING',
                    'COMPLETED',
                ],
                true
            )
        ) {

            $query->where(
                'status',
                $status
            );
        }


        /*
        * Score filter
        */
        $risk =
            strtoupper(
                (string) $request->input('risk')
            );

        switch ($risk) {

            case 'LOW':

                $query->where(
                    'score',
                    '>=',
                    90
                );

                break;


            case 'MEDIUM':

                $query->whereBetween(
                    'score',
                    [75, 89]
                );

                break;


            case 'HIGH':

                $query->whereBetween(
                    'score',
                    [50, 74]
                );

                break;


            case 'CRITICAL':

                $query->where(
                    'score',
                    '<',
                    50
                );

                break;
        }


        /*
        * Pagination.
        */
        $assessments = $query
            ->latest('scanned_at')
            ->paginate(10)
            ->withQueryString();


        /*
        * Statistik keseluruhan.
        */
        $totalAssessments =
            VulnerabilityAssessment::count();


        $critical =
            VulnerabilityAssessment::sum(
                'critical_count'
            );


        $high =
            VulnerabilityAssessment::sum(
                'high_count'
            );


        $medium =
            VulnerabilityAssessment::sum(
                'medium_count'
            );


        $low =
            VulnerabilityAssessment::sum(
                'low_count'
            );


        $totalFindings =
            $critical +
            $high +
            $medium +
            $low;


        /*
        * Assessment terakhir.
        */
        $latestAssessment =
            VulnerabilityAssessment::query()
                ->with('databaseConnection')
                ->latest('scanned_at')
                ->first();


        /*
        * Average score.
        */
        $averageScore =
            VulnerabilityAssessment::query()
                ->whereNotNull('score')
                ->avg('score');


        $averageScore =
            $averageScore !== null
                ? round($averageScore, 1)
                : 0;


        /*
        * Best assessment.
        */
        $bestAssessment =
            VulnerabilityAssessment::query()
                ->with('databaseConnection')
                ->whereNotNull('score')
                ->orderByDesc('score')
                ->first();


        /*
        * Worst assessment.
        */
        $worstAssessment =
            VulnerabilityAssessment::query()
                ->with('databaseConnection')
                ->whereNotNull('score')
                ->orderBy('score')
                ->first();


        return view(
            'security-reports.index',
            compact(
                'assessments',
                'totalAssessments',
                'critical',
                'high',
                'medium',
                'low',
                'totalFindings',
                'latestAssessment',
                'averageScore',
                'bestAssessment',
                'worstAssessment',
                'search',
                'status',
                'risk'
            )
        );
    }


    /**
     * Detail Security Report.
     */
    public function show(
        VulnerabilityAssessment $assessment
    ) {

        /*
         * Load relationship.
         */
        $assessment->load([
            'databaseConnection',
            'findings',
        ]);


        /*
         * Hitung total findings.
         */
        $totalFindings =
            $assessment->findings->count();


        /*
         * Hitung severity jika diperlukan
         * oleh halaman detail.
         */
        $critical =
            $assessment->findings
                ->where('severity', 'CRITICAL')
                ->count();


        $high =
            $assessment->findings
                ->where('severity', 'HIGH')
                ->count();


        $medium =
            $assessment->findings
                ->where('severity', 'MEDIUM')
                ->count();


        $low =
            $assessment->findings
                ->where('severity', 'LOW')
                ->count();


        return view(
            'security-reports.show',
            compact(
                'assessment',
                'totalFindings',
                'critical',
                'high',
                'medium',
                'low'
            )
        );
    }


    /**
     * Print Security Report.
     */
    public function print(
        VulnerabilityAssessment $assessment
    ) {

        /*
         * Load seluruh data yang diperlukan
         * untuk report.
         */
        $assessment->load([
            'databaseConnection',
            'findings',
        ]);


        /*
         * Statistik findings.
         */
        $totalFindings =
            $assessment->findings->count();


        $critical =
            $assessment->findings
                ->where('severity', 'CRITICAL')
                ->count();


        $high =
            $assessment->findings
                ->where('severity', 'HIGH')
                ->count();


        $medium =
            $assessment->findings
                ->where('severity', 'MEDIUM')
                ->count();


        $low =
            $assessment->findings
                ->where('severity', 'LOW')
                ->count();


        return view(
            'security-reports.print',
            compact(
                'assessment',
                'totalFindings',
                'critical',
                'high',
                'medium',
                'low'
            )
        );
    }

    /**
     * Menentukan risk rating berdasarkan score.
     */
    private function getRiskRating(int|float|null $score): array
    {
        $score = (int) ($score ?? 0);

        if ($score >= 90) {
            return [
                'level' => 'LOW',
                'label' => 'Low Risk',
                'description' =>
                    'Database memiliki tingkat keamanan yang baik dan hanya membutuhkan perbaikan minor.',
            ];
        }

        if ($score >= 75) {
            return [
                'level' => 'MEDIUM',
                'label' => 'Medium Risk',
                'description' =>
                    'Ditemukan beberapa kelemahan keamanan yang sebaiknya segera diperbaiki.',
            ];
        }

        if ($score >= 50) {
            return [
                'level' => 'HIGH',
                'label' => 'High Risk',
                'description' =>
                    'Ditemukan kelemahan keamanan yang cukup serius dan membutuhkan tindakan perbaikan.',
            ];
        }

        return [
            'level' => 'CRITICAL',
            'label' => 'Critical Risk',
            'description' =>
                'Database memiliki risiko keamanan tinggi dan membutuhkan tindakan perbaikan segera.',
        ];
    }

    public function rerun(
        VulnerabilityAssessment $assessment,
        VulnerabilityAssessmentController $vulnerabilityAssessmentController
    ) {
        /*
        * Pastikan assessment mempunyai
        * database connection.
        */
        $assessment->load('databaseConnection');

        if (!$assessment->databaseConnection) {

            return back()->withErrors([
                'rerun' =>
                    'Database connection untuk assessment ini tidak ditemukan.'
            ]);
        }


        /*
        * Jalankan kembali scan menggunakan
        * controller vulnerability assessment.
        *
        * Kita membuat Request baru agar
        * method scan() dapat menggunakan
        * database_connection_id yang sama.
        */
        $request = Request::create(
            route('vulnerability-assessments.scan'),
            'POST',
            [
                'database_connection_id' =>
                    $assessment->database_connection_id,
            ]
        );


        /*
        * Salin session user saat ini.
        */
        $request->setLaravelSession(
            request()->session()
        );


        /*
        * Jalankan scan.
        */
        return $vulnerabilityAssessmentController->scan(
            $request,
            app(\App\Services\DatabaseConnectorService::class)
        );
    }

    public function comparison(
        VulnerabilityAssessment $assessment
    ) {
        /*
        * Cari assessment sebelumnya
        * untuk database connection yang sama.
        */
        $previousAssessment =
            VulnerabilityAssessment::query()
                ->where(
                    'database_connection_id',
                    $assessment->database_connection_id
                )
                ->where(
                    'id',
                    '<',
                    $assessment->id
                )
                ->latest('id')
                ->first();


        /*
        * Jika belum pernah ada assessment
        * sebelumnya.
        */
        if (!$previousAssessment) {

            return view(
                'security-reports.comparison',
                compact(
                    'assessment',
                    'previousAssessment'
                )
            );
        }


        /*
        * Hitung perubahan score.
        */
        $scoreChange =
            $assessment->score -
            $previousAssessment->score;


        /*
        * Hitung perubahan finding.
        */
        $criticalChange =
            $assessment->critical_count -
            $previousAssessment->critical_count;

        $highChange =
            $assessment->high_count -
            $previousAssessment->high_count;

        $mediumChange =
            $assessment->medium_count -
            $previousAssessment->medium_count;

        $lowChange =
            $assessment->low_count -
            $previousAssessment->low_count;


        /*
        * Total findings.
        */
        $previousTotal =
            $previousAssessment->critical_count +
            $previousAssessment->high_count +
            $previousAssessment->medium_count +
            $previousAssessment->low_count;


        $currentTotal =
            $assessment->critical_count +
            $assessment->high_count +
            $assessment->medium_count +
            $assessment->low_count;


        $totalChange =
            $currentTotal -
            $previousTotal;


        return view(
            'security-reports.comparison',
            compact(
                'assessment',
                'previousAssessment',
                'scoreChange',
                'criticalChange',
                'highChange',
                'mediumChange',
                'lowChange',
                'previousTotal',
                'currentTotal',
                'totalChange'
            )
        );
    }
}