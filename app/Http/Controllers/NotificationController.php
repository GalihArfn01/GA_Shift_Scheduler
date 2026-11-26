<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Halaman daftar semua notifikasi user yang sedang login.
     */
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'semua');

        $query = $filter === 'belum_dibaca'
            ? auth()->user()->unreadNotifications()
            : auth()->user()->notifications();

        $notifications = $query->latest()->paginate(20)->withQueryString();

        // Kelompokkan notifikasi per halaman berdasarkan rentang waktu
        $groups = [];
        foreach ($notifications as $notif) {
            $created = $notif->created_at;

            if ($created->isToday()) {
                $key = 'Hari Ini';
            } elseif ($created->isYesterday()) {
                $key = 'Kemarin';
            } elseif ($created->diffInDays(now()) <= 7) {
                $key = '7 Hari Terakhir';
            } else {
                $key = 'Lebih Lama';
            }

            $groups[$key][] = $notif;
        }

        $totalCount  = auth()->user()->notifications()->count();
        $unreadCount = auth()->user()->unreadNotifications()->count();

        return view('notifications.index', compact('notifications', 'groups', 'filter', 'totalCount', 'unreadCount'));
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca, lalu redirect ke URL tujuan.
     */
    public function markRead(string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('dashboard');

        // Normalisasi URL tujuan menjadi path relatif. Notifikasi lama menyimpan
        // URL absolut (localhost / 127.0.0.1 / domain lama) sehingga redirect ke
        // host berbeda — navigasi AJAX (swup) di blokir CORS dan baris terlihat
        // "tidak bisa diklik". Dengan path relatif, redirect selalu same-origin.
        $path  = (string) parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);
        $url   = $path . ($query ? '?' . $query : '');

        if (! str_starts_with($url, '/')) {
            $url = route('dashboard');
        }

        return redirect($url);
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca tanpa pindah halaman.
     */
    public function markOneRead(string $id)
    {
        auth()->user()->notifications()->findOrFail($id)->markAsRead();

        return back();
    }

    /**
     * Tandai SEMUA notifikasi user sebagai sudah dibaca.
     */
    public function markAllRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi telah ditandai sebagai dibaca.');
    }
}
