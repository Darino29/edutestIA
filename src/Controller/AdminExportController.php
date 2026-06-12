<?php

namespace App\Controller;

use App\Entity\Exam;
use League\Csv\Writer;
use Mpdf\Mpdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur pour l’exportation des examens (CSV / PDF)
 * Accessible aux enseignants ET aux administrateurs
 */
#[Route('/export')]
class AdminExportController extends AbstractController
{
    /**
     * 🧾 Export des résultats au format CSV
     */
    #[Route('/exam/{id}/export/csv', name: 'admin_exam_export_csv')]
    public function exportCsv(Exam $exam): StreamedResponse
    {
        // 🔒 Autorisation : enseignant OU admin
        if (!$this->isGranted('ROLE_TEACHER') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        return new StreamedResponse(function() use ($exam) {
            $csv = Writer::createFromFileObject(new \SplTempFileObject());
            $csv->setOutputBOM(Writer::BOM_UTF8);
            $csv->insertOne(['Étudiant', 'Email', 'Note', 'Soumis']);

            foreach ($exam->getAssignments() as $assignment) {
                if ($assignment->getFinalGrade() === null) {
                    continue;
                }
                $student = $assignment->getStudent();
                $csv->insertOne([
                    $student?->getFullName() ?? '',
                    $student?->getEmail() ?? '',
                    number_format((float) $assignment->getFinalGrade(), 2, '.', ''),
                    $assignment->getSubmittedAt()?->format('d/m/Y H:i') ?? '',
                ]);
            }

            echo (string) $csv;
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="exam_'.$exam->getId().'_resultats.csv"',
        ]);
    }

    /**
     * 📄 Export des résultats au format PDF
     */
    #[Route('/exam/{id}/export/pdf', name: 'admin_exam_export_pdf')]
    public function exportPdf(Exam $exam): Response
    {
        // 🔒 Autorisation : enseignant OU admin
        if (!$this->isGranted('ROLE_TEACHER') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        // Génération du HTML avec Twig
        $html = $this->renderView('admin/export_pdf.html.twig', [
            'exam' => $exam,
            'assignments' => $exam->getAssignments(),
        ]);

        $mpdf = new Mpdf([
            'default_font_size' => 10,
            'default_font'      => 'dejavusans',
            'tempDir'           => sys_get_temp_dir() . '/mpdf',
        ]);

        $mpdf->WriteHTML($html);

        $pdfContent = $mpdf->Output('', 'S');

        return new Response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="exam_'.$exam->getId().'_resultats.pdf"',
        ]);
    }
}
