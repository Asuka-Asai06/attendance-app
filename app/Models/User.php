<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'admin_status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'admin_status' => 'boolean',
    ];

    /**
     * ユーザーの勤怠記録。
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * ユーザーが申請した勤怠修正申請。
     */
    public function CorrectionRequests(): HasMany
    {
        return $this->hasMany(
            CorrectionRequest::class
        );
    }

    /**
     * ユーザーが承認した勤怠修正申請。
     */
    public function approvedCorrectionRequests(): HasMany
    {
        return $this->hasMany(
            CorrectionRequest::class,
            'approved_by'
        );
    }
}
