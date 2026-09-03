<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureCurrentTeamMembership;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityRouteAuthorizationGuardTest extends TestCase
{
    /**
     * Routes that must always require authentication
     * and valid current-team membership.
     *
     * @return array<int, string>
     */
    private function securityRoutePrefixes(): array
    {
        return [
            'database-connections',
            'database-explorer',
            'database-activities',
            'database-discovery',
            'sensitive-data',
            'database-query',
            'database-users',
            'database-privileges',
            'sql-query',
            'query-history',
            'security-audit',
            'security-alerts',
            'security-incidents',
            'security-policies',
            'vulnerability-assessments',
            'security-reports',
            'security-findings',
            'security-risk',
            'security-dashboard',
        ];
    }

    /**
     * Routes whose operations must remain admin-only.
     *
     * @return array<int, string>
     */
    private function adminOnlyRouteNames(): array
    {
        return [
            'database-connections.index',
            'database-connections.create',
            'database-connections.store',
            'database-connections.show',
            'database-connections.destroy',
            'database-connections.test',
            'database-connections.scan',

            'database-explorer.show',

            'database-discovery.index',
            'database-discovery.scan',
            'database-discovery.show',
            'database-discovery.table',

            'sensitive-data.index',
            'sensitive-data.scan',

            'database-query.index',
            'database-query.execute',

            'database-users.index',
            'database-users.scan',

            'database-privileges.index',
            'database-privileges.scan',

            'sql-query.index',
            'sql-query.execute',

            'query-history.index',
            'query-history.show',

            'security-audit.scan',

            'security-policies.index',
            'security-policies.create',
            'security-policies.store',
            'security-policies.edit',
            'security-policies.update',
            'security-policies.destroy',
            'security-policies.toggle',

            'vulnerability-assessments.scan',

            'security-reports.rerun',
        ];
    }

    public function test_security_center_routes_require_authentication_and_current_team_membership(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(
                fn (LaravelRoute $route): bool => $this->isSecurityRoute($route)
            );

        $this->assertNotEmpty(
            $routes,
            'No Security Center routes were discovered.'
        );

        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware();

            $this->assertContains(
                'auth',
                $middleware,
                sprintf(
                    'Route [%s] must require auth middleware.',
                    $route->getName() ?? $route->uri()
                )
            );

            $this->assertContains(
                EnsureCurrentTeamMembership::class,
                $middleware,
                sprintf(
                    'Route [%s] must require current-team membership.',
                    $route->getName() ?? $route->uri()
                )
            );
        }
    }

    public function test_administrative_security_routes_remain_admin_only(): void
    {
        foreach ($this->adminOnlyRouteNames() as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull(
                $route,
                sprintf(
                    'Expected administrative route [%s] does not exist.',
                    $routeName
                )
            );

            $middleware = $route->gatherMiddleware();

            $this->assertContains(
                'security.team:admin',
                $middleware,
                sprintf(
                    'Administrative route [%s] must require security.team:admin.',
                    $routeName
                )
            );
        }
    }

    public function test_incident_and_alert_operational_routes_are_not_accidentally_admin_only(): void
    {
        $operationalRoutes = [
            'security-alerts.index',
            'security-alerts.show',
            'security-alerts.acknowledge',
            'security-alerts.investigate',
            'security-alerts.investigation-notes.store',
            'security-alerts.assign',
            'security-alerts.unassign',
            'security-alerts.resolve',
            'security-alerts.reopen',
            'security-alerts.escalate-to-incident',

            'security-incidents.index',
            'security-incidents.reports.index',
            'security-incidents.show',
            'security-incidents.acknowledge',
            'security-incidents.investigate',
            'security-incidents.contain',
            'security-incidents.resolve',
            'security-incidents.close',
            'security-incidents.assign',
            'security-incidents.unassign',
            'security-incidents.investigation-notes.store',
        ];

        foreach ($operationalRoutes as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull(
                $route,
                sprintf(
                    'Expected operational route [%s] does not exist.',
                    $routeName
                )
            );

            $middleware = $route->gatherMiddleware();

            $this->assertNotContains(
                'security.team:admin',
                $middleware,
                sprintf(
                    'Operational route [%s] should remain available to valid team members.',
                    $routeName
                )
            );
        }
    }

    private function isSecurityRoute(LaravelRoute $route): bool
    {
        $uri = $route->uri();

        foreach ($this->securityRoutePrefixes() as $prefix) {
            if (
                $uri === $prefix ||
                str_starts_with($uri, $prefix.'/')
            ) {
                return true;
            }
        }

        return false;
    }
}
