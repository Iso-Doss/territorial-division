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
		if (!Schema::hasTable(config('territorial-division.table_prefix') . 'neighborhoods')) {
			Schema::create(config('territorial-division.table_prefix') . 'neighborhoods', function (Blueprint $table) {
				$table->uuid('id')->primary()->unique();
				$table->string('name');
				$table->string('code');
				$table->string('description')->nullable();
				$table->string('total_population')->nullable();
				$table->foreignUuid('section_id')->references('id')->on('sections')->cascadeOnUpdate()->cascadeOnDelete();
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
		Schema::dropIfExists(config('territorial-division.table_prefix') . 'neighborhoods');
	}
};
