<?php

namespace form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Extension\Core\Type\FormType;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Util\ServerParams;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class form_type_etoken_extension extends AbstractTypeExtension
{
    private $etoken_manager;
    private $translator;

    public function __construct(
        etoken_manager_interface $etoken_manager, 
        TranslatorInterface $translator = null
    )
    {
        $this->etoken_manager = $etoken_manager;
        $this->translator = $translator;
    }

    public function buildForm(
        FormBuilderInterface $builder, 
        array $options
    )
    {
        if (!$options['etoken_enabled']) 
        {
            return;
        }

        $builder->addEventSubscriber(new etoken_validation_listener(
            $this->etoken_manager,
            $this->translator
        ));
    }

    public function finishView(
        FormView $view, 
        FormInterface $form, 
        array $options
    )
    {
        if ($options['etoken_enabled'] && !$view->parent && $options['compound']) 
        {
            $factory = $form->getConfig()->getFormFactory();

            $value = (string) $this->etoken_manager->get();

            $etoken_form = $factory->createNamed(
                '_etoken', 
                'Symfony\Component\Form\Extension\Core\Type\HiddenType', 
                $value, 
                [
                    'mapped' => false,
                ]
            );

            $view->children['_etoken'] = $etoken_form->createView($view);
        }
    }
 
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'etoken_enabled' => true,
        ]);
    }
 
    public function getExtendedType()
    {
        return FormType::class;
    }

    public function getDefaultOptions(array $options)
    {
        return [
            'etoken_enabled'        => true,
        ];
    }
}
