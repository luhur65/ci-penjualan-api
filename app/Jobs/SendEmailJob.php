<?php

namespace App\Jobs;

use CodeIgniter\Queue\BaseJob;
use App\Libraries\EmailSender;

class SendEmailJob extends BaseJob
{
    public function process()
    {
        $payload = $this->data;

        $email = $payload['email'] ?? null;
        $subject = $payload['subject'] ?? 'Notification';
        $template = $payload['template'] ?? 'default_template';
        $templateData = $payload['data'] ?? [];

        if (!$email) {
            log_message('error', 'SendEmailJob failed: No email address provided.');
            return false;
        }

        try {
            $mailer = new EmailSender();
            $mailer->sendEmail($email, $subject, $template, $templateData);
            return true;
        } catch (\Exception $e) {
            log_message('error', 'SendEmailJob failed: ' . $e->getMessage());
            // Depending on the throw behavior in EmailSender, we might want to return false to retry or log it.
            // Throwing exception again will typically fail the job and it might be retried depending on the queue configuration.
            throw $e;
        }
    }
}
