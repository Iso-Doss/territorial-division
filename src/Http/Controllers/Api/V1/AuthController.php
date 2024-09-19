<?php

namespace IsoDoss\TerritorialDivision\Http\Controllers\Api\V1;

use IsoDoss\TerritorialDivision\Events\SendNotificationEvent;
use IsoDoss\TerritorialDivision\Http\Controllers\Controller;
use IsoDoss\TerritorialDivision\Http\Requests\Auth\ForgotPasswordRequest;
use IsoDoss\TerritorialDivision\Http\Requests\Auth\ResetPasswordRequest;
use IsoDoss\TerritorialDivision\Http\Requests\Auth\SendEmailValidateAccountRequest;
use IsoDoss\TerritorialDivision\Http\Requests\Auth\SignInRequest;
use IsoDoss\TerritorialDivision\Http\Requests\Auth\SignUpRequest;
use IsoDoss\TerritorialDivision\Http\Requests\Auth\ValidateAccountRequest;
use IsoDoss\TerritorialDivision\Http\Resources\SendApiResponse;
use IsoDoss\TerritorialDivision\Models\PasswordResetToken;
use IsoDoss\TerritorialDivision\Models\User;
use IsoDoss\TerritorialDivision\Models\UserPasswordHistory;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Sign up the user.
     *
     * @param SignUpRequest $request The sign-up request.
     * @return JsonResponse The response.
     *
     * @throws Exception The exception.
     */
    public static function signUp(SignUpRequest $request): JsonResponse
    {
        $user = self::createUser($request);

        return (new SendApiResponse(true, __('messages.auth.sign-up.success'), [], [], $request->validated(), $user->toArray(), 201))->response();
    }

    /**
     * Send the email to validate the user account.
     *
     * @param SendEmailValidateAccountRequest $request The send email validate account request.
     * @return JsonResponse The response.
     */
    public static function sendEmailValidateAccount(SendEmailValidateAccountRequest $request): JsonResponse
    {
        $requestData = $request->validated();
        $user = User::whereEmail($requestData['email'])->whereNull('activated_at')->whereNull('verified_at')->whereNull('deleted_at')->first();
        $passwordResetToken = PasswordResetToken::whereEmail($requestData['email'])->whereRole($user->role)->whereType('VALIDATE-ACCOUNT')->first();

        if (!is_null($passwordResetToken)) {
            $token = $passwordResetToken->token;
        } else {
            $token = Str::random(64);
            PasswordResetToken::create(['email' => $requestData['email'], 'role' => $user->role, 'token' => $token, 'type' => 'VALIDATE-ACCOUNT']);
        }

        // Notification création de compte.
        $sendEmailValidateAccountMailData['title'] = __('messages.auth.send-email-validate-account.mail', ['app-name' => config('app.name')]);
        $sendEmailValidateAccountMailData['message'] = __('messages.auth.send-email-validate-account.mail', ['app-name' => config('app.name')]);
        $sendEmailValidateAccountMailData['view'] = 'mails.auth.sign-up';
        $sendEmailValidateAccountMailData['token'] = $token;
        $sendEmailValidateAccountMailData['validate_account_url'] = $requestData['validate_account_url'] ?? '';
        event(new SendNotificationEvent($user, $sendEmailValidateAccountMailData));

        return (new SendApiResponse(true, __('messages.auth.send-email-validate-account.success'), [], [], $requestData, $user->toArray(), 201))->response();
    }

    /**
     * Validate user account.
     *
     * @param ValidateAccountRequest $request The validate account request.
     * @return JsonResponse The response.
     */
    public static function validateAccount(ValidateAccountRequest $request): JsonResponse
    {
        $requestData = $request->validated();
        $user = User::whereEmail($requestData['email'])->whereRole($requestData['role'])->whereNull('activated_at')->whereNull('verified_at')->whereNull('deleted_at')->first();
        $validateAccountToken = PasswordResetToken::whereEmail($requestData['email'])->whereRole($requestData['role'])->whereToken($requestData['token']);

        if (is_null($validateAccountToken->first()) || $validateAccountToken->first()->email != $user->email) {
            return (new SendApiResponse(false, __('messages.auth.validate-account.invalid-token', ['attribute' => 'token']), [], [], $requestData, [], 404))->response();
        }

        $user->update(['verified_at' => now(), 'activated_at' => now(), 'email_verified_at' => now()]);
        $validateAccountToken->delete();

        // Notification de validation de compte.
        $validateAccountMailData['title'] = __('messages.auth.validate-account.mail', ['app-name' => config('app.name')]);
        $validateAccountMailData['message'] = __('messages.auth.validate-account.mail', ['app-name' => config('app.name')]);
        $validateAccountMailData['view'] = 'mails.auth.validate-account';
        $validateAccountMailData['validate_account_url'] = $requestData['validate_account_url'] ?? '';
        event(new SendNotificationEvent($user, $validateAccountMailData));

        return (new SendApiResponse(true, __('messages.auth.validate-account.success'), [], [], $requestData, $user->toArray(), 201))->response();
    }

    /**
     * Sign in the user.
     *
     * @param SignInRequest $request The sign in request.
     * @return JsonResponse The response.
     *
     * @throws ValidationException The validation exception.
     */
    public static function signIn(SignInRequest $request): JsonResponse
    {
        $request->authenticate();
        $requestData = $request->validated();

        // Notification d'une nouvelle connexion.
        $user = User::find(Auth::user()?->id);
        $dataSignIn['title'] = __('messages.auth.sign-in.mail', ['app-name' => config('app.name')]);
        $dataSignIn['message'] = __('messages.auth.sign-in.mail', ['app-name' => config('app.name')]);
        $dataSignIn['view'] = 'mails.auth.sign-in';
        event(new SendNotificationEvent($user, $dataSignIn));

        $userData = $user->toArray();
        $userData['token'] = $user->createToken('cfp-le-savoir-faire-api-sign-in-token', [$user->role], now()->addMinutes(60 * 24))->plainTextToken;
        //$userData['roles'] = $user->roles;
        //$userData['role'] = $user->defaultRole;
        //$userData['permissions'] = $user->getAllPermissions();
        //Session::put('cfp-le-savoir-faire-api-user-connected', $userData);
        Session::put('cfp-le-savoir-faire-api-sign-in-token', $userData['token']);
        // RDC-ID : Mettre en place une fonction qui check les avertissements.
        $warnings = [];
        if ($user->has_default_password) {
            $warnings[] = __('messages.auth.sign-in.change-default-password');
        }

        return (new SendApiResponse(true, __('messages.auth.sign-in.success'), [], $warnings, $requestData, $userData))->response();
    }

    /**
     * Forgot password.
     *
     * @param ForgotPasswordRequest $request The forgot password request.
     * @return JsonResponse The response.
     */
    public static function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $requestData = $request->validated();
        $user = User::whereEmail($requestData['email'])->whereNotNull('activated_at')->whereNotNull('verified_at')->whereNull('deleted_at')->first();
        $oldForgotPasswordToken = PasswordResetToken::whereEmail($requestData['email'])->whereType('FORGOT-PASSWORD')->first();

        if (!is_null($oldForgotPasswordToken)) {
            $token = $oldForgotPasswordToken->token;
        } else {
            $token = Str::random(64);
            PasswordResetToken::create(['email' => $requestData['email'], 'role' => $user->role, 'token' => $token, 'type' => 'FORGOT-PASSWORD']);
        }

        // Notification de mot de passe oublié.
        $forgotPasswordMailData['title'] = __('messages.auth.forgot-password.mail', ['app-name' => config('app.name')]);
        $forgotPasswordMailData['message'] = __('messages.auth.forgot-password.mail', ['app-name' => config('app.name')]);
        $forgotPasswordMailData['view'] = 'mails.auth.forgot-password';
        $forgotPasswordMailData['token'] = $token;
        $forgotPasswordMailData['reset_password_url'] = $requestData['reset_password_url'] ?? '';
        event(new SendNotificationEvent($user, $forgotPasswordMailData));

        return (new SendApiResponse(true, __('messages.auth.forgot-password.success'), [], [], $requestData, $user->toArray(), 201))->response();
    }

    /**
     * Reset password.
     *
     * @param ResetPasswordRequest $request The reset password request.
     * @return JsonResponse The response.
     */
    public static function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $requestData = $request->validated();
        $user = User::whereEmail($requestData['email'])->whereRole($requestData['role'])->whereNotNull('activated_at')->whereNotNull('verified_at')->whereNull('deleted_at')->first();
        $resetPasswordAccountToken = PasswordResetToken::whereEmail($requestData['email'])->whereRole($requestData['role'])->whereToken($requestData['token'])->whereType('FORGOT-PASSWORD');

        $userPasswordHistories = UserPasswordHistory::where('user_id', $user->id)->get();
        foreach ($userPasswordHistories as $userPasswordHistory) {
            if (Hash::check($requestData['password'], $userPasswordHistory->password)) {
                return (new SendApiResponse(false, __('messages.auth.reset-password.failed'), ['password' => __('messages.auth.reset-password.new-password-already-use'), 'password_confirmation' => __('messages.auth.reset-password.new-password-already-use'), 'new_password' => __('messages.auth.reset-password.new-password-already-use'), 'new_password_confirmation' => __('messages.auth.reset-password.new-password-already-use')], [], $requestData))->response();
            }
        }

        $user->update([
            'password' => Hash::make($requestData['password']),
            'password_updated_at' => now(),
            'has_default_password' => false,
        ]);
        UserPasswordHistory::create(['user_id' => $user->id, 'password' => $user->password]);
        $user->tokens()->delete();
        $resetPasswordAccountToken->delete();

        // Notification de réinitialisation du mot de passe.
        $dataResetPassword['title'] = __('messages.auth.reset-password.mail', ['app-name' => config('app.name')]);
        $dataResetPassword['message'] = __('messages.auth.reset-password.mail', ['app-name' => config('app.name')]);
        $dataResetPassword['view'] = 'mails.auth.reset-password';
        event(new SendNotificationEvent($user, $dataResetPassword));

        return (new SendApiResponse(true, __('messages.auth.reset-password.success'), [], [], ['user' => $user->toArray()]))->response();
    }

    /**
     * Sign out a user.
     *
     * @param Request $request The request.
     * @return JsonResponse The response.
     */
    public static function signOut(Request $request): JsonResponse
    {
        $requestToken = self::getToken($request);
        $currentAccessToken = PersonalAccessToken::findToken($requestToken);
        $currentAccessToken?->delete();

        return (new SendApiResponse(true, __('messages.auth.sign-out.success')))->response();
    }
}
