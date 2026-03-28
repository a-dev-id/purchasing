<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Purchase Request Returned for Revision</title>
</head>

<body style="margin:0; padding:0; background:#f6f6f6; font-family:Arial, Helvetica, sans-serif; color:#111827;">
    <div style="max-width:700px; margin:40px auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="padding:24px 32px; border-bottom:1px solid #e5e7eb;">
            <h2 style="margin:0; font-size:22px;">Purchase Request Returned for Revision</h2>
            <p style="margin:8px 0 0; color:#6b7280;">
                {{ $returnedByLabel ?? 'Purchasing' }} has returned your purchase request and asked for revision.
            </p>
        </div>

        <div style="padding:32px;">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="padding:10px 0; width:180px; color:#6b7280;">PR Number</td>
                    <td style="padding:10px 0; font-weight:600;">{{ $purchaseRequest->request_number ?: 'Draft #' . $purchaseRequest->id }}</td>
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
            </table>

            @if(filled($messageText))
            <div style="margin-top:24px; padding:16px; background:#fff7ed; border:1px solid #fed7aa; border-radius:10px;">
                <div style="font-size:13px; color:#9a3412; font-weight:700; margin-bottom:8px;">Revision Note</div>
                <div style="font-size:14px; color:#7c2d12; line-height:1.6;">
                    {{ $messageText }}
                </div>
            </div>
            @endif
        </div>

        <div style="padding:0 32px 32px;">
            <a href="{{ $purchaseRequestUrl }}" style="display:inline-block; background:#d97706; color:#ffffff; text-decoration:none; padding:12px 20px; border-radius:8px; font-weight:600;">
                Revise Purchase Request
            </a>

            <p style="margin:12px 0 0; font-size:13px; color:#6b7280;">
                Please sign in first before opening the purchase request.
            </p>
        </div>
    </div>
</body>

</html>