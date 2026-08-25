<?php

namespace App\Mail;

use App\Models\Newsletter;
use App\Models\NewsletterDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class NewsletterMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Newsletter $newsletter,
        public NewsletterDelivery $delivery,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->newsletter->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.newsletter',
            with: [
                'formattedContent' => $this->formattedContent(),
                'unsubscribeUrl' => filled($this->delivery->unsubscribe_token)
                    ? route('unsubscribe', $this->delivery->unsubscribe_token)
                    : null,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function formattedContent(): string
    {
        $html = sprintf(
            '<div class="newsletter-content">%s</div>',
            $this->newsletter->content,
        );

        $inlinedHtml = (new CssToInlineStyles)->convert($html, <<<'CSS'
            .newsletter-content {
                color: #27272a;
                font-family: Arial, Helvetica, sans-serif;
                font-size: 16px;
                line-height: 1.75;
            }
            .newsletter-content div,
            .newsletter-content p {
                margin: 0 0 16px;
            }
            .newsletter-content h1 {
                margin: 0 0 20px;
                color: #18181b;
                font-size: 30px;
                line-height: 1.25;
            }
            .newsletter-content h2 {
                margin: 24px 0 14px;
                color: #18181b;
                font-size: 24px;
                line-height: 1.3;
            }
            .newsletter-content h3 {
                margin: 20px 0 12px;
                color: #18181b;
                font-size: 20px;
                line-height: 1.4;
            }
            .newsletter-content strong {
                color: #18181b;
                font-weight: 700;
            }
            .newsletter-content a {
                color: #4f46e5;
                font-weight: 600;
                text-decoration: underline;
            }
            .newsletter-content blockquote {
                margin: 20px 0;
                padding: 14px 18px;
                border-left: 4px solid #8b5cf6;
                background: #f5f3ff;
                color: #52525b;
                font-style: italic;
            }
            .newsletter-content ul,
            .newsletter-content ol {
                margin: 0 0 18px;
                padding-left: 24px;
            }
            .newsletter-content li {
                margin-bottom: 8px;
            }
            .newsletter-content img {
                display: block;
                max-width: 100%;
                height: auto;
                margin: 20px auto;
                border-radius: 10px;
            }
            CSS);

        if (preg_match('/<body>(.*)<\/body>/s', $inlinedHtml, $matches) === 1) {
            return $matches[1];
        }

        return $this->newsletter->content;
    }
}
