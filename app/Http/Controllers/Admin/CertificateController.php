<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as FacadeRequest;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $certificates = Certificate::with(['user', 'course.translations'])
            ->when($request->search, function ($q) use ($request) {
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%"))
                  ->orWhereHas('course.translations', fn($t) => $t->where('title', 'like', "%{$request->search}%"))
                  ->orWhere('certificate_code', 'like', "%{$request->search}%");
            })
            ->latest('issued_at')->paginate(25)->withQueryString();
        return view('admin.certificates.index', compact('certificates'));
    }

    public function destroy(Certificate $certificate)
    {
        $certificate->delete();
        return back()->with('success', 'Certificate revoked.');
    }

    public function template()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        return view('admin.certificate-template.edit', compact('settings'));
    }

    public function updateTemplate(Request $request)
    {
        $fields = ['cert_institution','cert_subheading','cert_sig_name','cert_sig_title','cert_description','cert_bg_color'];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                Setting::set($field, $request->input($field));
            }
        }

        if ($request->hasFile('cert_logo')) {
            $path = $request->file('cert_logo')->store('cert', 'public');
            Setting::set('cert_logo', $path);
        }

        return back()->with('success', 'Certificate template updated.');
    }
}
