<?php

namespace App\Twig;

use Symfony\Component\Translation\TranslatorInterface;
use App\Twig\DateFormatExtension;

class DatepickerExtension
{
	private $dateFormatExtension;
	private $translator;

	private $formatSearch = ['%e', '%d', '%m', '%Y', '%b', '%B', '%a', '%A'];
	private $formatReplace = ['d', 'dd', 'mm', 'yyyy', 'M', 'MM', 'D', 'DD'];
	private $placeholderReplace = [];
	private $placeholder;

	public function __construct(
		DateFormatExtension $dateFormatExtension,
		TranslatorInterface $translator	
	)
	{
		$this->dateFormatExtension = $dateFormatExtension;
		$this->translator = $translator;	
	}

	public function getPlaceholder(array $context):string
	{
		$format = $this->dateFormatExtension->getFormat($context, 'day');

		if (!count($this->placeholderReplace))
		{
			foreach($this->formatSearch as $s)
			{
				$this->placeholderReplace[] = $this->translator->trans('datepicker.placeholder.' . ltrim($s, '%'));
			}
		}

		if (!isset($this->placeholder))
		{
			$this->placeholder = str_replace($this->formatSearch, $this->placeholderReplace, $format);
		}

		return $this->placeholder;
	}

	public function getFormat(array $context):string
	{
		$format = $this->DateFormatExtension->getFormat($context, 'day');

		return str_replace($this->formatSearch, $this->formatReplace, $format);
	}
}
