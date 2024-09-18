<?php

namespace IsoDoss\TerritorialDivision\Http\Controllers\Api\V1;

use IsoDoss\TerritorialDivision\Http\Controllers\Controller;
use IsoDoss\TerritorialDivision\Http\Resources\SendApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocalizationController extends Controller
{
    /**
     * Get language.
     *
     * @return JsonResponse The response.
     */
    public static function getLanguage(): JsonResponse
    {
        return (new SendApiResponse(true, __('messages.localization.get-language.success', ['language' => App::getLocale()]), [], [], [], ['language' => App::getLocale()]))->response();
    }

    /**
     * Set language.
     *
     * @param string $language The language.
     * @return JsonResponse The response.
     */
    public static function setLanguage(string $language): JsonResponse
    {
        Session::put('lang', $language);

        return (new SendApiResponse(true, __('messages.localization.set-language.success'), [], [], ['language' => $language], ['language' => App::getLocale()]))->response();
    }
}
