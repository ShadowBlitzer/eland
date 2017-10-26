<?php

namespace form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class inline_choice_type extends AbstractType
{
    public function __construct()
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
 //       $view->vars['typeahead_attr'] = $this->typeahead_type_attr->get($options);
    }    

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'expanded'          => true,
            'multiple'          => false,     
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

    /*
    public function getBlockPrefix()
    {
        return 'radio_label';
    }
    */
}