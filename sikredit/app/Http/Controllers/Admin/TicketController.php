<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Notifications\GeneralNotification;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = Ticket::query()->with('user')
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByRaw("case status when 'open' then 0 when 'answered' then 1 else 2 end")
            ->latest()->paginate(20)->withQueryString();

        return view('admin.tickets.index', compact('tickets'));
    }

    public function show(Ticket $ticket)
    {
        return view('admin.tickets.show', ['ticket' => $ticket->load('messages.user', 'user', 'application')]);
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:3000'], 'close' => ['nullable', 'boolean']]);
        $ticket->messages()->create(['user_id' => $request->user()->id, 'message' => $data['message']]);
        $ticket->update(['status' => $request->boolean('close') ? 'closed' : 'answered']);
        $ticket->user->notify(new GeneralNotification("Tanggapan pengaduan {$ticket->number}", 'Petugas kami telah menanggapi pengaduan Anda: '.$ticket->subject, route('customer.tickets.show', $ticket), 'ticket'));

        return back()->with('success', 'Tanggapan terkirim.');
    }
}
