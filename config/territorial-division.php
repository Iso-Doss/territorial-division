<?php

declare(strict_types=1);

return [
	'table_prefix' => env('TERRITORIAL_DIVISION_TABLE_PREFIX', 'territorial_division_'),
	'route' => [
		'web' => [
			'domain' => env('TERRITORIAL_DIVISION_ROUTE_WEB_DOMAINE', 'territorial-division.web.'),
			'prefix' => env('TERRITORIAL_DIVISION_ROUTE_WEB_PREFIX', 'territorial-division.web.V1'),
			'namespace' => env('TERRITORIAL_DIVISION_ROUTE_WEB_NAMESPACE', 'IsoDoss\TerritorialDivision\Http\Controllers'),
			'middleware' => env('TERRITORIAL_DIVISION_ROUTE_WEB_MIDDLEWARE', 'web'),
		],
		'api' => [
			'domain' => env('TERRITORIAL_DIVISION_ROUTE_WEB_DOMAINE', 'territorial-division.api.'),
			'prefix' => env('TERRITORIAL_DIVISION_ROUTE_WEB_PREFIX', 'territorial-division.api.V1'),
			'namespace' => env('TERRITORIAL_DIVISION_ROUTE_WEB_NAMESPACE', 'IsoDoss\TerritorialDivision\Http\Controllers'),
			'middleware' => env('TERRITORIAL_DIVISION_ROUTE_WEB_MIDDLEWARE', 'api'),
		],
	]
];