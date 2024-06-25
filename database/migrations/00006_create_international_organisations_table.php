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
		if (!Schema::hasTable(config('territorial-division.table_prefix') . 'international_organisations')) {
			Schema::create(config('territorial-division.table_prefix') . 'international_organisations', function (Blueprint $table) {
				$table->uuid('id')->primary()->unique();
				$table->string('name')->unique();
				$table->string('code')->unique();
				$table->string('description')->nullable();
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
		Schema::dropIfExists(config('territorial-division.table_prefix') . 'international_organisations');
	}
};
