<?php

$public = $app['controllers_factory'];

$public->host('x.' . $app['overall_domain']);

$public->get('/', function(){
	return 'public';
});

return $public;