<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
			new TwigFilter('underline', [$this, 'underline']),
			new TwigFilter('replace_when_zero', [$this, 'replaceWhenZero']),
			new TwigFilter('date_format', 'App\Twig\DateFormatExtension::get'),
			new TwigFilter('web_date', 'App\Twig\WebDateExtension::get'),
			new TwigFilter('web_user', 'twig\\web_user::get'),
			new TwigFilter('access', 'twig\\access::get'),
			new TwigFilter('view', 'twig\\view::get'),           
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('distance_p', 'App\Twig\DistanceExtension::format_p'),
            new TwigFunction('datepicker_format', 'App\Twig\DatepickerExtension::get_format'),
            new TwigFunction('datepicker_placeholder', 'App\Twig\DatepickerExtension::get_placeholder'),
            new TwigFunction('config', 'App\Twig\ConfigExtension::get'),
        ];
    }

	public function underline(string $input, string $char = '-')
	{
		$len = strlen($input);
		return $input . "\r\n" . str_repeat($char, $len);
	}

	public function replaceWhenZero(int $input, $replace = null)
	{
		return $input === 0 ? $replace : $input; 
	}
}
