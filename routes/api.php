<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use IsoDoss\TerritorialDivision\Http\Controllers\Api\V1\AuthController;
use IsoDoss\TerritorialDivision\Http\Controllers\Api\V1\LocalizationController;
use IsoDoss\TerritorialDivision\Http\Resources\SendApiResponse;

Route::name('v1.')->prefix('/v1')->group(function () {
	// Resource doesn't exist endpoint.
	Route::fallback(function (Request $request) {
		return (new SendApiResponse(false, __('messages.fallback'), [], [], $request->all(), [], 404))->response();
	});

	// Unauthorized endpoint.
	Route::name('unauthorized')->get('/unauthorized', function (Request $request) {
		return (new SendApiResponse(false, __('messages.unauthorized'), [], [], $request->all(), [], 401))->response();
	});

	// Language endpoints.
	Route::name('language.')->prefix('/language')->group(function () {
		Route::get('/', [LocalizationController::class, 'getLanguage'])->name('get-lang');
		Route::get('/{lang}', [LocalizationController::class, 'setLanguage'])->name('set-lang');
	});

	// Auth endpoints.
	Route::middleware('guest:sanctum')->name('auth.')->prefix('/auth')->group(function () {
		Route::post('/sign-up', [AuthController::class, 'signUp'])->name('sign-up');
		Route::post('/send-email-validate-account', [AuthController::class, 'sendEmailValidateAccount'])->name('send-email-validate-account');
		Route::get('/validate-account/{email}/{role}/{token}', [AuthController::class, 'validateAccount'])->name('validate-account');
		Route::post('/sign-in', [AuthController::class, 'signIn'])->name('sign-in');
		Route::post('/password/forgot', [AuthController::class, 'forgotPassword'])->name('forgot-password');
		Route::post('/password/reset/{email}/{role}/{token}', [AuthController::class, 'resetPassword'])->name('reset-password');
		Route::post('/sign-out', [AuthController::class, 'signOut'])->name('sign-out')->withoutMiddleware('guest:sanctum')->middleware(['auth:sanctum']);
	});

	Route::middleware(['auth:sanctum'])->group(function () {
	});
});