<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiagnosticController;

Route::get('/diagnostic/summary', [DiagnosticController::class, 'summary']);
Route::get('/diagnostic/schedules', [DiagnosticController::class, 'checkSchedules']);
Route::get('/diagnostic/block', [DiagnosticController::class, 'checkBlockSchedule']);
Route::get('/diagnostic/subjects', [DiagnosticController::class, 'checkSubjects']);
Route::get('/diagnostic/subject/{id}', [DiagnosticController::class, 'checkSubjectDetail']);
