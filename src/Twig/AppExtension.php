<?php declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
			new TwigFilter('underline', [$this, 'underline']),
			new TwigFilter('replace_when_zero', [$this, 'replaceWhenZero']),        
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
