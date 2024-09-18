<?php

namespace IsoDoss\TerritorialDivision\Http\Controllers;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use IsoDoss\TerritorialDivision\Events\UserAccountEvent;
use IsoDoss\TerritorialDivision\Http\Controllers\Api\V1\UserController;
use IsoDoss\TerritorialDivision\Http\Requests\Auth\SignUpRequest;
use IsoDoss\TerritorialDivision\Models\PasswordResetToken;
use IsoDoss\TerritorialDivision\Models\User;
use IsoDoss\TerritorialDivision\Models\UserPasswordHistory;
use IsoDoss\TerritorialDivision\Models\UserRole;
use IsoDoss\TerritorialDivision\Services\ChromePdfService;
use Throwable;

class Controller extends BaseController
{
	use AuthorizesRequests, ValidatesRequests;

	/**
	 * Default message for api error fields.
	 */
	const API_DEFAULT_ERROR_FIELDS_MESSAGE = 'Oups !!! Un ou plusieurs champ(s) sont incorrect(s).';

	/**
	 * Les opérateurs autorisés lors du get filter pour de simple comparaison.
	 */
	const FILTER_COMPARATOR = ['=', '<', '<=', '>', '>=', '<>', '!=', 'LIKE'];

	/**
	 * Les opérateurs autorisés lors du get filter pour des comparaisons avancées nécessitant un tableau.
	 */
	const FILTER_COMPARATOR_WITH_ARRAY = ['IN', 'NOT_IN', 'BETWEEN'];

	/**
	 * Les opérateurs autorisés lors du get filter pour des comparaisons avancées afin de vérifier la présence de valeur ou nom d'un champ.
	 */
	const FILTER_COMPARATOR_WITHOUT_VALUE = ['NULL', 'NOT_NULL'];

	/**
	 * Upload image.
	 *
	 * @param Request $request The request.
	 * @param string $path The path.
	 * @param string $fieldName The field name.
	 * @return bool|string The upload image.
	 */
	public static function uploadImage(Request $request, string $path, string $fieldName = 'image'): bool|string
	{
		///** @var UploadedFile | null $image */
		//$image = $request->validated($fieldName);
		/** @var UploadedFile | null $image */
		$image = $request->file($fieldName);
		if ($image === null || $image->getError()) {
			return false;
		}
		$image->store($path, 'cfp');

		return $image->store($path, 'public');
	}

	/**
	 * Get token.
	 *
	 * @param Request $request The request.
	 * @return array|mixed|string|null $token The token.
	 */
	public static function getToken(Request $request)
	{
		return Session::get('cfp-le-savoir-faire-api-sign-in-token') ?? $request->header('Authorization');
	}

	/**
	 * Send web response.
	 *
	 * @param JsonResponse $response The request.
	 * @param string $route The route name.
	 * @param array $parameters The route parameters.
	 * @param string $successMessage The custom success message.
	 * @return RedirectResponse The redirect response.
	 */
	public function sendWebResponse(JsonResponse $response, string $route, array $parameters = [], string $successMessage = ''): RedirectResponse
	{
		$responseData = json_decode($response->content(), true);

		return redirect()->route($route, $parameters)
			->with(['success' => ($responseData['success']) ? ($successMessage) ?: $responseData['message'] : '', 'warnings' => ($responseData['warnings']) ?: [], 'error' => (!$responseData['success']) ? $responseData['message'] : '', 'input' => $responseData['input']])
			->withErrors($responseData['errors'])
			->withInput($responseData['input']);
	}

	/**
	 * Generate random password.
	 *
	 * @return string $password The password.
	 *
	 * @throws Exception The exception.
	 */
	public static function generateRandomPassword(): string
	{
		// Set random length for password
		$password_length = random_int(8, 16);
		$password = '';
		for ($i = 0; $i < $password_length; $i++) {
			$password .= chr(random_int(32, 126));
		}

		return $password;
	}

