<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\TokenCache;

class TokenUrl
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
		TokenCache $tokenCache
	)
	{
		$this->requestStack = $requestStack;
		$this->urlGenerator = $urlGenerator;
		$this->tokenCache = $tokenCache;

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

		$token = $this->tokenCache->gen($data);

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

		$data = $this->tokenCache->get($token);

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

