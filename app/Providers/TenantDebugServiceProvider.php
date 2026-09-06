<?php

namespace App\Providers;

use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\ServiceProvider;

/**
 * Provider tools debugging untuk konteks multi-tenancy EduZone.
 *
 * Hanya aktif di local/develop (tidak register bindings di production).
 */
class TenantDebugServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (! app()->environment(['local', 'development', 'testing'])) {
            return;
        }

        // ── explainTenantScope(): cek apakah query ke-filter tenant dgn benar ─
        Builder::macro('explainTenantScope', function (): array {
            /** @var Builder $this */
            $builder = clone $this;

            $scopes = $builder->appliedScopes;

            $hasSchoolScope = false;
            $schoolScopeClass = \App\Multitenancy\Scopes\SchoolScope::class;
            foreach ($scopes as $scope) {
                if ($scope instanceof $schoolScopeClass) {
                    $hasSchoolScope = true;
                    break;
                }
            }

            // Cek ada where school_id di bindings (cara manual)
            $sql = $builder->toRawSql();
            $schoolTable = $this->getModel()->getTable();
            $schoolIdBindingCol = "`{$schoolTable}`.`school_id`";
            $schoolIdPgCol = "\"{$schoolTable}\".\"school_id\"";
            $hasWhereSchoolId = str_contains($sql, $schoolIdBindingCol) || str_contains($sql, $schoolIdPgCol)
                || str_contains($sql, '.school_id')
                || str_contains($sql, '"school_id"');

            $currentTenant = School::current();

            return [
                'model'             => get_class($this->getModel()),
                'table'             => $schoolTable,
                'has_school_scope'  => $hasSchoolScope,
                'current_tenant_id' => $currentTenant?->id,
                'current_tenant'    => $currentTenant?->name . ($currentTenant?->slug ? " ({$currentTenant->slug})" : ''),
                'tenant_scope_applied_in_sql' => $hasWhereSchoolId,
                'sql_preview'       => $sql,
                'bindings_count'    => count($this->getBindings()),
            ];
        });

        // ── dumpTenantScope(): var_dump explain + die ─────────────────────────
        Builder::macro('dumpTenantScope', function (): Builder {
            /** @var Builder $this */
            $info = $this->explainTenantScope();
            dump($info);
            return $this;
        });

        Builder::macro('ddTenantScope', function (): never {
            /** @var Builder $this */
            dd($this->explainTenantScope());
        });

        // ── toRawSql() fallback kalau versi Laravel tidak punya (untuk BaseBuilder) ─
        if (! BaseBuilder::hasMacro('toRawSql')) {
            BaseBuilder::macro('toRawSql', function (): string {
                /** @var BaseBuilder $this */
                $sql = $this->toSql();
                foreach ($this->getBindings() as $binding) {
                    $value = is_numeric($binding) ? $binding : "'" . addslashes((string) $binding) . "'";
                    $sql = preg_replace('/\?/', (string) $value, $sql, 1);
                }
                return $sql;
            });
            Builder::macro('toRawSql', function (): string {
                return $this->toBase()->toRawSql();
            });
        }
    }
}
