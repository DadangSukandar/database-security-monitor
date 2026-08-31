<?php

namespace App\Http\Controllers;

use App\Models\SecurityPolicy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SecurityPolicyController extends Controller
{
    /**
     * Display security policies.
     */
    public function index(Request $request)
    {
        $query = SecurityPolicy::query();

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('rule_type', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Rule type filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('rule_type')) {
            $query->where(
                'rule_type',
                $request->rule_type
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Severity filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('severity')) {
            $query->where(
                'severity',
                $request->severity
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Status filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            if ($request->status === 'active') {
                $query->where('is_active', true);
            }

            if ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $totalPolicies = SecurityPolicy::count();

        $activePolicies = SecurityPolicy::where(
            'is_active',
            true
        )->count();

        $inactivePolicies = SecurityPolicy::where(
            'is_active',
            false
        )->count();

        $criticalPolicies = SecurityPolicy::where(
            'severity',
            'CRITICAL'
        )->count();

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $policies = $query
            ->orderBy('priority')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view(
            'security-policies.index',
            compact(
                'policies',
                'totalPolicies',
                'activePolicies',
                'inactivePolicies',
                'criticalPolicies'
            )
        );
    }


    /**
     * Show create form.
     */
    public function create()
    {
        return view(
            'security-policies.create'
        );
    }


    /**
     * Store new policy.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:100',
                'unique:security_policies,code',
            ],

            'rule_type' => [
                'required',
                'string',
                'max:100',
            ],

            'severity' => [
                'required',
                Rule::in([
                    'CRITICAL',
                    'HIGH',
                    'MEDIUM',
                    'LOW',
                ]),
            ],

            'priority' => [
                'required',
                'integer',
                'min:1',
                'max:9999',
            ],

            'conditions' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Convert conditions JSON
        |--------------------------------------------------------------------------
        */

        $conditions = null;

        if (!empty($validated['conditions'])) {

            $decoded = json_decode(
                $validated['conditions'],
                true
            );

            if (
                json_last_error() !== JSON_ERROR_NONE
            ) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'conditions' =>
                            'Conditions harus berupa JSON yang valid.'
                    ]);
            }

            $conditions = $decoded;
        }

        /*
        |--------------------------------------------------------------------------
        | Create
        |--------------------------------------------------------------------------
        */

        SecurityPolicy::create([
            'name' => $validated['name'],
            'code' => strtoupper(
                $validated['code']
            ),
            'rule_type' => $validated['rule_type'],
            'severity' => $validated['severity'],
            'conditions' => $conditions,
            'priority' => $validated['priority'],
            'is_active' =>
                $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('security-policies.index')
            ->with(
                'success',
                'Security policy berhasil dibuat.'
            );
    }


    /**
     * Show edit form.
     */
    public function edit(
        SecurityPolicy $securityPolicy
    ) {
        return view(
            'security-policies.edit',
            compact('securityPolicy')
        );
    }


    /**
     * Update policy.
     */
    public function update(
        Request $request,
        SecurityPolicy $securityPolicy
    ) {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique(
                    'security_policies',
                    'code'
                )->ignore($securityPolicy->id),
            ],

            'rule_type' => [
                'required',
                'string',
                'max:100',
            ],

            'severity' => [
                'required',
                Rule::in([
                    'CRITICAL',
                    'HIGH',
                    'MEDIUM',
                    'LOW',
                ]),
            ],

            'priority' => [
                'required',
                'integer',
                'min:1',
                'max:9999',
            ],

            'conditions' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Conditions
        |--------------------------------------------------------------------------
        */

        $conditions = null;

        if (!empty($validated['conditions'])) {

            $decoded = json_decode(
                $validated['conditions'],
                true
            );

            if (
                json_last_error() !== JSON_ERROR_NONE
            ) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'conditions' =>
                            'Conditions harus berupa JSON yang valid.'
                    ]);
            }

            $conditions = $decoded;
        }

        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        $securityPolicy->update([
            'name' => $validated['name'],
            'code' => strtoupper(
                $validated['code']
            ),
            'rule_type' => $validated['rule_type'],
            'severity' => $validated['severity'],
            'conditions' => $conditions,
            'priority' => $validated['priority'],
            'is_active' =>
                $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('security-policies.index')
            ->with(
                'success',
                'Security policy berhasil diperbarui.'
            );
    }


    /**
     * Delete policy.
     */
    public function destroy(
        SecurityPolicy $securityPolicy
    ) {
        $securityPolicy->delete();

        return redirect()
            ->route('security-policies.index')
            ->with(
                'success',
                'Security policy berhasil dihapus.'
            );
    }


    /**
     * Toggle policy status.
     */
    public function toggle(
        SecurityPolicy $securityPolicy
    ) {
        $securityPolicy->update([
            'is_active' =>
                !$securityPolicy->is_active,
        ]);

        return back()->with(
            'success',
            'Status security policy berhasil diubah.'
        );
    }
}