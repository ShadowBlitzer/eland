<?php
namespace transformer;

use Doctrine\DBAL\Connection as db;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class typeahead_user_transformer implements DataTransformerInterface
{
    public function __construct()
    {
    }

    /*
    * from db to input
    */
    public function transform($code)
    {
        if (null === $code) 
        {
            return '';
        }

        return $code;
    }

    /*
    * from input to db (remove username)
    */
    public function reverseTransform($code)
    {
        if (!$code) 
        {
            return;
        }

        list($code) = explode(' ', $code);

        return $code;
    }
}