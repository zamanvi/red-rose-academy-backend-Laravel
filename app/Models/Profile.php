<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'date',
        'once',
        'points',
        'bio',
        'designation',
        'birthday',
        'gender',
        'about',
        'phone',
        'institute',
        'address',
        'upazila_id',
        'city_id',
        'division_id',
        'country_id',
        'company_name',
    ];
    protected $hidden = ['phone', 'address'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
