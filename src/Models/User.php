<?php

namespace IsoDoss\TerritorialDivision\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
	use HasFactory, HasUuids, LogsActivity, SoftDeletes, HasApiTokens, Notifiable;

	/**
	 * Create a new Eloquent model instance.
	 *
	 * @param array $attributes The attributes.
	 * @return void
	 */
	public function __construct(array $attributes = [])
	{
		parent::__construct($attributes);
		$this->table = config('territorial-division.table_prefix') . parent::getTable();
	}

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'email',
		'password',
		'name',
		'user_type',
		'first_name',
		'last_name',
		'user_name',
		'registration_number',
		'phone_number',
		'whatsapp_phone_number',
		'ifu',
		'avatar',
		'gender',
		'birthday',
		'birthplace',
		'city',
		'address',
		'website',
		'has_default_password',
		'godfather_id',
		'country_id',
		'activated_at',
		'verified_at',
		'password_updated_at',
		'email_verified_at',
		'phone_number_verified_at',
	];

	/**
	 * The attributes that should be hidden for serialization.
	 *
	 * @var array<int, string>
	 */
	protected $hidden = [
		'password',
		'remember_token',
	];

	/**
	 * The attributes that should be cast.
	 *
	 * @var array<string, string>
	 */
	protected $casts = [
		'activated_at' => 'datetime',
		'verified_at' => 'datetime',
		'password_updated_at' => 'datetime',
		'email_verified_at' => 'datetime',
		'phone_number_verified_at' => 'datetime',
		'deleted_at' => 'datetime',
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
	];

	/**
	 * The relations to eager load on every query.
	 *
	 * @var array
	 */
	protected $with = [];

	/**
	 * The accessors to append to the model's array form.
	 *
	 * @var array
	 */
	protected $appends = [];

	/**
	 * Get country.
	 *
	 * @return BelongsTo That belongs to.
	 */
	public function country(): BelongsTo
	{
		return $this->belongsTo(Country::class);
	}

	/**
	 * Get godfather.
	 *
	 * @return BelongsTo That belongs to.
	 */
	public function godfather(): BelongsTo
	{
		return $this->belongsTo(User::class, 'godfather_id');
	}

	/**
	 * Get activity log options.
	 *
	 * @return LogOptions The log options.
	 */
	public function getActivityLogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logOnly(Schema::getColumnListing((new User())->getTable()));
	}
}
