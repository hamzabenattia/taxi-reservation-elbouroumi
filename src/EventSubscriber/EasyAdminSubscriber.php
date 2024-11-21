<?php

// src/EventSubscriber/EasyAdminSubscriber.php
namespace App\EventSubscriber;

use App\Entity\Facture;
use App\Repository\ReservationRepository;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Service\PdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class EasyAdminSubscriber implements EventSubscriberInterface
{
    private $pdfGenerator;
    private $params;
    private $twig;
    private $manager;
    private $logger;




    public function __construct(PdfGenerator $pdfGenerator , 
    LoggerInterface $logger,
    ParameterBagInterface $params, Environment $twig , private ReservationRepository $repo ,  EntityManagerInterface $manager)
    {
        $this->pdfGenerator = $pdfGenerator;
        $this->params = $params;
        $this->twig = $twig;
        $this->manager = $manager;
        $this->logger = $logger;


    }

    public static function getSubscribedEvents()
    {
        return [
            AfterEntityPersistedEvent::class => ['generateFacturePdf'],
        ];
    }

    public function generateFacturePdf(AfterEntityPersistedEvent $event)
    {
        $entity = $event->getEntityInstance();

        if (!($entity instanceof Facture)) {
            return;
        }

       

        $reservation = $this->repo->findOneBy(['id' => $entity->getReservation()]);

        if ($reservation){
            $reservation->setPrice($entity->getPriceTTC());
            $entity->setClient($reservation->getClient());
        }
        

    $filename = $this->pdfGenerator->generateFacturePdf($reservation,$entity);

      $entity->setName($filename);
      $this->manager->flush();

        
    }
}
