<?php
namespace transformer;

use AppBundle\Entity\Issue;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class datepicker_transformer implements DataTransformerInterface
{
    private $schema;

    public function __construct(string $schema)
    {
        $this->schema = $schema;
    }

    public function transform($issue)
    {
        if (null === $issue) 
        {
            return '';
        }

        return $issue;
    }

    public function reverseTransform($issueNumber)
    {
        // no issue number? It's optional, so that's ok
        if (!$issueNumber) {
            return;
        }

        $issue = $this->em
            ->getRepository(Issue::class)
            // query for the issue with this id
            ->find($issueNumber)
        ;

        if (null === $issue) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(sprintf(
                'An issue with number "%s" does not exist!',
                $issueNumber
            ));
        }

        return $issue;
    }
}