<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\Setting;
use Illuminate\Http\Response;

class SettingController extends Controller
{
    /**
     * API: return all settings as key-value object
     */
    public function apiIndex()
    {
        $settings = Setting::all();
        $data = [];
        foreach ($settings as $setting) {
            $data[$setting->key] = $setting->value;
        }
        return ApiResponse::respond(['settings' => $data], true, 'All settings', Response::HTTP_OK);
    }
}