<?php

namespace App\Models\Concerns;

use App\Support\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $company = app(TenantContext::class)->company();

            if ($company !== null) {
                $builder->where($builder->getModel()->getTable().'.company_id', $company->id);
            }
        });

        static::creating(function ($model): void {
            $company = app(TenantContext::class)->company();

            if ($company !== null) {
                $model->company_id = $company->id;
            }
        });
    }

    public static function withoutTenant(): Builder
    {
        return (new static())->newQueryWithoutScope('tenant');
    }
}
