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
		if (!Schema::hasTable(config('territorial-division.table_prefix') . 'country_names')) {
			Schema::create(config('territorial-division.table_prefix') . 'country_names', function (Blueprint $table) {
				$table->uuid('id')->primary()->unique();
				$table->string('name');
				$table->string('description')->nullable();
				$table->foreignUuid('country_id')->references('id')->on('countries')->cascadeOnUpdate()->cascadeOnDelete();
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
		Schema::dropIfExists(config('territorial-division.table_prefix') . 'country_names');
	}
};
