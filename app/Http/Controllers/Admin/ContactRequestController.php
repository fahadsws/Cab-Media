<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use Illuminate\Http\Request;

class ContactRequestController extends Controller
{
    public function index(Request $r)
    {
        $items = ContactRequest::query()
            ->when($r->status, fn ($q, $s) => $q->where('status', $s))
            ->when($r->q, fn ($q, $s) => $q->where(fn ($x) => $x->where('nombre', 'like', "%$s%")->orWhere('empresa', 'like', "%$s%")->orWhere('email', 'like', "%$s%")->orWhere('mejora', 'like', "%$s%")))
            ->tap(fn ($q) => \App\Support\DataTable::dateRange($q, $r->range))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.contacts.index', ['items' => $items]);
    }

    public function show(ContactRequest $contact)
    {
        return view('admin.contacts.show', ['c' => $contact]);
    }

    public function update(Request $r, ContactRequest $contact)
    {
        $d = $r->validate(['status' => 'required|in:'.implode(',', array_keys(ContactRequest::STATUSES))]);
        $contact->update($d);

        return back()->with('success', 'Estado actualizado.');
    }

    public function destroy(ContactRequest $contact)
    {
        $contact->delete();

        return redirect()->route('admin.contacts.index')->with('success', 'Solicitud eliminada.');
    }
}