	/**
	 * Create user.
	 *
	 * @param SignUpRequest $request The sign-up request.
	 *
	 * @throws Exception The exception.
	 */
	public static function createUser(SignUpRequest $request): User|Model
	{
		$requestData = $request->validated();
		$requestData['registration_number'] = uniqid('registration-');
		$requestData['has_default_password'] = $request->boolean('has_default_password');

		$defaultPassword = '';
		if ($requestData['has_default_password']) {
			$requestData['password'] = $defaultPassword = self::generateRandomPassword();
			$requestData['has_default_password'] = 1;
		}

		$requestData['password'] = Hash::make($requestData['password']);

		$user = User::create($requestData);
		UserPasswordHistory::create(['user_id' => $user->id, 'password' => $requestData['password']]);
		$user->assignRole(UserRole::whereName($requestData['role'])->first());

		if ($user->role === 'customer') {
			try {
				$token = Str::random(64);
				PasswordResetToken::create(['email' => $requestData['email'], 'role' => $requestData['role'], 'token' => $token, 'type' => 'VALIDATE-ACCOUNT']);
			} catch (QueryException $e) {
				Log::error('Create user query exception', ['Create user query exception' => $e]);
				$passwordResetToken = PasswordResetToken::whereEmail($requestData['email'])->whereRole($requestData['role'])->whereType('VALIDATE-ACCOUNT')->first();
				$token = $passwordResetToken->token;
			}
		}

		// Notification création de compte.
		$signUpMailData['title'] = __('messages.auth.sign-up.mail', ['app-name' => config('app.name')]);
		$signUpMailData['message'] = __('messages.auth.sign-up.mail', ['app-name' => config('app.name')]);
		$signUpMailData['view'] = 'mails.auth.sign-up';
		$signUpMailData['token'] = $token ?? '';
		$signUpMailData['validate_account_url'] = $requestData['validate_account_url'] ?? '';
		event(new UserAccountEvent($user, $signUpMailData));

		// Notification mot de passe par défaut.
		if ($user->has_default_password) {
			$signUpDefaultPasswordMailData['title'] = __('messages.auth.sign-up.default-password', ['app-name' => config('app.name')]);
			$signUpDefaultPasswordMailData['message'] = __('messages.auth.sign-up.default-password', ['app-name' => config('app.name')]);
			$signUpDefaultPasswordMailData['view'] = 'mails.auth.sign-up-default-password';
			$signUpDefaultPasswordMailData['default_password'] = $defaultPassword;
			event(new UserAccountEvent($user, $signUpDefaultPasswordMailData));
		}

		return $user;
	}

	/**
	 * Generate student registration number.
	 *
	 * @param string $siteCode The site code.
	 * @param int $studentNumber The student number
	 * @param string|null $year The year.
	 * @return string The student registration number.
	 */
	public static function generateStudentRegistrationNumber(string $siteCode, int $studentNumber, ?string $year = null): string
	{
		$studentNumberCode = '';
		$studentNumberLength = strlen((string)$studentNumber);
		if ($studentNumberLength < 6) {
			$studentNumberCode = str_repeat('0', 6 - $studentNumberLength);
		}
		$year = (is_null($year)) ? now()->year : $year;

		return $siteCode . '-' . $year . '-' . $studentNumberCode . $studentNumber;
	}

	/**
	 * Generate document.
	 *
	 * @param array $data The data.
	 * @param string $documentFileNamePath The document file name path.
	 * @param string $documentFileName The document file name.
	 * @param string $outputPath The output path.
	 * @param string $storageDiskName The storage disk name.
	 * @return array $output The output.
	 *
	 * @throws Throwable The throwable.
	 */
	public static function generateDocument(array $data, string $documentFileNamePath, string $documentFileName, string $outputPath, string $storageDiskName = 'public'): array
	{
		$output = [];
		$outputDir = Storage::disk($storageDiskName)->path('') . $outputPath;
		ob_start();
		echo view($documentFileNamePath, ['data' => $data])->render();
		$html = ob_get_clean();
		if (!is_dir($outputDir)) {
			if (!mkdir_r($outputDir)) {
				$output['status'] = false;
				$output['message'] = __('Échec lors de la création des dossiers.');

				return $output;
			}
		}

		$chromePdf = new ChromePdfService();
		$chromePdf->output($outputDir . $documentFileName);
		$chromePdf->generateFromHtml($html);
		if (file_exists($outputDir . $documentFileName)) {
			chmod($outputDir . $documentFileName, 0777);
		}
		$output['status'] = true;
		$output['message'] = 'Pdf généré avec succès.';
		$output['document_file_name'] = $outputPath . $documentFileName;

		return $output;
	}

	/**
	 * Slugify.
	 *
	 * @param string $text The text
	 * @param string $divider The divider.
	 * @return string The text slugify.
	 */
	public static function slugify(string $text, string $divider = '-'): string
	{
		return slugify($text, $divider);
	}

