<?php
namespace App\Form\ColumnSelect;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use App\Repository\TypeContactRepository;

class UserContactDetailColumnSelectType extends AbstractType
{
    private $typeContactRepository;
    private $schema;

    public function __construct(TypeContactRepository $typeContactRepository, string $schema)
    {
        $this->typeContactRepository = $typeContactRepository;
        $this->schema = $schema;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $typeContactAry = $this->typeContactRepository->getAllAbbrev($this->schema);

        foreach ($typeContactAry as $id => $abbrev)
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