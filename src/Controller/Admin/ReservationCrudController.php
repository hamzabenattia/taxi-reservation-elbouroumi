<?php

namespace App\Controller\Admin;

use App\Entity\Facture;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Service\EmailSender;
use App\Service\PdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class ReservationCrudController extends AbstractCrudController
{
    private $pdfGenerator;
    private $params;
    private $twig;
    private $manager;




    public function __construct(PdfGenerator $pdfGenerator, private EmailSender $emailSender, ParameterBagInterface $params, Environment $twig, private ReservationRepository $repo,  EntityManagerInterface $manager)
    {
        $this->pdfGenerator = $pdfGenerator;
        $this->params = $params;
        $this->twig = $twig;
        $this->manager = $manager;
    }

    public static function getEntityFqcn(): string
    {
        return Reservation::class;
    }


    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInPlural('Reservations')
            ->setEntityLabelInSingular('Reservation')
            ->setDefaultSort(['reservation_datetime' => 'DESC']);
    }




    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {

        if (!$entityInstance->getFacture()) {
            $facture = new Facture();
        } else {
            $facture = $entityInstance->getFacture();
        }
        $facture->setPriceTTC($entityInstance->getPrice());
        $facture->setPriceHT($entityInstance->getPrice()/ Facture::TVA);
        $facture->setReservation($entityInstance);
        $facture->setClient($entityInstance->getClient());
        $entityInstance->setFacture($facture);
        $entityManager->persist($facture);
        $entityManager->flush();


        // Generate PDF for the new Facture
        // $factureName = $this->pdfGenerator->generateFacturePdf($entityInstance, $facture);
        // $facture->setName($factureName);
        // $entityManager->persist($facture);
        // $entityManager->flush();
    }



    public function configureActions(Actions $actions): Actions
    {

        $viewInvoice = Action::new('Facture')
            ->displayIf(fn ($entity) => $entity->getFacture() !== null)
            ->linkToRoute('app_facture', function (Reservation $Reservation): array {
                return [
                    'id' => $Reservation->getFacture(),
                ];
            });
           

        return $actions
            // ...
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            
        ->addBatchAction(Action::new('reject', 'Refuser')
        ->linkToCrudAction('rejectReservation')
        ->addCssClass('btn btn-danger')
        ->setIcon('fa fa-ban'))
        ->addBatchAction(Action::new('approve', 'Accepter')
        ->linkToCrudAction('approveReservation')
        ->addCssClass('btn btn-primary')
        ->setIcon('fa fa-check'))
        ->add(Crud::PAGE_INDEX, $viewInvoice);
    }


    public function approveReservation(BatchActionDto $batchActionDto, ReservationRepository $reservationRepository , EntityManagerInterface $entityManager)
{
    foreach ($batchActionDto->getEntityIds() as $id) {
        $reservation = $reservationRepository->find($id);
        $reservation->setStatus(status: Reservation::STATUS_CONFIRMED);

        $this->emailSender->sendEmail(
            'noreply@ebtaxi91.fr',
            $reservation->getClient()->getEmail(),
            'Votre réservation N° '.$reservation->getId().' a été acceptée',
            'emails/Client/reservationAccecpter.html.twig',
            [
                'reservation' => $reservation
            ]
        );
    }

    $entityManager->flush();

    return $this->redirect($batchActionDto->getReferrerUrl());
}

public function rejectReservation(BatchActionDto $batchActionDto, ReservationRepository $reservationRepository , EntityManagerInterface $entityManager)
{
    foreach ($batchActionDto->getEntityIds() as $id) {
        $reservation = $reservationRepository->find($id);
        $reservation->setStatus(status: Reservation::STATUS_CANCELLED);

        $this->emailSender->sendEmail(
            'noreply@ebtaxi91.fr',
            $reservation->getClient()->getEmail(),
            'Votre réservation N° '.$reservation->getId().' a été refusée',
            'emails/Client/reservationRefuser.html.twig',
            [
                'reservation' => $reservation
            ],
        );
    }

    $entityManager->flush();

    return $this->redirect($batchActionDto->getReferrerUrl());
}

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices([
                'Confirmé' => Reservation::STATUS_CONFIRMED,
                'Annulé' => Reservation::STATUS_CANCELLED,
                'En attente' => Reservation::STATUS_PENDING,
            ]))
            ->add(DateTimeFilter::new('reservation_datetime')->setLabel('Date de réservation'))
            ->add(EntityFilter::new('client')->setLabel('Client'))
            ;
    }



    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('destination')->onlyOnDetail()->setLabel('Destination'),
            TextField::new('destination')->onlyOnIndex()->setLabel('Destination'),
            TextField::new('depAddress')->onlyOnIndex()->setLabel('Addresse de départ'),
            TextField::new('depAddress')->onlyOnDetail()->setLabel('Addresse de départ'),
            DateTimeField::new('reservation_datetime')->onlyOnIndex()->setLabel('Date et heure de réservation'),
            DateTimeField::new('reservation_datetime')->onlyOnDetail()->setLabel('Date et heure de réservation'),
            NumberField::new('nbPassengers')->onlyOnIndex()->setLabel('Nombre de passagers'),
            NumberField::new('nbPassengers')->onlyOnDetail()->setLabel('Nombre de passagers'),

            MoneyField::new('price')->setCurrency('EUR')->setLabel('Prix')->setStoredAsCents(false)->setNumDecimals(2),
            ChoiceField::new('status')->setChoices([
                'Confirmé' => Reservation::STATUS_CONFIRMED,
                'Annulé' => Reservation::STATUS_CANCELLED,
                'En attente' => Reservation::STATUS_PENDING,

            ])->hideOnForm(),
            TextField::new('client.phoneNumber')->onlyOnDetail()->setLabel('Numéro de téléphone de client'),
            TextField::new('client.email')->onlyOnDetail()->setLabel('Email de client'),
            TextField::new('client.firstName')->onlyOnDetail()->setLabel('Prénom de client'),
            TextField::new('client.lastName ')->onlyOnDetail()->setLabel('Nom de client'),

            DateField::new('createdAt')->hideOnForm()->setLabel('Date de création'),


        ];
    }
}
