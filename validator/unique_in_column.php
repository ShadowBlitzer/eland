<?php

namespace validator;

use Doctrine\DBAL\Connection as db;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use validator\unique_in_column_validator;

class unique_in_column extends Constraint
{
    public $message = 'validation.unique_in_column';
    public $db;
    public $table;
    public $column;
    public $schema;
    public $ignore;

    public function __construct($options = null)
    {
        parent::__construct($options);

        if ($this->db === null)
        {
            throw new MissingOptionsException(
                sprintf('The option "db" is mandatory 
                for constraint %s', __CLASS__));
        }

        if (!$this->db instanceof db)
        {
            throw new InvalidArgumentException(
                sprintf('The option "db" must be an instance of 
                Doctrine\DBAL\Connection for constraint %s', __CLASS__));
        }

        if ($this->table === null)
        {
            throw new MissingOptionsException(
                sprintf('The option "table" is mandatory 
                for constraint %s', __CLASS__));
        }

        if (!is_string($this->table) || $this->table === '')
        {
            throw new InvalidArgumentException(
                sprintf('The option "table" must be a valid 
                string for constraint %s', __CLASS__));
        }

        if ($this->column === null)
        {
            throw new MissingOptionsException(
                sprintf('The option "column" is mandatory 
                for constraint %s', __CLASS__));
        }

        if (!is_string($this->column) || $this->column === '')
        {
            throw new InvalidArgumentException(
                sprintf('The option "column" must be a valid 
                string for constraint %s', __CLASS__));
        }

        if ($this->schema === null)
        {
            throw new MissingOptionsException(
                sprintf('The option "schema" is mandatory 
                for constraint %s', __CLASS__));
        }

        if (!is_string($this->schema) || $this->schema === '')
        {
            throw new InvalidArgumentException(
                sprintf('The option "schema" must be a valid 
                string for constraint %s', __CLASS__));
        }    

        if (isset($this->ignore) && !is_array($this->ignore))
        {
            throw new InvalidArgumentException(
                sprintf('The option "igore" must be a valid 
                array when set for constraint %s', __CLASS__));
        }   
    }

    public function validatedBy()
    {
        return unique_in_column_validator::class;
    }
}