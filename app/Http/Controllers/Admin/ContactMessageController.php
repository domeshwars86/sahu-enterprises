<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactMessageController extends Controller
{
    public function index(Request $request, AuditLogger $auditLogger): Response
    {
        $user = $request->user();
        abort_unless($user->hasAdminPermission('contact_messages.view'), 403);
        $canSeeSensitive = $user->hasAdminPermission('contact_messages.view_sensitive');
        $messages = ContactMessage::query()->latest()->paginate(25)->withQueryString();
        $auditLogger->record('contact_messages.inbox_viewed', $user, metadata: ['page' => $messages->currentPage()]);

        return Inertia::render('Admin/ContactMessages/Index', [
            'messages' => $messages->through(fn (ContactMessage $message): array => [
                'id' => $message->id,
                'reference' => $message->reference_number,
                'subject' => $message->subject,
                'name' => $canSeeSensitive ? $message->name : $this->maskName($message->name),
                'email' => $canSeeSensitive ? $message->email : $this->maskEmail($message->email),
                'mobile' => $canSeeSensitive ? $message->mobile_number : $this->maskMobile($message->mobile_number),
                'message' => $canSeeSensitive ? $message->message : null,
                'createdAt' => $message->created_at?->toIso8601String(),
            ]),
        ]);
    }

    private function maskName(string $name): string
    {
        return (preg_split('/\s+/', trim($name))[0] ?? 'Customer').' …';
    }

    private function maskEmail(?string $email): ?string
    {
        if (! $email || ! str_contains($email, '@')) {
            return null;
        }

        [$name, $domain] = explode('@', $email, 2);

        return mb_substr($name, 0, 1).str_repeat('•', max(2, mb_strlen($name) - 1)).'@'.$domain;
    }

    private function maskMobile(string $mobile): string
    {
        return str_repeat('•', max(0, mb_strlen($mobile) - 4)).mb_substr($mobile, -4);
    }
}
