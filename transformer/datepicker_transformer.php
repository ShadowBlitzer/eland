<?php
namespace transformer;

use AppBundle\Entity\Issue;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use twig\web_date;

class datepicker_transformer implements DataTransformerInterface
{
    private $web_date;

    public function __construct(web_date $web_date)
    {
        $this->web_date = $web_date;
    }

    public function transform($date)
    {
        if (null === $date) 
        {
            return '';
        }

        return $this->web_date->get($date, 'day');
    }

    public function reverseTransform($input_date)
    {
        if (!$input_date) 
        {
            return;
        }

        $parsed = strptime($input_date, $this->web_date->get_format('day'));

        if ($parsed === false)
        {
            throw new TransformationFailedException(sprintf(
                'User input "%s" could not be parsed to a date',
                $input_date
            ));            
        }

        $year = $parsed['tm_year'] + 1900;
        $month = $parsed['tm_mon'] + 1;
        $day = $parsed['tm_mday'];
        $hour = $parsed['tm_hour'];
        $min = $parsed['tm_min'];
        $sec = $parsed['tm_sec'];

        return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $min, $sec);
    }
}