<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Facture;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FactureController extends AbstractController
{
    #[Route('/facture/{id}', name: 'app_facture')]
   #[IsGranted('FACTURE_VIEW', subject: 'facture' , message: 'Vous n\'avez pas les permissions pour accéder à cette facture')]

    public function index(Facture $facture ): Response
    {
        return $this->render('facture/index.html.twig', [
            'facture' => $facture,
        ]);
    }
}
