<?php

namespace IsoDoss\TerritorialDivision;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * TerritorialD] division service provider.
 */
class TerritorialDivisionServiceProvider extends ServiceProvider
{
	/**
	 * Register any package services.
	 *
	 * @return void
	 */
	public function register(): void
	{
		//
	}

	/**
	 * Bootstrap any package services.
	 *
	 * @return void
	 */
	public function boot(): void
	{
		//$this->registerCommands();
		//$this->registerEvents();
		//$this->registerResources();
		//$this->offerPublishing();
		$this->registerConfigs();
		$this->registerMigrations();
		$this->registerRoutes();
	}

	/**
	 * Register territorial division config files.
	 *
	 * @return void
	 */
	protected function registerConfigs(): void
	{
		$this->publishes([
			__DIR__ . '/../config/territorial-division.php' => config_path('territorial-division.php'),
		], 'territorial-division-configs');
	}

	/**
	 * Register territorial division migration files.
	 *
	 * @return void
	 */
	protected function registerMigrations(): void
	{
		$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

		$this->publishes([
			__DIR__.'/../database/migrations' => database_path('migrations')
		], 'territorial-division-migrations');
	}

	/**
	 * Register territorial division route files.
	 *
	 * @return void
	 */
	protected function registerRoutes(): void
	{
		Route::group([
			'domain' => config('territorial-division.route.web.domain'),
			'prefix' => config('territorial-division.route.web.prefix'),
			'namespace' => config('territorial-division.route.web.namespace'),
			'middleware' => config('territorial-division.route.web.middleware'),
		], function () {
			$this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
		});

		Route::group([
			'domain' => config('territorial-division.route.api.domain'),
			'prefix' => config('territorial-division.route.api.prefix'),
			'namespace' => config('territorial-division.route.api.namespace'),
			'middleware' => config('territorial-division.route.api.middleware'),
		], function () {
			$this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
		});
	}
}
