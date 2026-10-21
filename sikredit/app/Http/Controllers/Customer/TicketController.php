<?php

namespace App\Http\Controllers\Customer;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\GeneralNotification;
use App\Services\NumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = Ticket::query()->where('user_id', $request->user()->id)->latest()->paginate(10);

        return view('customer.tickets.index', compact('tickets'));
    }

    public function create(Request $request)
    {
        $applications = $request->user()->customer->applications()->latest()->get();

        return view('customer.tickets.create', compact('applications'));
    }

    public function store(Request $request)
    {
        $customer = $request->user()->customer;
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(Ticket::CATEGORIES))],
            'loan_application_id' => ['nullable', Rule::exists('loan_applications', 'id')->where('customer_id', $customer->id)],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $ticket = Ticket::query()->create([
            'number' => NumberGenerator::next('TKT', 'tickets', 'number'),
            'user_id' => $request->user()->id,
            'loan_application_id' => $data['loan_application_id'] ?? null,
            'category' => $data['category'],
            'subject' => $data['subject'],
        ]);
        $ticket->messages()->create(['user_id' => $request->user()->id, 'message' => $data['message']]);

        User::query()->where('role', Role::CustomerService->value)->where('is_active', true)->get()
            ->each(fn ($u) => $u->notify(new GeneralNotification("Pengaduan baru {$ticket->number}", $ticket->subject, route('admin.tickets.show', $ticket), 'ticket')));

        return redirect()->route('customer.tickets.show', $ticket)->with('success', 'Pengaduan terkirim. Petugas kami akan segera merespons.');
    }

    public function show(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);
        $ticket->load('messages.user', 'application');

        return view('customer.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->user_id === $request->user()->id && $ticket->status !== 'closed', 403);
        $data = $request->validate(['message' => ['required', 'string', 'max:3000']]);
        $ticket->messages()->create(['user_id' => $request->user()->id, 'message' => $data['message']]);
        $ticket->update(['status' => 'open']);

        return back()->with('success', 'Balasan terkirim.');
    }
}
