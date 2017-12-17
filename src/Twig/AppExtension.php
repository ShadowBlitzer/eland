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
			new TwigFilter('date_format', 'App\Twig\DateFormatExtension::get', [
				'needs_context'		=> true,
			]),
			new TwigFilter('user_format', 'App\Twig\UserFormatExtension::get', [
				'needs_context'		=> true,
			]),
			new TwigFilter('view', 'App\Twig\ViewExtension::get', [
				'needs_context'		=> true,
			]),           
        ];
    }

    public function getFunctions()
    {
        return [
//            new TwigFunction('distance_p', 'App\Twig\DistanceExtension::format_p'),
			new TwigFunction('datepicker_format', 'App\Twig\DatepickerExtension::getFormat',[
				'needs_context'		=> true,
			]),
            new TwigFunction('datepicker_placeholder', 'App\Twig\DatepickerExtension::getPlaceholder', [
				'needs_context'		=> true,
			]),
            new TwigFunction('config', 'App\Twig\ConfigExtension::get', [
				'needs_context'		=> true,
			]),
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
