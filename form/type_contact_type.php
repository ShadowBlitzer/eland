<?php

namespace form;

use Doctrine\DBAL\Connection as db;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;
use validator\unique_in_column;

class type_contact_type extends AbstractType
{
    private $db;
    private $schema;

    public function __construct(db $db, string $schema)
    {
        $this->db = $db;
        $this->schema = $schema;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('abbrev', TextType::class, [			
            'constraints' 	=> [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 10, 'min' => 1]),
                new unique_in_column([
                    'db'        => $this->db,
                    'schema'    => $this->schema,
                    'table'     => 'type_contact',
                    'column'    => 'abbrev',
                    'ignore'    => $options['ignore'],
                ]),
            ],
                'attr'	=> [
                    'maxlength'	=> 10,
                ],
            ])

            ->add('name', TextType::class, [			
            'constraints' 	=> [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 20, 'min' => 1]),
            ],
                'attr'	=> [
                    'maxlength'	=> 20,
                ],
            ])    

            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'blocked_ary'   => [],
            'ignore'        => null,
        ]);
    }
}