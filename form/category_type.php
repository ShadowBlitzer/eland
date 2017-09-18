<?php

namespace form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\Connection as db;
use Symfony\Component\Translation\TranslatorInterface;

class category_type extends AbstractType
{	
    private $db;
    private $schema;
    private $translator;

    public function __construct(
        db $db, 
        string $schema, 
        TranslatorInterface $translator
    )
	{
		$this->db = $db;
        $this->schema = $schema;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $parent_categories = [
            '-- ' . $this->translator->trans('category_add.main_category') . ' --' => 0,
        ];

        $rs = $this->db->prepare('select id, name 
            from ' . $this->schema . '.categories 
            where leafnote = 0 
            order by name asc');

        $rs->execute();

        while ($row = $rs->fetch())
        {
            $parent_categories[$row['name']] = $row['id'];
        }

        $builder->add('name', TextType::class, [			
            'constraints' 	=> [
                new Assert\Length(['max' => 40, 'min' => 1]),
            ],
                'attr'	=> [
                    'maxlength'	=> 40,
                ],
            ])

            ->add('id_parent', ChoiceType::class, [
                'choices'  					=> $parent_categories,
                'choice_translation_domain' => false,
            ])

            ->add('submit', SubmitType::class);
    }
}