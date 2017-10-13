<?php

// interlets, user & admin
$app->mount('/{_locale}/{schema}/{access}/ads', new provider\ad_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/users', new provider\user_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/images', new provider\image_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/transactions', new provider\transaction_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/news', new provider\news_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/docs', new provider\doc_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/forum', new provider\forum_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/elas', new provider\elas_controller_provider());

// user & admin
$app->mount('/{_locale}/{schema}/{access}/notifications', new provider\notification_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/support', new provider\support_controller_provider());

// admin
$app->mount('/{_locale}/{schema}/{access}/status', new provider\status_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/pages', new provider\page_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/permissions', new provider\permission_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/categories', new provider\category_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/custom-fields', new provider\custom_field_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/contact-types', new provider\type_contact_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/contact-details', new provider\contact_detail_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/config', new provider\config_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/export', new provider\export_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/auto-min-limit', new provider\auto_min_limit_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/mass-transaction', new provider\mass_transaction_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/periodic-charge', new provider\periodic_charge_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/logs', new provider\log_controller_provider());

// public 
$app->mount('/{_locale}/{schema}/login', new provider\login_controller_provider());
$app->mount('/{_locale}/{schema}/logout', new provider\logout_controller_provider());
$app->mount('/{_locale}/{schema}/password-reset', new provider\password_reset_controller_provider());
$app->mount('/{_locale}/{schema}/register', new provider\register_controller_provider());
$app->mount('/{_locale}/{schema}/contact', new provider\contact_controller_provider());
$app->mount('/{_locale}/{schema}', new provider\public_page_controller_provider());

// main 
$app->mount('{_locale}/hosting-request', new provider\hosting_request_controller_provider());
$app->mount('{_locale}', new provider\main_controller_provider());
$app->mount('/monitor', new provider\monitor_controller_provider());

$app->get('/', function() use ($app){
	return $app->redirect($app->path('main_index'));
});

$app['controllers']->assert('schema', '[a-z][a-z0-9]*')
	->assert('_locale', '[a-z]{2}')
	->assert('access', '[giua]');
