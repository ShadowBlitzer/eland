<?php declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class LocaleSubscriber implements EventSubscriberInterface
{
    private $locales = [
        'nl'    => 'nl_NL.UTF-8',
        'en'    => 'en_GB.UTF-8',
    ];

    public function onKernelRequest(GetResponseEvent $event)
    {
        $locale = $event->getRequest()->getLocale();

        if (isset($this->locales[$locale]))
        {
            setlocale(LC_TIME, $this->locales[$locale]);         
        }    
    }

    public static function getSubscribedEvents()
    {
        return [
           'kernel.request' => 'onKernelRequest',
        ];
    }
}
