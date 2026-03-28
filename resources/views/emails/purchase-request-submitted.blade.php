<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>
        @if(!empty($isSubmittedToGm))
        Purchase Request Submitted to GM
        @elseif(!empty($isReturnedToAccounting))
        Purchase Request Returned to Accounting
        @elseif(!empty($isSubmittedToAccounting))
        Purchase Request Submitted to Accounting
        @elseif(!empty($isReturnedToPurchasing))
        Purchase Request Returned to Purchasing
        @elseif(!empty($isResubmission))
        Revised Purchase Request Resubmitted
        @else
        New Purchase Request Submitted
        @endif
    </title>
</head>

<body style="margin:0; padding:0; background:#f6f6f6; font-family:Arial, Helvetica, sans-serif; color:#111827;">
    <div style="max-width:700px; margin:40px auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="padding:24px 32px; border-bottom:1px solid #e5e7eb;">
            <h2 style="margin:0; font-size:22px;">
                @if(!empty($isSubmittedToGm))
                Purchase Request Submitted to GM
                @elseif(!empty($isReturnedToAccounting))
                Purchase Request Returned to Accounting
                @elseif(!empty($isSubmittedToAccounting))
                Purchase Request Submitted to Accounting
                @elseif(!empty($isReturnedToPurchasing))
                Purchase Request Returned to Purchasing
                @elseif(!empty($isResubmission))
                Revised Purchase Request Resubmitted
                @else
                New Purchase Request Submitted
                @endif
            </h2>

            <p style="margin:8px 0 0; color:#6b7280;">
                @if(!empty($isSubmittedToGm))
                Accounting has reviewed this purchase request and submitted it to GM for final approval.
                @elseif(!empty($isReturnedToAccounting))
                GM has returned this purchase request to Accounting for further review.
                @elseif(!empty($isSubmittedToAccounting))
                Purchasing has reviewed this purchase request and submitted it to Accounting for the next approval step.
                @elseif(!empty($isReturnedToPurchasing))
                Accounting or GM has returned this purchase request to Purchasing for further review.
                @elseif(!empty($isResubmission))
                The requester has updated the purchase request based on the revision note and submitted it back to Purchasing.
                @else
                A requester has submitted a new purchase request and it is now waiting for Purchasing review.
                @endif
            </p>
        </div>

        <div style="padding:32px;">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="padding:10px 0; width:180px; color:#6b7280;">PR Number</td>
                    <td style="padding:10px 0; font-weight:600;">{{ $purchaseRequest->request_number }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0; color:#6b7280;">Requester Name</td>
                    <td style="padding:10px 0; font-weight:600;">{{ $purchaseRequest->requester_name }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0; color:#6b7280;">Request Name</td>
                    <td style="padding:10px 0; font-weight:600;">{{ $purchaseRequest->title }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0; color:#6b7280;">Department</td>
                    <td style="padding:10px 0; font-weight:600;">{{ $purchaseRequest->department_name }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0; color:#6b7280;">Priority</td>
                    <td style="padding:10px 0; font-weight:600;">{{ ucfirst($purchaseRequest->priority) }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0; color:#6b7280;">Submitted At</td>
                    <td style="padding:10px 0; font-weight:600;">{{ optional($purchaseRequest->submitted_at)->format('d M Y H:i') }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0; color:#6b7280;">Items</td>
                    <td style="padding:10px 0; font-weight:600;">{{ $purchaseRequest->items()->count() }}</td>
                </tr>
            </table>
        </div>

        <div style="padding:0 32px 32px;">
            <a href="{{ $purchaseRequestUrl }}" style="display:inline-block; background:#d97706; color:#ffffff; text-decoration:none; padding:12px 20px; border-radius:8px; font-weight:600;">
                Open Purchase Request
            </a>

            <p style="margin:12px 0 0; font-size:13px; color:#6b7280;">
                You may need to sign in first before opening the purchase request detail.
            </p>
        </div>
    </div>
</body>

</html>