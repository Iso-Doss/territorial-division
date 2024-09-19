<?php

namespace IsoDoss\TerritorialDivision\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin IdeHelperLanguage
 */
class Language extends Model
{
	use HasFactory, HasUuids, LogsActivity, SoftDeletes;

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
		'name',
		'code',
		'description',
		'activated_at',
	];

	/**
	 * The attributes that should be hidden for serialization.
	 *
	 * @var array<int, string>
	 */
	protected $hidden = [];

	/**
	 * The attributes that should be cast.
	 *
	 * @var array<string, string>
	 */
	protected $casts = [
		'activated_at' => 'datetime',
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
	 * Get activity log options.
	 *
	 * @return LogOptions The log options.
	 */
	public function getActivityLogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logOnly(Schema::getColumnListing((new Language())->getTable()));
	}
}
