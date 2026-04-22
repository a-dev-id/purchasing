<?php

namespace App\Mail;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseRequestSubmittedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public PurchaseRequest $purchaseRequest;
    public string $fromStatus;

    public function __construct(PurchaseRequest $purchaseRequest, string $fromStatus = 'draft')
    {
        $this->purchaseRequest = $purchaseRequest;
        $this->fromStatus = $fromStatus;
    }

    protected function isRequesterResubmission(): bool
    {
        return in_array($this->fromStatus, [
            'revision_from_purchasing',
            'revision_from_accounting',
            'revision_from_gm',
            'revision_to_requester_from_accounting',
            'revision_to_requester_from_gm',
        ], true);
    }

    protected function isReturnedToPurchasing(): bool
    {
        return in_array($this->fromStatus, [
            'revision_to_purchasing_from_accounting',
            'revision_to_purchasing_from_gm',
        ], true);
    }

    protected function isSubmittedToAccounting(): bool
    {
        return $this->fromStatus === 'submitted_to_accounting';
    }

    protected function isReturnedToAccounting(): bool
    {
        return $this->fromStatus === 'revision_to_accounting_from_gm';
    }

    protected function isSubmittedToGm(): bool
    {
        return $this->fromStatus === 'submitted_to_gm';
    }

    protected function isSubmittedToFinancialController(): bool
    {
        return $this->fromStatus === 'gm_approved';
    }

    protected function getSubjectPrefix(): string
    {
        return match (true) {
            $this->isSubmittedToFinancialController() => 'Purchase Request Approved by GM - Sent to Financial Controller',
            $this->isSubmittedToGm() => 'Purchase Request Submitted to GM',
            $this->isReturnedToAccounting() => 'Purchase Request Returned to Accounting',
            $this->isSubmittedToAccounting() => 'Purchase Request Submitted to Accounting',
            $this->isReturnedToPurchasing() => 'Purchase Request Returned to Purchasing',
            $this->isRequesterResubmission() => 'Revised Purchase Request Resubmitted',
            default => 'New Purchase Request Submitted',
        };
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->getSubjectPrefix() . ' - ' . ($this->purchaseRequest->request_number ?: 'PR #' . $this->purchaseRequest->id),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-request-submitted',
            with: [
                'purchaseRequest' => $this->purchaseRequest,
                'purchaseRequestUrl' => PurchaseRequestResource::getUrl('edit', [
                    'record' => $this->purchaseRequest,
                ]),
                'isResubmission' => $this->isRequesterResubmission(),
                'isReturnedToPurchasing' => $this->isReturnedToPurchasing(),
                'isSubmittedToAccounting' => $this->isSubmittedToAccounting(),
                'isReturnedToAccounting' => $this->isReturnedToAccounting(),
                'isSubmittedToGm' => $this->isSubmittedToGm(),
                'isSubmittedToFinancialController' => $this->isSubmittedToFinancialController(),
            ],
        );
    }
}
