<?php

namespace IsoDoss\TerritorialDivision\Http\Controllers;

use IsoDoss\TerritorialDivision\Http\Controllers\Api\V1\LocalizationController as LocalizationControllerApiController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;

class LocalizationController extends Controller
{
    /**
     * Get language.
     *
     * @return string The language.
     */
    public function getLanguage(): string
    {
        return App::getLocale();
    }

    /**
     * Set language.
     *
     * @param string $language The language.
     * @return RedirectResponse The redirect response.
     */
    public function setLanguage(string $language): RedirectResponse
    {
        LocalizationControllerApiController::setLanguage($language);

        return redirect()->back();
    }
}
