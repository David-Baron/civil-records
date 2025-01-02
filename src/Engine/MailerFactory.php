<?php

namespace CivilRecords\Engine;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\Mailer;
use CivilRecords\Engine\MailerTemplate;
use Symfony\Component\Mailer\Transport;

class MailerFactory
{
    public function __construct()
    {
        if (!isset($_ENV['MAILER_FACTORY_DSN']) || empty($_ENV['MAILER_FACTORY_DSN']) || !isset($_ENV['EMAIL_SITE']) || empty($_ENV['EMAIL_SITE'])) {
            throw new \Exception("Mailling system is not configured.", 1);
        }
    }

    public function createEmail(string $from, string $to, string $subject, string $template = null, array $data = [])
    {
        if ($template !== null) {
            $view = MailerTemplate::format($template, $data);
        } else {
            $view = $data['message'];
        }

        $email = (new Email())
            ->from(Address::create($from))
            ->to($to)
            ->subject($subject)
            ->html($view);

        return $email;
    }

    public function send(Email $email)
    {
        // $dsn = 'smtp://localhost:1025';
        $dsn = $_ENV['MAILER_FACTORY_DSN'];
        $transport = Transport::fromDsn($dsn);
        $mailer = new Mailer($transport);
        $mailer->send($email);
    }
}
