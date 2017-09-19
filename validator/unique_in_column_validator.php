<?php

namespace validator;

use Doctrine\DBAL\Connection as db;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class unique_in_column_validator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $query = 'select *
            from ' . $constraint->schema . '.' . $constraint->table . ' 
            where ' . $constraint->column . ' =  ?';

        $params = [$value];

        if (isset($constraint->ignore)
            && is_array($constraint->ignore)
            && count($constraint->ignore))
        {
            foreach ($constraint->ignore as $column => $ignore_value)
            {
                $query .= ' and ' . $column . ' <> ?';
                $params[] = $ignore_value;
            }
        }

        if (isset($constraint->filter)
            && is_array($constraint->filter)
            && count($constraint->filter))
        {
            foreach ($constraint->filter as $column => $filter_value)
            {
                $query .= ' and ' . $column . ' = ?';
                $params[] = $filter_value;
            }
        }

        if ($constraint->db->fetchColumn($query, $params))
        {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%value%', $value)
                ->setTranslationDomain('messages')
                ->addViolation();            
        }
    }
}