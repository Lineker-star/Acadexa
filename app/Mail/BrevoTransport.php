<?php

namespace App\Mail;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

/**
 * Sends every e-mail of the platform through Brevo's transactional HTTP API
 * (https://api.brevo.com/v3/smtp/email) with an API key — no SMTP port needed,
 * which also works on hosts that block outgoing SMTP.
 *
 * Enabled with MAIL_MAILER=brevo (automatic once BREVO_API_KEY is set, see config/mail.php).
 * The sender (MAIL_FROM_ADDRESS) must be a sender or domain validated in the Brevo account.
 */
class BrevoTransport extends AbstractTransport
{
    private const ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

    public function __construct(private ?string $apiKey)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if (blank($this->apiKey)) {
            throw new TransportException('Brevo: BREVO_API_KEY is not set.');
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $from = $email->getFrom()[0] ?? null;
        if (! $from) {
            throw new TransportException('Brevo: the message has no sender (MAIL_FROM_ADDRESS).');
        }

        $payload = array_filter([
            'sender'      => $this->address($from),
            'to'          => $this->addresses($email->getTo()),
            'cc'          => $this->addresses($email->getCc()),
            'bcc'         => $this->addresses($email->getBcc()),
            'replyTo'     => ($reply = $email->getReplyTo()[0] ?? null) ? $this->address($reply) : null,
            'subject'     => $email->getSubject(),
            'htmlContent' => $email->getHtmlBody(),
            'textContent' => $email->getTextBody(),
            'attachment'  => $this->attachments($email),
        ]);

        $response = Http::withHeaders(['api-key' => $this->apiKey, 'accept' => 'application/json'])
            ->timeout(20)->retry(2, 500, throw: false)
            ->post(self::ENDPOINT, $payload);

        if (! $response->successful()) {
            // Brevo explains the refusal (unknown sender, invalid key, credits…) in "message".
            throw new TransportException('Brevo: ' . ($response->json('message') ?: 'HTTP ' . $response->status()));
        }

        if ($id = $response->json('messageId')) {
            $message->getOriginalMessage()->getHeaders()->addTextHeader('X-Brevo-Message-Id', $id);
        }
    }

    /** @param Address[] $addresses */
    private function addresses(array $addresses): array
    {
        return array_map(fn (Address $address) => $this->address($address), $addresses);
    }

    private function address(Address $address): array
    {
        return array_filter(['email' => $address->getAddress(), 'name' => $address->getName()]);
    }

    private function attachments(Email $email): array
    {
        $files = [];
        foreach ($email->getAttachments() as $attachment) {
            $files[] = [
                'name'    => $attachment->getFilename() ?: 'file',
                'content' => base64_encode($attachment->getBody()),
            ];
        }
        return $files;
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
