<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{

    use HasFactory, Notifiable, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'lang',
        'site_id',
        'admin',
        'user_id',
        'cognome',
        'matricola',
        'is_ad_user'
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*']);
    }

    public function active_apps(): BelongsToMany
    {
        return $this->BelongsToMany(ActiveApp::class);
    }

    public function site(): BelongsTo
    {
        return $this->BelongsTo(Site::class);
    }

    public static function langs(){
        $array["IT"] = "Italiano";
        $array["EN"] = "Inglese";
        return $array;
    }
}
