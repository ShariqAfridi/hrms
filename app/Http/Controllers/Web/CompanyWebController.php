<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyWebController extends Controller
{
    public function index(Request $request)
    {
        $query = Organization::withCount([
            'users' => function ($q) {
                $q->excludeDevs();
            },
            'payrolls'
        ]);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%")
                    ->orWhere('industry', 'like', "%{$search}%");
            });
        }

        $companies = $query->orderBy('name', 'asc')->paginate(10);

        return view('companies.index', compact('companies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'company_type' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'currency' => 'required|string|max:10',
            'payroll_tax_rate' => 'nullable|numeric|min:0|max:100',
            'website' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:4096',
        ]);

        try {
            $validated['legal_name'] = $validated['legal_name'] ?? '';
            $validated['company_type'] = $validated['company_type'] ?? '';
            $validated['registration_number'] = $validated['registration_number'] ?? '';
            $validated['tax_number'] = $validated['tax_number'] ?? '';
            $validated['phone'] = $validated['phone'] ?? '';
            $validated['industry'] = $validated['industry'] ?? '';
            $validated['country'] = $validated['country'] ?? '';
            $validated['city'] = $validated['city'] ?? '';
            $validated['website'] = $validated['website'] ?? '';
            $validated['description'] = $validated['description'] ?? '';
            $validated['payroll_tax_rate'] = $validated['payroll_tax_rate'] ?? 0;
            $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(4);
            $validated['is_active'] = true;

            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $uploadDir = public_path('uploads/company_logos');
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $filename = 'company_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                $file->move($uploadDir, $filename);
                $validated['logo'] = 'uploads/company_logos/' . $filename;
            }

            Organization::create($validated);

            return redirect()->route('companies.index')->with('success', 'Company profile created successfully!');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not create company: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $company = Organization::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'company_type' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'currency' => 'required|string|max:10',
            'payroll_tax_rate' => 'nullable|numeric|min:0|max:100',
            'website' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:4096',
            'remove_logo' => 'nullable|boolean',
        ]);

        try {
            $validated['legal_name'] = $validated['legal_name'] ?? '';
            $validated['company_type'] = $validated['company_type'] ?? '';
            $validated['registration_number'] = $validated['registration_number'] ?? '';
            $validated['tax_number'] = $validated['tax_number'] ?? '';
            $validated['phone'] = $validated['phone'] ?? '';
            $validated['industry'] = $validated['industry'] ?? '';
            $validated['country'] = $validated['country'] ?? '';
            $validated['city'] = $validated['city'] ?? '';
            $validated['website'] = $validated['website'] ?? '';
            $validated['description'] = $validated['description'] ?? '';
            $validated['payroll_tax_rate'] = $validated['payroll_tax_rate'] ?? 0;
            $validated['is_active'] = $request->has('is_active') ? (bool)$request->input('is_active') : true;

            if ($request->boolean('remove_logo')) {
                if (!empty($company->logo)) {
                    $oldPath = public_path(ltrim($company->logo, '/\\'));
                    if (file_exists($oldPath) && is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $validated['logo'] = null;
            }

            if ($request->hasFile('logo')) {
                // Delete previous custom logo file if present
                if (!empty($company->logo)) {
                    $oldPath = public_path(ltrim($company->logo, '/\\'));
                    if (file_exists($oldPath) && is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $file = $request->file('logo');
                $uploadDir = public_path('uploads/company_logos');
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $filename = 'company_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                $file->move($uploadDir, $filename);
                $validated['logo'] = 'uploads/company_logos/' . $filename;
            }

            unset($validated['remove_logo']);
            $company->update($validated);

            if (isset($validated['payroll_tax_rate'])) {
                \App\Models\Payroll::where('organization_id', $company->id)->update([
                    'tax_rate' => (float) $validated['payroll_tax_rate'],
                ]);
            }

            return redirect()->route('companies.index')->with('success', 'Company details updated successfully!');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not update company: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $company = Organization::findOrFail($id);

        if (!empty($company->logo)) {
            $oldPath = public_path(ltrim($company->logo, '/\\'));
            if (file_exists($oldPath) && is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $company->delete();

        return redirect()->route('companies.index')->with('success', 'Company deleted successfully!');
    }
}
