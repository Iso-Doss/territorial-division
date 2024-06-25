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
		if (!Schema::hasTable(config('territorial-division.table_prefix') . 'country_international_organisation')) {
			Schema::create(config('territorial-division.table_prefix') . 'country_international_organisation', function (Blueprint $table) {
				$table->uuid('id')->primary()->unique();
				$table->foreignUuid('country_id')->references('id')->on('countries')->cascadeOnUpdate()->cascadeOnDelete();
				$table->foreignUuid('io_id')->references('id')->on('international_organisations')->cascadeOnDelete()->cascadeOnUpdate();
				$table->timestamp('activated_at')->nullable()->useCurrent();
				$table->softDeletes();
				$table->timestamps();
				//$table->primary(['country_id', 'io_id']);
			});
		}
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists(config('territorial-division.table_prefix') . 'country_international_organisation');
	}
};
