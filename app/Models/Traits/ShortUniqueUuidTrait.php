<?php

/**
 * Generates a short, unique uuid for a model
 */

namespace App\Models\Traits;

use Illuminate\Support\Str;
use Hashids\Hashids;


trait ShortUniqueUuidTrait
{

    protected static function bootShortUniqueUuidTrait()
    {
        static::creating(function ($model) {
            // create a temporary uuid, which would generate a fairly long string
            $model->uuid = (string) Str::uuid();
        });
        static::created(function ($model) {
            // once the model has been created, we have an id field to feed in
            $model->uuid = self::getUniqueShortUniqueUuid($model);
            $model->save();
        });
    }

    protected static function getUniqueShortUniqueUuid($model)
    {
        $hashids = new Hashids(class_basename($model), 7);
        $uuid = $hashids->encode($model->id . time());

        for ($i = 0; $i < 100; $i++) {
            $basename = get_class($model);
            $existing = $basename::where('uuid', $uuid)->first();
            if (!$existing) {
                return $uuid;
            }
            $uuid = $hashids->encode($model->id . '-' . $i);
        }

        // We couldn't get a unique id
        return (string) Str::uuid();
    }
}
