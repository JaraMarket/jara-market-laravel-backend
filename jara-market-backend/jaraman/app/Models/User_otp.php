<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User_otp extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'otp',
        'email',
    ];
}
