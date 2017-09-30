<?php
namespace transformer;

use Doctrine\DBAL\Connection as db;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class typeahead_user_transformer implements DataTransformerInterface
{
    private $db;
    private $schema;

    public function __construct(db $db, string $schema)
    {
        $this->db = $db;
        $this->schema = $schema;
    }

    /*
    * add username to code 
    */
    public function transform($code)
    {
        if (null === $code) 
        {
            return '';
        }

        $username = $this->db->selectColumn('select name 
            from ' . $schema . '.users 
            where letscode = ?', [$code]);

        return $code . ($username ? ' ' . $username : '');
    }

    /*
    * remove username from code
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