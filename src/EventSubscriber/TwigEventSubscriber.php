<?php

namespace App\EventSubscriber;

use App\Repository\ConferenceRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;

class TwigEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var ConferenceRepository
     */
    private $conferenceRepository;

    public function __construct(Environment $environment, ConferenceRepository $conferenceRepository)
    {
        $this->twig = $environment;
        $this->conferenceRepository = $conferenceRepository;
    }

    public function onKernelController(ControllerEvent $event)
    {
        $this->twig->addGlobal('conferences', $this->conferenceRepository->findAll());
    }

    public static function getSubscribedEvents()
    {
        return [
            'kernel.controller' => 'onKernelController',
        ];
    }
}