	/**
	 * Enable or disable model.
	 *
	 * @param Model $model The model.
	 * @param array $requestData The request data.
	 * @return bool $enableOrDisableModel The model is enable or disable.
	 */
	public static function enableOrDisableModel(Model $model, array $requestData): bool
	{
		$enableOrDisableModel = false;
		$oldStatus = (is_null($model->activated_at)) ? 'disable' : 'enable';
		$activatedAt = ($requestData['new_status'] == 'enable') ? now() : null;

		if ($requestData['new_status'] != $oldStatus) {
			$enableOrDisableModel = $model->update(['activated_at' => $activatedAt]);
		}

		return $enableOrDisableModel;
	}

	/**
	 * Enable or disable to do.
	 *
	 * @return string $toDo The to do.
	 */
	public static function enableOrDisableToDo(array $requestData): string
	{
		return ($requestData['new_status'] == 'enable') ? 'activé' : 'désactivé';
	}

	/**
	 * Group action on model's collection.
	 *
	 * @param Builder|EloquentBuilder $builder The query builder or the eloquent builder.
	 * @param array $requestData The request data.
	 * @return string $toDo The to do.
	 */
	public static function groupActionOnModelCollection(Builder|EloquentBuilder $builder, array $requestData): string
	{
		$toDo = '';

		if ($requestData['action'] == 'enable') {
			$toDo = 'activés';
			$builder->update(['activated_at' => now()]);
		} elseif ($requestData['action'] == 'disable') {
			$toDo = 'désactivés';
			$builder->update(['activated_at' => null]);
		} elseif ($requestData['action'] == 'delete') {
			$toDo = 'supprimés';
			$builder->delete();
		}
		//elseif ('import' == $groupActionData['action']) {
		//    $toDo = 'importés';
		//} elseif ('export' == $groupActionData['action']) {
		//    $toDo = 'exportés';
		//}

		return $toDo;
	}

	/**
	 * Get global filters.
	 *
	 * @param string $model The model.
	 * @param array $requestData The request data.
	 * @return LengthAwarePaginator The Length aware paginator.
	 */
	public function getGlobalFilters(string $model, array $requestData = []): LengthAwarePaginator
	{
		$outputColumns = self::getOutputColumns($model, $requestData);
		$requestData = self::addOrderByKeyOnRequestDataCollection($model, $requestData);

		$eloquentBuilder = $model::when(!empty($requestData['with']), function ($query) use ($model, $requestData) {
			$requestData = self::filterWithInRequestDataCollection($model, $requestData);

			return $query->with($requestData['with']);
		});

		if (!empty($requestData['columns'])) {
			foreach ($requestData['columns'] as $column => $value) {
				if (Schema::hasColumn((new $model())->getTable(), $column)) {
					$eloquentBuilder->when(!empty($value), function ($query) use ($column, $value) {
						if (in_array($value['operator'], self::FILTER_COMPARATOR)) {
							return $query->where($column, $value['operator'], ($value['operator'] == 'LIKE') ? '%' . $value['value'] . '%' : $value['value']);
						} elseif (in_array($value['operator'], self::FILTER_COMPARATOR_WITH_ARRAY)) {
							if ($value['operator'] == 'IN') {
								return $query->whereIn($column, $value);
							} elseif ($value['operator'] == 'NOT_IN') {
								return $query->whereNotIn($column, $value);
							} elseif ($value['operator'] == 'BETWEEN') {
								return $query->whereBetween($column, $value);
							}
						} elseif (in_array($value['operator'], self::FILTER_COMPARATOR_WITHOUT_VALUE)) {
							if ($value['operator'] == 'NULL') {
								return $query->whereNull($column);
							} elseif ($value['operator'] == 'NOT_NULL') {
								return $query->whereNotNull($column);
							}
						}

						return $query;
					});
				}
			}
		}

		return self::filterCollectionsByStatus($eloquentBuilder, $requestData)
			->orderBy($requestData['order_by']['column'], $requestData['order_by']['direction'])
			->paginate($requestData['number_per_page'] ?? 10, $outputColumns ?: ['*'], 'page', $requestData['page'] ?? 1);
	}

	/**
	 * Get output columns.
	 *
	 * @param string $model The model.
	 * @param array $requestData The request data.
	 * @return string[] The output columns.
	 */
	public function getOutputColumns(string $model, array $requestData = []): array
	{
		$outputColumns = ['*'];
		if (!empty($requestData['output_columns'])) {
			$tableColumns = Schema::getColumnListing((new $model())->getTable());
			foreach ($requestData['output_columns'] as $key => $value) {
				if (!in_array($value, $tableColumns)) {
					unset($requestData['output_columns'][$key]);
				}
			}
			$outputColumns = $requestData['output_columns'];
		}

		return $outputColumns;
	}

