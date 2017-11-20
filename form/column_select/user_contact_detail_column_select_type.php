<?php
namespace form\column_select;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use repository\type_contact_repository;

class user_contact_detail_column_select_type extends AbstractType
{
    private $type_contact_repository;
    private $schema;

    public function __construct(type_contact_repository $type_contact_repository, string $schema)
    {
        $this->type_contact_repository = $type_contact_repository;
        $this->schema = $schema;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $type_contact_ary = $this->type_contact_repository->get_all_abbrev($this->schema);

        foreach ($type_contact_ary as $id => $abbrev)
        {
            $builder->add($abbrev, CheckboxType::class, [
                'required'      => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }
}