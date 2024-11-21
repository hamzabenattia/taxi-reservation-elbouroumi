<?php

// src/Service/PdfGenerator.php
namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use TCPDF;
use Twig\Environment;


class PdfGenerator 
{

    private $params;
    private $logger;
    private $twig;


    public function __construct(ParameterBagInterface $params , LoggerInterface $logger, Environment $twig ) {
        $this->params = $params;
        $this->logger = $logger;
        $this->twig = $twig;

    }




    public function generateFacturePdf($reservation,$entity)
    {
        // $pdf = new TCPDF();
        // $pdf->AddPage();
        // // Write facture data to PDF
        // $pdf->writeHTML($htmlTemplate);
        // // Generate PDF file content
        // $pdfContent = $pdf->Output('', 'S');
        
        // $pdfDirectory = $this->params->get('kernel.project_dir') . '/public/facture/';
        // $pdfFilename = 'facture_' . $entity->getId() . '.pdf';

    
        // // Save PDF file to the server

        // $filesystem = new Filesystem();
        // $filesystem->mkdir($pdfDirectory);

        // file_put_contents($pdfDirectory . $pdfFilename, $pdfContent);

        // $this->logger->debug('Facture PDF generated: ' . $pdfDirectory . $pdfFilename);

        // return $pdfFilename;

    // Configure Dompdf
        $pdfOptions = new Options();
        
        $dompdf = new Dompdf($pdfOptions);
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isRemoteEnabled', true);
               
        $htmlTemplate = $this->twig->render('pdf/invoice_template.html.twig', [
            'invoice' => $entity,
            'reservation' => $reservation,
            // 'inlineCss' => $this->getInlineCss()
        ]);


        
        $dompdf->loadHtml($htmlTemplate);
        $dompdf->setPaper('legal', 'portrait');
        $dompdf->render();
        
        // Save PDF to a specific directory
        $outputDir = $this->params->get('kernel.project_dir') . '/public/facture/';
        if (!file_exists(filename: $outputDir)) {
            mkdir($outputDir, 0777, true);
        }
        
        $filename = 'facture_' . $entity->getId() . '.pdf';
        $filepath = $outputDir . $filename;
        
        file_put_contents($filepath, $dompdf->output());
        
        return $filename;

    }

    private function getInlineCss(): string
    {
        return <<<CSS
        body { 
            background: #ccc; 
            padding: 30px; 
            font-size: 0.6em; 
            font-family: Arial, sans-serif;
        }
        h6 { font-size: 1em; }
        .container { 
            width: 21cm; 
            min-height: 29.7cm; 
        }
        .invoice { 
            background: #fff; 
            width: 100%; 
            padding: 50px; 
        }
        .logo { width: 4cm; }
        .document-type { 
            text-align: right; 
            color: #444; 
        }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { 
            border: 1px solid #ddd; 
            padding: 8px; 
        }
        .table-striped tbody tr:nth-of-type(odd) { 
            background-color: rgba(0,0,0,.05); 
        }
        .text-right { text-align: right; }
        .conditions { 
            font-size: 0.7em; 
            color: #666; 
        }
        .bottom-page { 
            font-size: 0.7em; 
        }
CSS;
    }
}
