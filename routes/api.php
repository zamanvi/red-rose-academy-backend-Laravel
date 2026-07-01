<?php

use App\Http\Controllers\SuperAdmin\ChapterController;
use App\Http\Controllers\SuperAdmin\LessonController;
use App\Http\Controllers\SuperAdmin\WordController;
use App\Http\Controllers\SuperAdmin\NoticeController;
use App\Http\Controllers\SuperAdmin\SettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('grammer')->middleware(['grammer', 'throttle:60,1'])->group(function () {
    Route::get('/initial', [ChapterController::class, 'initial']);
    Route::get('/chapters', [ChapterController::class, 'index']);
    Route::get('/chapter/show/{id}', [ChapterController::class, 'show']);
    Route::get('/lessons/{id}', [LessonController::class, 'chapters_lessons_create']);
    Route::get('/lesson/show/{id}', [LessonController::class, 'show']);
    Route::get('/words/{id}', [WordController::class, 'chapters_lessons_words_create']);
    Route::get('/word/show/{id}', [WordController::class, 'show']);
    Route::get('/notices', [NoticeController::class, 'apiIndex']);
    Route::get('/settings', [SettingController::class, 'apiIndex']);
});

