@extends('layouts.app')

@section('title', 'Company Directory')

@section('content')

<div x-data="companyManager()" class="space-y-6 lg:space-y-8">

    <!-- Page Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Multi-Company Management</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-indigo-500/15 text-indigo-400 border border-indigo-500/30 shadow-xs">
                    {{ $companies->total() ?? count($companies) }} Entities
                </span>
            </div>
            <p class="text-xs font-medium text-slate-400 mt-1">Manage multiple corporate entities, tax rates, currencies, and company payrolls.</p>
        </div>

        <button @click="openAddModal()" class="px-4 py-2.5 bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 hover:from-indigo-500 hover:to-purple-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center space-x-2">
            <i class="fa-solid fa-plus"></i>
            <span>Register New Company</span>
        </button>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-4 shadow-xl">
        <form action="{{ route('companies.index') }}" method="GET" class="w-full flex items-center gap-3">
            <div class="relative w-full md:w-96">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search company by name, legal name, or industry..." class="w-full pl-9 pr-4 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-medium text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-all">
            </div>

            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-600/20 transition-all">
                Search
            </button>
            @if(request('search'))
                <a href="{{ route('companies.index') }}" class="text-xs font-semibold text-rose-400 hover:underline">Clear</a>
            @endif
        </form>
    </div>

    <!-- Companies Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @forelse($companies as $company)
            @php
                $colorThemes = [
                    [
                        'name' => 'indigo',
                        'avatar' => 'from-indigo-500 via-blue-500 to-cyan-400',
                        'btn_gradient' => 'from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500',
                    ],
                    [
                        'name' => 'sky',
                        'avatar' => 'from-sky-400 via-cyan-500 to-blue-500',
                        'btn_gradient' => 'from-sky-600 to-cyan-600 hover:from-sky-500 hover:to-cyan-500',
                    ],
                    [
                        'name' => 'purple',
                        'avatar' => 'from-purple-500 via-pink-500 to-rose-400',
                        'btn_gradient' => 'from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500',
                    ],
                    [
                        'name' => 'emerald',
                        'avatar' => 'from-emerald-400 via-teal-500 to-cyan-500',
                        'btn_gradient' => 'from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500',
                    ],
                    [
                        'name' => 'amber',
                        'avatar' => 'from-amber-400 via-orange-500 to-rose-400',
                        'btn_gradient' => 'from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400',
                    ],
                ];
                $theme = $colorThemes[$loop->index % count($colorThemes)];
            @endphp
            <div class="company-card-{{ $theme['name'] }} relative overflow-hidden bg-slate-950/80 border border-slate-800 rounded-3xl shadow-xl transition-all duration-300 flex flex-col justify-between group hover:-translate-y-1">
                
                <!-- Main Content Body -->
                <div class="p-5 sm:p-6 space-y-4">
                    <!-- Top Bar: Avatar, Company Heading & Status Pill -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center space-x-3 min-w-0">
                            @if($company->logo_url)
                                <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 shadow-sm p-1 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <img src="{{ $company->logo_url }}" alt="{{ $company->name }} Logo" class="w-full h-full object-contain" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                                    <div style="display:none;" class="w-full h-full rounded-xl bg-gradient-to-tr {{ $theme['avatar'] }} items-center justify-center text-white font-black text-lg shadow-sm">
                                        {{ strtoupper(substr($company->name, 0, 1)) }}
                                    </div>
                                </div>
                            @else
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr {{ $theme['avatar'] }} border border-white/40 flex items-center justify-center text-white font-black text-xl shadow-md shrink-0 group-hover:scale-105 transition-transform">
                                    {{ strtoupper(substr($company->name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <h3 class="font-black text-white text-base leading-tight truncate group-hover:text-indigo-400 transition-colors" title="{{ $company->name }}">{{ $company->name }}</h3>
                                <div class="flex items-center space-x-1.5 mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-slate-800/80 text-slate-300 border border-slate-700/60 shadow-2xs">
                                        {{ $company->industry ?: 'Corporate' }}
                                    </span>
                                    <span class="text-[10px] font-semibold text-slate-400 font-mono">#{{ substr($company->id, 0, 8) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Status Pill -->
                        <span class="px-2.5 py-1 rounded-full font-black text-[10px] uppercase tracking-wider shadow-xs flex items-center space-x-1.5 shrink-0 {{ $company->is_active ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/15 text-rose-400 border border-rose-500/30' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $company->is_active ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400' }}"></span>
                            <span>{{ $company->is_active ? 'Active' : 'Inactive' }}</span>
                        </span>
                    </div>

                    <!-- Description -->
                    <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed">{{ $company->description ?: 'No detailed description provided for this organization.' }}</p>

                    <!-- Location Tag -->
                    @if($company->country || $company->city)
                        <div class="flex items-center space-x-2 text-[11px] font-bold text-rose-400 bg-rose-500/10 px-3 py-1.5 rounded-xl border border-rose-500/20">
                            <i class="fa-solid fa-location-dot text-rose-400 shrink-0"></i>
                            <span class="truncate">{{ $company->city ? $company->city . ', ' : '' }}{{ $company->country ?: 'Global Operations' }}</span>
                        </div>
                    @endif

                    <!-- Saturated Financial & Metric Inset Panels -->
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="p-2.5 rounded-2xl bg-indigo-500/10 border border-indigo-500/20">
                            <span class="text-slate-400 text-[10px] uppercase font-extrabold tracking-wider">Currency</span>
                            <p class="font-black text-indigo-400 mt-0.5 text-xs flex items-center">
                                <i class="fa-solid fa-coins mr-1.5 text-amber-400"></i>{{ $company->currency }}
                            </p>
                        </div>
                        <div class="p-2.5 rounded-2xl bg-amber-500/10 border border-amber-500/20">
                            <span class="text-slate-400 text-[10px] uppercase font-extrabold tracking-wider">Tax Rate %</span>
                            <p class="font-black text-amber-400 mt-0.5 text-xs flex items-center">
                                <i class="fa-solid fa-percent mr-1.5 text-amber-400"></i>{{ number_format($company->payroll_tax_rate, 1) }}%
                            </p>
                        </div>
                    </div>

                    <!-- Vibrant Staff & Payroll Counters -->
                    <div class="flex items-center justify-between gap-2 text-xs pt-0.5">
                        <div class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-indigo-500/15 border border-indigo-500/25 text-indigo-300 font-bold text-[11px] truncate shadow-xs">
                            <i class="fa-solid fa-users text-indigo-400 shrink-0"></i>
                            <span class="truncate"><strong class="font-black text-white">{{ $company->users_count }}</strong> Staff</span>
                        </div>
                        <div class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-emerald-500/15 border border-emerald-500/25 text-emerald-300 font-bold text-[11px] truncate shadow-xs">
                            <i class="fa-solid fa-file-invoice-dollar text-emerald-400 shrink-0"></i>
                            <span class="truncate"><strong class="font-black text-white">{{ $company->payrolls_count }}</strong> Payrolls</span>
                        </div>
                    </div>
                </div>

                <!-- Action Bar Footer -->
                <div class="p-4 border-t border-slate-800/80 flex items-center justify-between gap-2 bg-slate-900/40 rounded-b-3xl">
                    <a href="{{ route('payroll.index', ['organization_id' => $company->id]) }}" class="px-3.5 py-2 bg-gradient-to-r {{ $theme['btn_gradient'] }} text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center space-x-2">
                        <i class="fa-solid fa-money-check-dollar"></i>
                        <span>Manage Payrolls</span>
                    </a>

                    <div class="flex items-center space-x-1.5">
                        <button @click="openEditModal({{ json_encode($company) }})" class="p-2 text-sky-400 hover:text-sky-300 bg-sky-500/10 hover:bg-sky-500/20 border border-sky-500/20 rounded-xl transition-all shadow-xs" title="Edit Company">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <form action="{{ route('companies.destroy', $company->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this company? All associated data will be removed.');" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-rose-400 hover:text-rose-300 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 rounded-xl transition-all shadow-xs" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-slate-950/60 border border-slate-800 rounded-3xl p-12 text-center space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-slate-900 border border-slate-800 text-slate-500 flex items-center justify-center mx-auto text-xl">
                    <i class="fa-solid fa-building"></i>
                </div>
                <p class="text-sm font-semibold text-slate-400">No companies found matching search criteria.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="p-4">
        {{ $companies->links() }}
    </div>

    <!-- Register Company Modal -->
    <div x-show="addModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="addModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-xl w-full p-6 space-y-5 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                        <i class="fa-solid fa-building-circle-plus"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Register New Company Entity</h3>
                        <p class="text-[11px] text-slate-400">Set up company profile, regional jurisdiction, and payroll tax defaults.</p>
                    </div>
                </div>
                <button @click="addModalOpen = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="{{ route('companies.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                
                <!-- Company Logo Upload -->
                <div>
                    <label class="block font-semibold text-slate-300 mb-1 flex items-center justify-between">
                        <span class="flex items-center space-x-1.5">
                            <i class="fa-solid fa-image text-indigo-400"></i>
                            <span>Company Official Logo</span>
                        </span>
                        <span class="text-[10px] text-slate-400 font-normal">PNG, JPG, SVG, WebP (Max 4MB)</span>
                    </label>
                    
                    <div class="flex items-center space-x-3.5 p-3 bg-slate-950/70 border border-slate-800 rounded-2xl">
                        <div class="w-14 h-14 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center overflow-hidden shrink-0 shadow-inner relative">
                            <template x-if="addLogoPreview">
                                <img :src="addLogoPreview" class="w-full h-full object-contain p-1" />
                            </template>
                            <template x-if="!addLogoPreview">
                                <i class="fa-solid fa-building text-slate-600 text-xl"></i>
                            </template>
                        </div>
                        
                        <div class="flex-1 space-y-1">
                            <div class="flex items-center space-x-2">
                                <label class="cursor-pointer px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-400 border border-indigo-500/30 rounded-xl font-bold text-[11px] transition-all flex items-center space-x-1.5">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <span>Upload Logo</span>
                                    <input type="file" name="logo" id="add-company-logo-input" accept="image/*" class="hidden" @change="handleAddLogoUpload($event)" />
                                </label>
                                <template x-if="addLogoPreview">
                                    <button type="button" @click="clearAddLogo()" class="px-2.5 py-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 rounded-xl font-bold text-[11px] transition-all flex items-center space-x-1">
                                        <i class="fa-solid fa-trash-can"></i>
                                        <span>Remove</span>
                                    </button>
                                </template>
                            </div>
                            <p class="text-[10px] text-slate-400" x-text="addLogoFileName || 'Appears on official salary slips, executive documents, and navigation'"></p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Company Name *</label>
                        <input type="text" name="name" required placeholder="e.g. PixelCraft Studio" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Legal Registered Name</label>
                        <input type="text" name="legal_name" placeholder="e.g. PixelCraft Technologies (Pvt.) Ltd." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Corporate Email *</label>
                        <input type="email" name="email" required placeholder="contact@demo-company.com" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Phone Number</label>
                        <input type="text" name="phone" placeholder="+1 (555) 019-2834" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Currency *</label>
                        <select name="currency" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                            <option value="PKR" selected>PKR (Rs)</option>
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                            <option value="CAD">CAD ($)</option>
                            <option value="AED">AED (د.إ)</option>
                            <option value="SAR">SAR (﷼)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Payroll Tax %</label>
                        <input type="number" step="0.1" name="payroll_tax_rate" value="10.0" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Industry</label>
                        <input type="text" name="industry" placeholder="Software & UI/UX Design" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <!-- Dynamic Country & City Selectors -->
                <div class="grid grid-cols-2 gap-3 bg-slate-950/60 p-3 rounded-2xl border border-slate-800/80">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1 flex items-center justify-between">
                            <span>Country *</span>
                            <span class="text-[10px] text-indigo-400 font-normal"><i class="fa-solid fa-earth-asia mr-1"></i>Select Country</span>
                        </label>
                        <select name="country" x-model="selectedCountry" @change="onCountryChange()" required class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500 transition-all">
                            <template x-for="c in countryList" :key="c">
                                <option :value="c" x-text="c" :selected="c === selectedCountry"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1 flex items-center justify-between">
                            <span>City *</span>
                            <span class="text-[10px] text-emerald-400 font-normal" x-text="availableCities.length > 0 ? availableCities.length + ' cities' : 'Custom'"></span>
                        </label>

                        <template x-if="availableCities.length > 0 && !isCustomAddCity">
                            <div class="space-y-1">
                                <select name="city" x-model="selectedCity" @change="onCitySelectChange()" required class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500 transition-all">
                                    <template x-for="city in availableCities" :key="city">
                                        <option :value="city" x-text="city" :selected="city === selectedCity"></option>
                                    </template>
                                    <option value="__custom__">+ Other / Custom City...</option>
                                </select>
                            </div>
                        </template>

                        <template x-if="availableCities.length === 0 || isCustomAddCity">
                            <div class="flex items-center space-x-1.5">
                                <input type="text" name="city" x-model="selectedCity" required placeholder="Enter City Name..." class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500 transition-all">
                                <template x-if="availableCities.length > 0">
                                    <button type="button" @click="isCustomAddCity = false; selectedCity = availableCities[0]" class="px-2.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs rounded-xl" title="Back to list">
                                        <i class="fa-solid fa-list"></i>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Company Description</label>
                    <textarea name="description" rows="2" placeholder="Full-stack web & mobile development, UI/UX product design, and creative engineering services." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500"></textarea>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" @click="addModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg">Create Company</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Company Modal -->
    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="editModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-xl w-full p-6 space-y-5 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Edit Company Details</h3>
                        <p class="text-[11px] text-slate-400">Update company parameters, location, and operational data.</p>
                    </div>
                </div>
                <button @click="editModalOpen = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form :action="'{{ url('companies') }}/' + activeCompany.id" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <!-- Company Logo Update -->
                <div>
                    <label class="block font-semibold text-slate-300 mb-1 flex items-center justify-between">
                        <span class="flex items-center space-x-1.5">
                            <i class="fa-solid fa-image text-indigo-400"></i>
                            <span>Company Official Logo</span>
                        </span>
                        <span class="text-[10px] text-slate-400 font-normal">PNG, JPG, SVG, WebP (Max 4MB)</span>
                    </label>
                    
                    <div class="flex items-center space-x-3.5 p-3 bg-slate-950/70 border border-slate-800 rounded-2xl">
                        <div class="w-14 h-14 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center overflow-hidden shrink-0 shadow-inner relative">
                            <template x-if="editLogoPreview">
                                <img :src="editLogoPreview" class="w-full h-full object-contain p-1" />
                            </template>
                            <template x-if="!editLogoPreview && activeCompany.logo_url && !removeEditLogoFlag">
                                <img :src="activeCompany.logo_url" class="w-full h-full object-contain p-1" />
                            </template>
                            <template x-if="!editLogoPreview && (!activeCompany.logo_url || removeEditLogoFlag)">
                                <i class="fa-solid fa-building text-slate-600 text-xl"></i>
                            </template>
                        </div>
                        
                        <div class="flex-1 space-y-1">
                            <input type="hidden" name="remove_logo" :value="removeEditLogoFlag ? '1' : '0'">
                            <div class="flex items-center space-x-2">
                                <label class="cursor-pointer px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-400 border border-indigo-500/30 rounded-xl font-bold text-[11px] transition-all flex items-center space-x-1.5">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <span x-text="(activeCompany.logo_url || editLogoPreview) ? 'Change Logo' : 'Upload Logo'"></span>
                                    <input type="file" name="logo" id="edit-company-logo-input" accept="image/*" class="hidden" @change="handleEditLogoUpload($event)" />
                                </label>
                                <template x-if="(activeCompany.logo_url || editLogoPreview) && !removeEditLogoFlag">
                                    <button type="button" @click="clearEditLogo()" class="px-2.5 py-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 rounded-xl font-bold text-[11px] transition-all flex items-center space-x-1">
                                        <i class="fa-solid fa-trash-can"></i>
                                        <span>Remove</span>
                                    </button>
                                </template>
                                <template x-if="removeEditLogoFlag && activeCompany.logo_url">
                                    <button type="button" @click="restoreEditLogo()" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl font-bold text-[11px] transition-all">
                                        <span>Undo Remove</span>
                                    </button>
                                </template>
                            </div>
                            <p class="text-[10px] text-slate-400" x-text="editLogoFileName || (removeEditLogoFlag ? 'Logo will be removed upon saving' : 'Used on payslips and company documents')"></p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Company Name *</label>
                        <input type="text" name="name" x-model="activeCompany.name" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Legal Registered Name</label>
                        <input type="text" name="legal_name" x-model="activeCompany.legal_name" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Corporate Email *</label>
                        <input type="email" name="email" x-model="activeCompany.email" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Phone Number</label>
                        <input type="text" name="phone" x-model="activeCompany.phone" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Currency *</label>
                        <select name="currency" x-model="activeCompany.currency" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                            <option value="PKR">PKR (Rs)</option>
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                            <option value="CAD">CAD ($)</option>
                            <option value="AED">AED (د.إ)</option>
                            <option value="SAR">SAR (﷼)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Payroll Tax %</label>
                        <input type="number" step="0.1" name="payroll_tax_rate" x-model="activeCompany.payroll_tax_rate" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Industry</label>
                        <input type="text" name="industry" x-model="activeCompany.industry" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <!-- Dynamic Country & City Selectors for Edit -->
                <div class="grid grid-cols-2 gap-3 bg-slate-950/60 p-3 rounded-2xl border border-slate-800/80">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1 flex items-center justify-between">
                            <span>Country *</span>
                            <span class="text-[10px] text-indigo-400 font-normal"><i class="fa-solid fa-earth-asia mr-1"></i>Select Country</span>
                        </label>
                        <select name="country" x-model="activeCompany.country" @change="onEditCountryChange()" required class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500 transition-all">
                            <template x-for="c in countryList" :key="c">
                                <option :value="c" x-text="c" :selected="c === activeCompany.country"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1 flex items-center justify-between">
                            <span>City *</span>
                            <span class="text-[10px] text-emerald-400 font-normal" x-text="editAvailableCities.length > 0 ? editAvailableCities.length + ' cities' : 'Custom'"></span>
                        </label>

                        <template x-if="editAvailableCities.length > 0 && !isCustomEditCity">
                            <div class="space-y-1">
                                <select name="city" x-model="activeCompany.city" @change="onEditCitySelectChange()" required class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500 transition-all">
                                    <template x-for="city in editAvailableCities" :key="city">
                                        <option :value="city" x-text="city" :selected="city === activeCompany.city"></option>
                                    </template>
                                    <option value="__custom__">+ Other / Custom City...</option>
                                </select>
                            </div>
                        </template>

                        <template x-if="editAvailableCities.length === 0 || isCustomEditCity">
                            <div class="flex items-center space-x-1.5">
                                <input type="text" name="city" x-model="activeCompany.city" required placeholder="Enter City Name..." class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500 transition-all">
                                <template x-if="editAvailableCities.length > 0">
                                    <button type="button" @click="isCustomEditCity = false; activeCompany.city = editAvailableCities[0]" class="px-2.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs rounded-xl" title="Back to list">
                                        <i class="fa-solid fa-list"></i>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Company Description</label>
                    <textarea name="description" x-model="activeCompany.description" rows="2" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500"></textarea>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg">Update Company</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function companyManager() {
    return {
        addModalOpen: false,
        editModalOpen: false,
        activeCompany: {},
        selectedCountry: 'Pakistan',
        selectedCity: 'Lahore',
        isCustomAddCity: false,
        isCustomEditCity: false,
        addLogoPreview: null,
        addLogoFileName: '',
        editLogoPreview: null,
        editLogoFileName: '',
        removeEditLogoFlag: false,

        handleAddLogoUpload(event) {
            const file = event.target.files[0];
            if (file) {
                this.addLogoFileName = file.name;
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.addLogoPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        clearAddLogo() {
            this.addLogoPreview = null;
            this.addLogoFileName = '';
            const input = document.getElementById('add-company-logo-input');
            if (input) input.value = '';
        },

        handleEditLogoUpload(event) {
            const file = event.target.files[0];
            if (file) {
                this.editLogoFileName = file.name;
                this.removeEditLogoFlag = false;
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.editLogoPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        clearEditLogo() {
            this.editLogoPreview = null;
            this.editLogoFileName = '';
            this.removeEditLogoFlag = true;
            const input = document.getElementById('edit-company-logo-input');
            if (input) input.value = '';
        },

        restoreEditLogo() {
            this.removeEditLogoFlag = false;
            this.editLogoFileName = '';
        },

        countriesData: {
            'Pakistan': [
                'Lahore', 'Karachi', 'Islamabad', 'Rawalpindi', 'Faisalabad', 
                'Multan', 'Peshawar', 'Quetta', 'Sialkot', 'Gujranwala', 
                'Hyderabad', 'Abbottabad', 'Bahawalpur', 'Sargodha', 'Sukkur', 
                'Mardan', 'Gujrat', 'Sheikhupura', 'Sahiwal', 'Rahim Yar Khan', 
                'Wah Cantt', 'Muzaffarabad', 'Mirpur', 'Dera Ghazi Khan', 'Jhelum',
                'Kasur', 'Okara', 'Chiniot', 'Kamoke', 'Hafizabad', 'Mingora', 'Nawabshah'
            ],
            'United States': [
                'New York', 'San Francisco', 'Los Angeles', 'Chicago', 'Austin', 
                'Seattle', 'Boston', 'Denver', 'Dallas', 'Atlanta', 
                'Miami', 'Washington D.C.', 'San Jose', 'Houston', 'San Diego', 
                'San Antonio', 'Phoenix', 'Philadelphia'
            ],
            'United Kingdom': [
                'London', 'Manchester', 'Birmingham', 'Edinburgh', 'Glasgow', 
                'Leeds', 'Bristol', 'Cambridge', 'Oxford', 'Liverpool', 
                'Belfast', 'Cardiff', 'Newcastle', 'Sheffield'
            ],
            'United Arab Emirates': [
                'Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Ras Al Khaimah', 
                'Fujairah', 'Al Ain', 'Umm Al Quwain'
            ],
            'Saudi Arabia': [
                'Riyadh', 'Jeddah', 'Dammam', 'Mecca', 'Medina', 
                'Khobar', 'Tabuk', 'Jubail', 'Dhahran'
            ],
            'Canada': [
                'Toronto', 'Vancouver', 'Montreal', 'Calgary', 'Ottawa', 
                'Edmonton', 'Waterloo', 'Mississauga', 'Quebec City', 'Winnipeg'
            ],
            'Germany': [
                'Berlin', 'Munich', 'Frankfurt', 'Hamburg', 'Cologne', 
                'Stuttgart', 'Düsseldorf', 'Leipzig', 'Dresden'
            ],
            'Australia': [
                'Sydney', 'Melbourne', 'Brisbane', 'Perth', 'Adelaide', 
                'Canberra', 'Gold Coast', 'Hobart'
            ],
            'Singapore': [
                'Singapore'
            ],
            'Qatar': [
                'Doha', 'Al Wakrah', 'Al Khor', 'Al Rayyan'
            ],
            'Kuwait': [
                'Kuwait City', 'Al Ahmadi', 'Hawalli', 'Salmiya'
            ],
            'Bahrain': [
                'Manama', 'Riffa', 'Muharraq'
            ],
            'Oman': [
                'Muscat', 'Salalah', 'Sohar', 'Nizwa'
            ],
            'Turkey': [
                'Istanbul', 'Ankara', 'Izmir', 'Antalya', 'Bursa', 'Adana', 'Gaziantep'
            ],
            'Malaysia': [
                'Kuala Lumpur', 'George Town', 'Johor Bahru', 'Cyberjaya', 'Petaling Jaya', 'Shah Alam'
            ],
            'Netherlands': [
                'Amsterdam', 'Rotterdam', 'The Hague', 'Utrecht', 'Eindhoven', 'Groningen'
            ],
            'Ireland': [
                'Dublin', 'Cork', 'Galway', 'Limerick', 'Waterford'
            ],
            'France': [
                'Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nice', 'Nantes', 'Bordeaux'
            ],
            'Spain': [
                'Madrid', 'Barcelona', 'Valencia', 'Seville', 'Zaragoza', 'Malaga'
            ],
            'Italy': [
                'Rome', 'Milan', 'Naples', 'Turin', 'Florence', 'Bologna'
            ],
            'Switzerland': [
                'Zurich', 'Geneva', 'Basel', 'Lausanne', 'Bern'
            ],
            'Sweden': [
                'Stockholm', 'Gothenburg', 'Malmö', 'Uppsala'
            ],
            'China': [
                'Beijing', 'Shanghai', 'Shenzhen', 'Guangzhou', 'Hangzhou', 'Chengdu', 'Wuhan'
            ],
            'Japan': [
                'Tokyo', 'Osaka', 'Kyoto', 'Yokohama', 'Fukuoka', 'Nagoya', 'Sapporo'
            ],
            'India': [
                'Bengaluru', 'Mumbai', 'Delhi', 'Hyderabad', 'Pune', 
                'Chennai', 'Gurugram', 'Noida', 'Kolkata', 'Ahmedabad'
            ],
            'Bangladesh': [
                'Dhaka', 'Chittagong', 'Sylhet', 'Khulna', 'Rajshahi'
            ],
            'Sri Lanka': [
                'Colombo', 'Kandy', 'Galle', 'Negombo'
            ],
            'Egypt': [
                'Cairo', 'Alexandria', 'Giza', 'Port Said'
            ],
            'South Africa': [
                'Johannesburg', 'Cape Town', 'Durban', 'Pretoria'
            ],
            'Other': []
        },

        get countryList() {
            return Object.keys(this.countriesData);
        },

        get availableCities() {
            return this.countriesData[this.selectedCountry] || [];
        },

        openAddModal() {
            this.selectedCountry = 'Pakistan';
            this.selectedCity = 'Lahore';
            this.isCustomAddCity = false;
            this.addLogoPreview = null;
            this.addLogoFileName = '';
            const input = document.getElementById('add-company-logo-input');
            if (input) input.value = '';
            this.addModalOpen = true;
        },

        onCountryChange() {
            const cities = this.availableCities;
            if (cities.length > 0) {
                this.selectedCity = cities[0];
                this.isCustomAddCity = false;
            } else {
                this.selectedCity = '';
                this.isCustomAddCity = true;
            }
        },

        onCitySelectChange() {
            if (this.selectedCity === '__custom__') {
                this.isCustomAddCity = true;
                this.selectedCity = '';
            } else {
                this.isCustomAddCity = false;
            }
        },

        openEditModal(company) {
            this.activeCompany = JSON.parse(JSON.stringify(company));
            this.editLogoPreview = null;
            this.editLogoFileName = '';
            this.removeEditLogoFlag = false;
            const input = document.getElementById('edit-company-logo-input');
            if (input) input.value = '';

            if (!this.activeCompany.country) {
                this.activeCompany.country = 'Pakistan';
            }
            const cities = this.countriesData[this.activeCompany.country] || [];
            if (!this.activeCompany.city && cities.length > 0) {
                this.activeCompany.city = cities[0];
                this.isCustomEditCity = false;
            } else if (cities.length > 0 && !cities.includes(this.activeCompany.city)) {
                this.isCustomEditCity = true;
            } else if (cities.length === 0) {
                this.isCustomEditCity = true;
            } else {
                this.isCustomEditCity = false;
            }
            this.editModalOpen = true;
        },

        get editAvailableCities() {
            return this.countriesData[this.activeCompany.country] || [];
        },

        onEditCountryChange() {
            const cities = this.editAvailableCities;
            if (cities.length > 0) {
                this.activeCompany.city = cities[0];
                this.isCustomEditCity = false;
            } else {
                this.activeCompany.city = '';
                this.isCustomEditCity = true;
            }
        },

        onEditCitySelectChange() {
            if (this.activeCompany.city === '__custom__') {
                this.isCustomEditCity = true;
                this.activeCompany.city = '';
            } else {
                this.isCustomEditCity = false;
            }
        }
    };
}
</script>

@endsection
