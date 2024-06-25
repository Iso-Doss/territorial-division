<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		if (!Schema::hasTable(config('territorial-division.table_prefix') . 'countries')) {
			Schema::create(config('territorial-division.table_prefix') . 'countries', function (Blueprint $table) {
				$table->uuid('id')->primary()->unique();
				$table->string('name')->unique();
				$table->string('code')->unique();
				$table->string('phone_code')->unique();
				$table->string('description')->nullable();
				$table->string('devise')->nullable();
				$table->string('anthem_name')->nullable(); // Hymne national
				$table->string('national_holiday_name')->nullable();
				$table->date('national_holiday_date')->nullable();
				$table->string('republic_president_name')->nullable();
				$table->string('republic_vice_president_name')->nullable();
				$table->string('parliament_name')->nullable();
				$table->string('official_language')->nullable();
				$table->string('political_capital_name')->nullable();
				$table->string('economic_capital_name')->nullable();
				$table->string('largest_city_name')->nullable();
				$table->bigInteger('total_area')->nullable();
				$table->bigInteger('water_area')->nullable();
				$table->string('time_zone')->nullable();
				$table->string('nationality')->nullable();
				$table->bigInteger('total_population')->nullable();
				$table->bigInteger('density')->nullable();
				$table->string('currency')->nullable();
				$table->string('internet_domain')->nullable();
				$table->timestamp('activated_at')->nullable()->useCurrent();
				$table->softDeletes();
				$table->timestamps();
			});
		}
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists(config('territorial-division.table_prefix') . 'countries');
	}
};
