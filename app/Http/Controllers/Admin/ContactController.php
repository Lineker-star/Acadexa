<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;

class ContactController extends Controller
{
    public function index()
    {
        $contacts = ContactSubmission::latest()->paginate(20);
        return view('admin.contacts.index', compact('contacts'));
    }

    public function show(ContactSubmission $contact)
    {
        $contact->update(['is_read' => true]);
        return view('admin.contacts.show', compact('contact'));
    }

    public function destroy(ContactSubmission $contact)
    {
        $contact->delete();
        return redirect()->route('admin.contacts.index')->with('success', 'Deleted.');
    }
}