	/**
	 * Filter collections by status.
	 *
	 * @param EloquentBuilder $collections The collections.
	 * @param array $requestData The request data.
	 * @return EloquentBuilder $collections The collection's filter by status.
	 */
	public static function filterCollectionsByStatus(EloquentBuilder $collections, array $requestData): EloquentBuilder
	{
		return $collections->when($requestData['status'] ?? '', function ($query) use ($requestData) {
			if (in_array($requestData['status'], ['on_hold', 'open', 'closed_waiting_for_money', 'closed'])) {
				return $query->whereStatus($requestData['status']);
			} elseif ($requestData['status'] === 'enable') {
				return $query->whereNotNull('activated_at');
			} elseif ($requestData['status'] === 'disable') {
				return $query->whereNull('activated_at');
			} elseif ($requestData['status'] === 'delete') {
				return $query->onlyTrashed();
			} else {
				return $query;
			}
		});
	}

	/**
	 * Add order by key on request data collection.
	 *
	 * @param string $table The table name.
	 * @param array $requestData The request data.
	 * @return array The request data.
	 */
	public static function addOrderByKeyOnRequestDataCollection(string $table, array $requestData = []): array
	{
		if (!isset($requestData['order_by']['column']) || is_null($requestData['order_by']['column']) || !Schema::hasColumn($table, $requestData['order_by']['column'])) {
			$requestData['order_by']['column'] = 'id';
		}

		if (!isset($requestData['order_by']['direction']) || (!in_array($requestData['order_by']['direction'], ['asc', 'desc']))) {
			$requestData['order_by']['direction'] = 'desc';
		}

		return $requestData;
	}

	/**
	 * Filter with in request data collection
	 *
	 * @param array $requestData The request data.
	 * @return array The request data.
	 */
	public static function filterWithInRequestDataCollection(string $model, array $requestData = []): array
	{
		foreach ($requestData['with'] as $key => $with) {
			if (is_array($with)) {
				if (!(new $model())->isRelation($key)) {
					unset($requestData['with'][$key]);
				}
			} elseif (is_string($with)) {
				if (str_contains($with, '.')) {
					if (!(new $model())->isRelation(explode('.', $with)[0])) {
						unset($requestData['with'][$key]);
					}
				} else {
					if (!(new $model())->isRelation($with)) {
						unset($requestData['with'][$key]);
					}
				}
			}
		}

		return $requestData;
	}

	/**
	 * Format gender name.
	 *
	 * @param array $data The data.
	 * @return array $data The data.
	 */
	public static function formatGender(array $data): array
	{
		if (!empty($data['gender']) && $data['gender'] == 'male') {
			$data['gender'] = 'Masculin';
		} elseif (!empty($data['gender']) && $data['gender'] == 'female') {
			$data['gender'] = 'Féminin';
		} elseif (!empty($data['gender']) && $data['gender'] == 'other') {
			$data['gender'] = 'Autre';
		}

		return $data;
	}

	/**
	 * Format date reporting.
	 *
	 * @param array $data The data.
	 * @param string $dateFieldName The date field name.
	 * @return array $data The data.
	 */
	public static function formatDateReporting(array $data, string $dateFieldName): array
	{
		if (!empty($data[$dateFieldName])) {
			$data[$dateFieldName] = Carbon::parse($data[$dateFieldName])->format('d/m/Y');
		}

		return $data;
	}

	public static function startEndDate(array $requestData = []): array
	{
		if (!empty($requestData['start_date']) && !empty($requestData['end_date'])) {
			$requestData['start_date'] = $requestData['start_date'] . ' 00:00:00';
			$requestData['end_date'] = $requestData['end_date'] . ' 23:59:59';
		} elseif (empty($requestData['start_date']) && !empty($requestData['end_date'])) {
			$requestData['start_date'] = $requestData['end_date'] . ' 00:00:00';
			$requestData['end_date'] = $requestData['end_date'] . ' 23:59:59';
		} elseif (!empty($requestData['start_date']) && empty($requestData['end_date'])) {
			$requestData['end_date'] = $requestData['start_date'] . ' 23:59:59';
			$requestData['start_date'] = $requestData['start_date'] . ' 00:00:00';
		} else {
			$requestData['start_date'] = null;
			$requestData['end_date'] = null;
		}

		return $requestData;
	}
}
