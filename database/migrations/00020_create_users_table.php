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
		if (!Schema::hasTable(config('territorial-division.table_prefix') . 'users')) {
			Schema::create(config('territorial-division.table_prefix') . 'users', function (Blueprint $table) {
				$table->uuid('id')->primary()->unique();
				$table->string('email')->nullable()->unique();
				$table->string('password');
				$table->string('name')->nullable();
				$table->enum('user_type', ['physical-person', 'corporation'])->nullable()->default('physical-person');
				$table->string('first_name')->nullable();
				$table->string('last_name')->nullable();
				$table->string('user_name')->nullable();
				$table->string('registration_number')->unique()->nullable();
				$table->string('phone_number')->nullable();
				$table->string('whatsapp_phone_number')->nullable();
				$table->bigInteger('ifu')->nullable();
				$table->string('avatar')->nullable();
				$table->enum('gender', ['male', 'female', 'other'])->nullable();
				$table->date('birthday')->nullable();
				$table->string('birthplace')->nullable();
				$table->string('city')->nullable();
				$table->text('address')->nullable();
				$table->text('website')->nullable();
				$table->boolean('has_default_password')->nullable()->default(0);
				$table->foreignUuid('godfather_id')->nullable()->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
				$table->foreignUuid('country_id')->nullable()->references('id')->on('countries')->cascadeOnUpdate()->cascadeOnDelete();
				$table->timestamp('verified_at')->nullable();
				$table->timestamp('email_verified_at')->nullable();
				$table->timestamp('phone_number_verified_at')->nullable();
				$table->timestamp('activated_at')->nullable();
				$table->rememberToken();
				$table->softDeletes();
				$table->timestamps();
			});

			//DB::statement('ALTER TABLE `users` ADD `number` INT NOT NULL AUTO_INCREMENT AFTER `id`, ADD INDEX (`number`)');
		}
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists(config('territorial-division.table_prefix') . 'users');
	}
};
