<?php declare(strict_types=1);

namespace service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use service\token_cache;

class token_url
{
	private $requestStack;
	private $urlGenerator;
	private $token_cache;

	private $request;
	private $locale;
	private $schema;

	public function __construct(
		RequestStack $requestStack, 
		UrlGenerator $urlGenerator,	
		token_cache $token_cache
	)
	{
		$this->requestStack = $requestStack;
		$this->urlGenerator = $urlGenerator;
		$this->token_cache = $token_cache;

		$this->request = $this->requestStack->getCurrentRequest();
		$this->schema = $this->request->attributes->get('schema');
		$this->locale = $this->request->getLocale();
	}

	public function gen(string $route, array $data):string
	{
		$data = array_merge($data, [
			'ip'		=> $this->request->getClientIp(),
			'path'		=> $this->request->getPathInfo(),
			'secure'	=> $this->request->isSecure(),
			'host'		=> $this->request->getHost(),
			'agent'		=> $this->request->headers->get('User-Agent'),
			'route'		=> $route,
		]);

		if (isset($this->schema))
		{
			$data['schema'] = $this->schema;
		}

		if (isset($this->locale))
		{
			$data['locale'] = $this->locale;
		}

		$token = $this->token_cache->gen($data);

		return $this->urlGenerator->generate($route, [
				'_locale' 	=> $this->locale,
				'schema'	=> $this->schema,
				'token'		=> $token, 
			], UrlGeneratorInterface::ABSOLUTE_URL);
	}

	public function get():array
	{
		$token = $this->request->get('token');

		if (!isset($token))
		{
			return [];
		}

		$data = $this->token_cache->get($token);

		if (!isset($data['locale'])
			|| !isset($data['ip'])
			|| !isset($data['route']))
		{
			return [];
		}

		$path = $this->urlGenerator->generate($data['route'], [
			'_locale'	=> $data['locale'],
			'schema'	=> $data['schema'] ?? null,
			'token'		=> $token,
		]);

		if ($path !== $this->request->getPathInfo())
		{
			return [];
		}

		return $data;
	}
}

