<?php

namespace App\Twig;

use App\Service\UserSimpleCache;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class UserFormatExtension extends AbstractExtension
{
	private $userSimpleCache;
	private $schema;
	private $access;
	private $local;

	private $format = [];

	public function __construct(
		UserSimpleCache $userSimpleCache,
		RequestStack $request_stack,
		UrlGenerator $urlGenerator
	)
	{
		$this->userSimpleCache = $userSimpleCache;
		$request = $request_stack->getCurrentRequest();
		$this->schema = $request->attributes->get('schema');
		$this->access = $request->attributes->get('access');
		$this->urlGenerator = $urlGenerator;	
	}

    public function getFilters()
    {
        return [
			new TwigFilter('user_format', [$this, 'get'], [
				'needs_context'		=> true,
			]),        
        ];
    }

	public function get(int $id):string
	{
		if (!isset($this->local[$this->schema]))
		{
			$this->local[$this->schema] = $this->userSimpleCache->get($this->schema);
		}

		if (!isset($this->local[$this->schema][$id]))
		{
			return '';
		}

		$out = '<a href="';
		$out .= $this->urlGenerator->generate('user_show', [
			'user'		=> $id,
			'access'	=> $this->access,
			'schema'	=> $this->schema,
			'user_type'	=> $this->local[$this->schema][$id][0],
		]);
		$out .= '">';
		$out .= $this->local[$this->schema][$id][1];
		$out .= '</a>';

		return $out;
	}
}
