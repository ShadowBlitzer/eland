<?

$secure = $app['controllers_factory'];

$secure->host('l.' . $app['overall_domain']);
	//->when('request.isSecure() == true')

$secure->get('/', function(Request $request){
	return 'secured ' . $request->getHost() . ' ' . $request;
});

