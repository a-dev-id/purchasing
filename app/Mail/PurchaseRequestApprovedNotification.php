<?php

namespace App\Mail;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseRequestApprovedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public PurchaseRequest $purchaseRequest;

    public function __construct(PurchaseRequest $purchaseRequest)
    {
        $this->purchaseRequest = $purchaseRequest;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Purchase Request Approved - ' . ($this->purchaseRequest->request_number ?: 'PR #' . $this->purchaseRequest->id),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-request-approved',
            with: [
                'purchaseRequest' => $this->purchaseRequest,
                'purchaseRequestUrl' => PurchaseRequestResource::getUrl('view', [
                    'record' => $this->purchaseRequest,
                ]),
            ],
        );
    }
}
