<?php

namespace App\Twig;

use App\Service\UserSimpleCache;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class UserFormatExtension extends AbstractExtension
{
	private $userSimpleCache;
	private $local;
	private $format = [];

	public function __construct(
		UserSimpleCache $userSimpleCache,
		UrlGeneratorInterface $urlGenerator
	)
	{
		$this->userSimpleCache = $userSimpleCache;
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

	public function get(array $context, int $id):string
	{
		if (!isset($this->local[$context['schema']]))
		{
			$this->local[$context['schema']] = $this->userSimpleCache->get($context['schema']);
		}

		if (!isset($this->local[$this->schema][$id]))
		{
			return '';
		}

		$out = '<a href="';
		$out .= $this->urlGenerator->generate('user_show', [
			'user'		=> $id,
			'access'	=> $context['access'],
			'schema'	=> $context['schema'],
			'user_type'	=> $this->local[$context['schema']][$id][0],
		]);
		$out .= '">';
		$out .= $this->local[$context['schema']][$id][1];
		$out .= '</a>';

		return $out;
	}
}
