<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ActiveApp extends Model
{
    use LogsActivity,  SoftDeletes;

    protected $fillable = [
        'name_it',
        'name_en',
        'code',
        'site_id',
        'azienda',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*']);
    }

    protected $appends = ['name', 'icon_link'];




    public function getNameAttribute()
    {
        if (Auth::user()->admin or Auth::user()->lang == "EN")
            return $this->name_en;
        else
            return $this->name_it;
    }

    public function site(): BelongsTo
    {
        return $this->BelongsTo(Site::class);
    }

    public function getIconLinkAttribute()
    {
        return route('active_apps.image', ["id" => $this->id]);
    }

}
