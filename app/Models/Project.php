<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Traits\ShortUniqueUuidTrait;

class Project extends Model
{
    use HasFactory, ShortUniqueUuidTrait;

    protected $fillable = [
        'uuid',
        'slug',
        'id_addr',
        'title',
        'description',
        'website',
        'userGithub',
        'projectGithub',
        'projectTwitter',
        'metadata',
        'flagged_at',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'owners' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        // Before creating a project, set its slug
        static::creating(function (Project $project) {
            $project->slug = $project->createUniqueSlug();
        });
    }

    public function applications()
    {
        return $this->hasMany(RoundApplication::class, 'project_addr', 'id_addr');
    }

    public function rounds()
    {
        return $this->hasMany(Round::class, 'project_addr', 'id_addr');
    }

    public function chains()
    {
        return $this->hasMany(Chain::class, 'project_addr', 'id_addr');
    }

    public function owners()
    {
        return $this->hasMany(ProjectOwner::class, 'project_id', 'id');
    }

    public function donations()
    {
        return $this->hasMany(ProjectDonation::class, 'project_id', 'id');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Create a unique slug for the project.
     */
    public function createUniqueSlug()
    {
        $slug = Str::slug($this->title);
        $count = static::where('slug', 'LIKE', "{$slug}%")->count();

        if ($count > 0) {
            for ($i = 1; $i <= 100; $i++) {
                $slug = Str::slug($this->title) . '-' . $i;
                if (!static::where('slug', $slug)->count()) {
                    break;
                }
            }
        }

        return $slug;
    }
}
