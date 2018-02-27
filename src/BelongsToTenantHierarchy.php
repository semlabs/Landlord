<?php

namespace HipsterJazzbo\Landlord;

use HipsterJazzbo\Landlord\Exceptions\ModelNotFoundForTenantException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @mixin Model
 */
trait BelongsToTenantHierarchy
{
    use BelongsTo;

    /**
     * Boot the trait. Will apply any scopes currently set, and
     * register a listener for when new models are created.
     */
    public static function bootBelongsToTenantHierarchy()
    {
        // Grab our singleton from the container
        static::$landlord = app(TenantManager::class);

        // Add a global scope for each tenant this model should be scoped by.
        static::$landlord->applyTenantHierarchyScopes(new static());

        // Add tenantColumns automatically when creating models
        static::creating(function (Model $model) {
            static::$landlord->newModel($model);
        });
    }

    public function delete()
    {
        $deleted = false;
        static::$landlord->modelTenants($this)->each(function ($tenantId, $tenantColumn) use (&$deleted) {
            if(static::$landlord->getTenants()->first()->first() === $this->{$tenantColumn}) {
                parent::delete();
                $deleted = true;
            }
        });
        if (!$deleted) {
           throw new ModelNotFoundException();
        }
    }

    public function update(array $attributes = [], array $options = [])
    {
        $updated = false;
        static::$landlord->modelTenants($this)->each(function ($tenantId, $tenantColumn) use ($attributes, $options, &$updated) {
            if(static::$landlord->getTenants()->first()->first() === $this->{$tenantColumn}) {
                parent::update($attributes, $options);
                $updated = true;
            }
        });
        if (!$updated) {
           throw new ModelNotFoundException();
        }
    }
}
