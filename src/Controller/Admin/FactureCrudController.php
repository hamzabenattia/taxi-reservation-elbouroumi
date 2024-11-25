<?php

namespace App\Controller\Admin;

use App\Entity\Facture;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;

class FactureCrudController extends AbstractCrudController
{


    public static function getEntityFqcn(): string
    {
        return Facture::class;
    }

    public function configureActions(Actions $actions): Actions
    {

        $viewInvoice = Action::new('Consulter')
        ->linkToRoute('app_facture', function (Facture $facture): array {
            return [
                'id' => $facture,
            ];
        });


        return $actions
            // ...
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_INDEX, $viewInvoice);
            ;
        }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            // TextField::new('reservation'),
            AssociationField::new('reservation')->autocomplete(),
            MoneyField::new('priceHT')->setCurrency('EUR')->onlyOnIndex()->setStoredAsCents(false)->setNumDecimals(2),
            MoneyField::new('priceTTC')->setCurrency('EUR')->setStoredAsCents(false)->setNumDecimals(2),
        ];
    }

    
    
}
