<?php

namespace App\Command;

use App\services\PdfGeneratorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestPdfGenerationCommand extends Command
{
    protected static $defaultName = 'app:test-pdf';

    private $pdfGenerator;

    public function __construct(PdfGeneratorService $pdfGenerator)
    {
        parent::__construct();
        $this->pdfGenerator = $pdfGenerator;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $html = '<h1>Test PDF</h1><p>Ceci est un test de génération de PDF.</p>';
        $pdfContent = $this->pdfGenerator->getOutputFromHtml($html);

        file_put_contents('test.pdf', $pdfContent);

        $output->writeln('PDF généré avec succès : test.pdf');

        return Command::SUCCESS;
    }
}
