<?php

namespace App\Mail;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseRequestReturnedToRequesterNotification extends Mailable
{
    use Queueable, SerializesModels;

    public PurchaseRequest $purchaseRequest;
    public ?string $messageText;
    public string $returnedByLabel;

    public function __construct(
        PurchaseRequest $purchaseRequest,
        ?string $messageText = null,
        string $returnedByLabel = 'Purchasing'
    ) {
        $this->purchaseRequest = $purchaseRequest;
        $this->messageText = $messageText;
        $this->returnedByLabel = $returnedByLabel;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Purchase Request Returned by ' . $this->returnedByLabel . ' for Revision - ' . ($this->purchaseRequest->request_number ?: 'PR #' . $this->purchaseRequest->id),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-request-returned-to-requester',
            with: [
                'purchaseRequest' => $this->purchaseRequest,
                'messageText' => $this->messageText,
                'returnedByLabel' => $this->returnedByLabel,
                'purchaseRequestUrl' => PurchaseRequestResource::getUrl('edit', [
                    'record' => $this->purchaseRequest,
                ]),
            ],
        );
    }
}
