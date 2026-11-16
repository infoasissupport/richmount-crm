<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Sanitize a notification link to ensure it is always a safe relative path
     * and never redirects to a mismatched host or local development URL (e.g. 127.0.0.1:8000).
     */
    public static function sanitizeNotificationLink(?string $link): string
    {
        if (! $link || $link === '#') {
            return '#';
        }

        $parts = parse_url($link);
        if ($parts === false) {
            return '#';
        }

        $path = $parts['path'] ?? '';
        if ($path === '' && empty($parts['query']) && empty($parts['fragment'])) {
            return '#';
        }

        if ($path === '') {
            $path = '/';
        } elseif (! str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        if (! empty($parts['query'])) {
            $path .= '?' . $parts['query'];
        }

        if (! empty($parts['fragment'])) {
            $path .= '#' . $parts['fragment'];
        }

        return $path;
    }

    /**
     * Get list of notifications for authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $user->notifications()->take(30)->get()->map(function ($n) {
            return [
                'id' => $n->id,
                'type' => $n->data['type'] ?? 'bell',
                'title' => $n->data['title'] ?? 'Notification',
                'text' => $n->data['text'] ?? '',
                'link' => self::sanitizeNotificationLink($n->data['link'] ?? null),
                'unread' => is_null($n->read_at),
                'created_at' => $n->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(string $id, Request $request): RedirectResponse|JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        $rawLink = $notification?->data['link'] ?? null;
        $link = self::sanitizeNotificationLink($rawLink);
        if ($link !== '#') {
            return redirect($link);
        }

        return redirect()->back();
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('toast_success', 'All notifications marked as read.');
    }
}

