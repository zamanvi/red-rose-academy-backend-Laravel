<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory;
    protected $fillable = ['email', 'otp'];
    protected $hidden = ['otp'];

    public static function createStore($email, $otp): bool
    {
        $newEntry = self::create([
            'email' => $email,
            'otp' => $otp,
        ]);
        return $newEntry instanceof self;
    }
    public static function updateStore($email, $otp, $id): bool
    {
        $updateEntry = self::where('id', $id)->update([
            'email' => $email,
            'otp' => $otp,
        ]);
        return $updateEntry;
    }
    public static function destroyStore($id): bool
    {
        $deleteEntry = self::where('id', $id)->delete();
        return $deleteEntry;
    }
}
