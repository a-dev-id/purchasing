<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Purchase Request Approved</title>
</head>

<body style="margin:0; padding:0; background:#f6f6f6; font-family:Arial, Helvetica, sans-serif; color:#111827;">
    <div style="max-width:700px; margin:40px auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="padding:24px 32px; border-bottom:1px solid #e5e7eb;">
            <h2 style="margin:0; font-size:22px;">Purchase Request Approved</h2>
            <p style="margin:8px 0 0; color:#6b7280;">
                This purchase request has been approved by GM.
            </p>
        </div>

        <div style="padding:32px;">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="padding:10px 0; width:180px; color:#6b7280;">PR Number</td>
                    <td style="padding:10px 0; font-weight:600;">{{ $purchaseRequest->request_number ?: 'PR #' . $purchaseRequest->id }}</td>
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
                    <td style="padding:10px 0; color:#6b7280;">Status</td>
                    <td style="padding:10px 0; font-weight:600;">Approved</td>
                </tr>
            </table>
        </div>

        <div style="padding:0 32px 32px;">
            <a href="{{ $purchaseRequestUrl }}" style="display:inline-block; background:#d97706; color:#ffffff; text-decoration:none; padding:12px 20px; border-radius:8px; font-weight:600;">
                View Purchase Request
            </a>

            <p style="margin:12px 0 0; font-size:13px; color:#6b7280;">
                You may need to sign in first before opening the purchase request detail.
            </p>
        </div>
    </div>
</body>

</html>