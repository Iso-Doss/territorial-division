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
		if (!Schema::hasTable(config('territorial-division.table_prefix') . 'password_reset_tokens')) {
			Schema::create(config('territorial-division.table_prefix') . 'password_reset_tokens', function (Blueprint $table) {
				$table->uuid('id')->primary()->unique();
				$table->string('email');
				$table->string('new_email')->nullable();
				$table->enum('type', ['validate-account', 'forgot-password']);
				$table->string('token');
				$table->unique(['email', 'role', 'type'], 'password_reset_tokens_unique_key');
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
		Schema::dropIfExists(config('territorial-division.table_prefix') . 'password_reset_tokens');
	}
};
