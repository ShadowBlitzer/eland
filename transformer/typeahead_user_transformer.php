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
    * from db to input (id to code + username)
    */
    public function transform($id)
    {
        if (null === $id) 
        {
            return '';
        }

        $user = $this->db->fetchAssoc('select letscode, name 
            from ' . $this->schema . '.users
             where id = ?', [$id]);

        if (!$user)
        {
            return '';
        }  

        return $user['letscode'] . ' ' . $user['code'];
    }

    /*
    * from input to db (code to id)
    */
    public function reverseTransform($code)
    {
        if (!$code) 
        {
            return;
        }

        list($code) = explode(' ', $code);

        $id = $this->db->fetchColumn('select id 
            from ' . $this->schema . '.users 
            where letscode = ?', [$code]);

        if (!$id)
        {
            return;
        }

        return $id;
    }
}