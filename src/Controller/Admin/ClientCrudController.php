<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ClientCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Client::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('firstName')->setLabel('Prénom'),
            TextField::new('lastName')->setLabel('Nom'),
            TextField::new('address')->setLabel('Addresse Postal'),
            TextField::new('phoneNumber')->setLabel('Numéro de téléphone'),
            TextField::new('email')->setLabel('Email'),
            AssociationField::new('reservations')->setLabel('N° Reservation')->hideOnForm(),
        ];
    }



    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX,  Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ;
        }



}
