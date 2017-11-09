<?php

namespace form\typeahead;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use form\typeahead\typeahead_type;
use transformer\typeahead_user_transformer;

class typeahead_user_type extends AbstractType
{
    private $transformer;
    
    public function __construct(typeahead_user_transformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'process'           => 'user',
        ]);
    }

    public function getParent()
    {
        return 'typeahead_type';
    }

    public function getBlockPrefix()
    {
        return 'typeahead_user';
    }
}