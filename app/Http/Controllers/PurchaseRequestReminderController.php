<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PurchaseRequestReminderController extends Controller
{
    public function run(Request $request, string $token)
    {
        abort_unless($token === config('app.cron_token'), 403);

        $now = now();
        $processed = 0;
        $sent = 0;
        $skipped = 0;

        $purchaseRequests = PurchaseRequest::with('requester')
            ->whereNotNull('last_activity_at')
            ->get()
            ->filter(function (PurchaseRequest $purchaseRequest) use ($now) {
                if (! $purchaseRequest->isReminderEligible()) {
                    return false;
                }

                $dueFromActivity = $purchaseRequest->last_activity_at->copy()->addDays(3);

                if ($now->lt($dueFromActivity)) {
                    return false;
                }

                if ($purchaseRequest->last_reminder_sent_at) {
                    $nextReminderAt = $purchaseRequest->last_reminder_sent_at->copy()->addDays(3);

                    if ($now->lt($nextReminderAt)) {
                        return false;
                    }
                }

                return true;
            });

        foreach ($purchaseRequests as $purchaseRequest) {
            $processed++;

            $recipients = $this->resolveRecipients($purchaseRequest);

            if (empty($recipients)) {
                $this->writeReminderLog(
                    purchaseRequest: $purchaseRequest,
                    message: 'Inactivity reminder skipped because no recipient email was found.',
                    meta: [
                        'owner_role' => $purchaseRequest->reminderOwnerRole(),
                        'owner_label' => $purchaseRequest->reminderOwnerLabel(),
                    ],
                );

                $skipped++;
                continue;
            }

            $subject = 'Reminder - Purchase Request Pending Action - ' . ($purchaseRequest->request_number ?: 'Draft');
            $html = $this->buildReminderHtml($purchaseRequest);

            foreach ($recipients as $email) {
                Mail::html($html, function ($message) use ($email, $subject) {
                    $message->to($email)->subject($subject);
                });
            }

            $purchaseRequest->update([
                'last_reminder_sent_at' => $now,
            ]);

            $this->writeReminderLog(
                purchaseRequest: $purchaseRequest,
                message: 'Inactivity reminder email sent to ' . $purchaseRequest->reminderOwnerLabel() . '.',
                meta: [
                    'owner_role' => $purchaseRequest->reminderOwnerRole(),
                    'owner_label' => $purchaseRequest->reminderOwnerLabel(),
                    'recipients' => $recipients,
                    'inactive_since' => optional($purchaseRequest->last_activity_at)?->toDateTimeString(),
                    'days_inactive' => optional($purchaseRequest->last_activity_at)?->diffInDays($now),
                ],
            );

            $sent++;
        }

        return response()->json([
            'success' => true,
            'processed' => $processed,
            'sent' => $sent,
            'skipped' => $skipped,
            'checked_at' => $now->toDateTimeString(),
        ]);
    }

    protected function resolveRecipients(PurchaseRequest $purchaseRequest): array
    {
        return match ($purchaseRequest->reminderOwnerRole()) {
            'requester' => $this->normalizeEmails([
                optional($purchaseRequest->requester)->email,
            ]),

            'purchasing' => $this->normalizeEmails(
                config('mail.purchasing_notification_emails', [])
            ),

            'accounting' => $this->normalizeEmails(
                config('mail.accounting_notification_emails', [])
            ),

            'gm' => $this->normalizeEmails(
                config('mail.gm_notification_emails', [])
            ),

            default => [],
        };
    }

    protected function normalizeEmails(array $emails): array
    {
        return collect($emails)
            ->map(fn($email) => trim((string) $email))
            ->filter(fn($email) => filled($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    protected function buildReminderHtml(PurchaseRequest $purchaseRequest): string
    {
        $requestNumber = e($purchaseRequest->request_number ?: '-');
        $title = e($purchaseRequest->title ?: '-');
        $department = e($purchaseRequest->department_name ?: '-');
        $requesterName = e($purchaseRequest->requester_name ?: '-');
        $priority = e(ucfirst($purchaseRequest->priority ?: '-'));
        $status = e(str_replace('_', ' ', ucfirst($purchaseRequest->status)));
        $owner = e($purchaseRequest->reminderOwnerLabel() ?: '-');
        $lastActivityAt = $purchaseRequest->last_activity_at
            ? $purchaseRequest->last_activity_at->format('d M Y H:i')
            : '-';

        return "
            <div style='font-family:Arial,Helvetica,sans-serif;color:#111827;line-height:1.6;max-width:700px;margin:0 auto;padding:24px;'>
                <h2 style='margin:0 0 16px;'>Purchase Request Reminder</h2>
                <p style='margin:0 0 16px;'>
                    This purchase request has had no activity for 3 days and is still waiting for action from <strong>{$owner}</strong>.
                </p>

                <table style='width:100%;border-collapse:collapse;margin-top:12px;'>
                    <tr>
                        <td style='padding:8px;border:1px solid #e5e7eb;width:220px;'><strong>PR Number</strong></td>
                        <td style='padding:8px;border:1px solid #e5e7eb;'>{$requestNumber}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px;border:1px solid #e5e7eb;'><strong>Request Name</strong></td>
                        <td style='padding:8px;border:1px solid #e5e7eb;'>{$title}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px;border:1px solid #e5e7eb;'><strong>Requester</strong></td>
                        <td style='padding:8px;border:1px solid #e5e7eb;'>{$requesterName}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px;border:1px solid #e5e7eb;'><strong>Department</strong></td>
                        <td style='padding:8px;border:1px solid #e5e7eb;'>{$department}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px;border:1px solid #e5e7eb;'><strong>Priority</strong></td>
                        <td style='padding:8px;border:1px solid #e5e7eb;'>{$priority}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px;border:1px solid #e5e7eb;'><strong>Status</strong></td>
                        <td style='padding:8px;border:1px solid #e5e7eb;'>{$status}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px;border:1px solid #e5e7eb;'><strong>Last Activity</strong></td>
                        <td style='padding:8px;border:1px solid #e5e7eb;'>{$lastActivityAt}</td>
                    </tr>
                </table>
            </div>
        ";
    }

    protected function writeReminderLog(PurchaseRequest $purchaseRequest, string $message, array $meta = []): void
    {
        $purchaseRequest->logs()->create([
            'user_id' => null,
            'user_name' => 'System',
            'user_email' => null,
            'role_name' => 'system',
            'action' => 'inactivity_reminder_sent',
            'from_status' => $purchaseRequest->status,
            'to_status' => $purchaseRequest->status,
            'message' => $message,
            'meta' => $meta,
            'acted_at' => now(),
        ]);
    }
}
