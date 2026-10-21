<?php

namespace App\Mail;

use App\Models\ContactRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactRequestReceived extends Mailable
{
    public function __construct(public ContactRequest $request) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New enquiry: '.$this->request->nombre, replyTo: [$this->request->email]);
    }

    public function content(): Content
    {
        return new Content(htmlString: nl2br(e(implode("\n", array_filter([
            'Name: '.$this->request->nombre,
            'Company: '.$this->request->empresa,
            'Email: '.$this->request->email,
            $this->request->telefono ? 'Phone: '.$this->request->telefono : null,
            '',
            $this->request->mejora,
        ], fn ($l) => $l !== null)))));
    }
}
