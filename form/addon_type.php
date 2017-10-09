<?php

namespace form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class addon_type extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (isset($options['fa'])) 
        {
            $view->vars['fa'] = $options['fa'];
        }

        if (isset($options['addon_label'])) 
        {
            $view->vars['addon_label'] = $options['addon_label'];
        }
    }    

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'addon_fa'      => null,
            'addon_label'   => null,
        ]);
    }

    public function getParent()
    {
        return TextType::class;
    }

    public function getBlockPrefix()
    {
        return 'addon';
    }
}