<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Mail;

/**
 * Envoi de documents par email (factures, reçus, rapports).
 */
class MailService
{
    /**
     * Envoie un document PDF par email.
     *
     * @param  string  $to  Adresse du destinataire
     * @param  string  $subject  Objet de l'email
     * @param  string  $body  Corps de l'email (texte brut)
     * @param  string  $pdfContent  Contenu binaire du PDF
     * @param  string  $filename  Nom du fichier joint
     */
    public function sendDocument(
        string $to,
        string $subject,
        string $body,
        string $pdfContent,
        string $filename,
    ): void {
        $attachment = Attachment::fromData(fn () => $pdfContent, $filename)
            ->withMime('application/pdf');

        Mail::raw($body, function ($message) use ($to, $subject, $attachment) {
            $message->to($to)
                ->subject($subject)
                ->attach($attachment);
        });

        ActivityLogger::success(
            ActivityAction::FILE_UPLOADED,
            "Document {$filename} envoyé par email à {$to}",
            [
                'metadata' => ['to' => $to, 'subject' => $subject, 'filename' => $filename],
            ]
        );
    }
}
