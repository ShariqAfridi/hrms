@extends('layouts.app')

@section('title', 'My Profile Settings')

@section('content')
<div class="space-y-8" x-data="{
    avatarPreview: '{{ $user->avatar_url ?? '' }}',
    initials: '{{ $user->initials }}',
    uploading: false,
    uploadProgress: 0,
    isDragging: false,
    selectedFileName: '',
    selectedFileSize: '',
    handleFile(file) {
        if (!file) return false;
        if (!file.type.startsWith('image/') && !file.name.match(/\.(png|jpe?g|webp|gif|svg)$/i)) {
            alert('Please select or drop an image file (PNG, JPG, JPEG, WEBP, or SVG).');
            return false;
        }
        if (file.size > 4 * 1024 * 1024) {
            alert('File size exceeds the 4MB limit. Please choose a smaller image.');
            return false;
        }
        this.selectedFileName = file.name;
        this.selectedFileSize = (file.size / 1024).toFixed(1) + ' KB';
        const reader = new FileReader();
        reader.onload = (ev) => {
            this.avatarPreview = ev.target.result;
        };
        reader.readAsDataURL(file);
        return true;
    },
    handleAvatarSelect(e) {
        const file = e.target.files && e.target.files[0];
        if (file) {
            this.handleFile(file);
        }
    },
    handleDrop(e) {
        this.isDragging = false;
        const dt = e.dataTransfer;
        if (dt && dt.files && dt.files.length > 0) {
            const file = dt.files[0];
            const isValid = this.handleFile(file);
            if (isValid) {
                const input = document.getElementById('quickAvatarInput');
                if (input) {
                    try {
                        input.files = dt.files;
                    } catch (err) {
                        try {
                            const newDt = new DataTransfer();
                            newDt.items.add(file);
                            input.files = newDt.files;
                        } catch (e2) {}
                    }
                }
            }
        }
    }
}">
    <!-- Header Banner -->
    <div class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 rounded-3xl p-6 lg:p-8 shadow-2xl">
        <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="flex items-center space-x-5">
                <!-- Large Avatar Preview -->
                <div class="relative group"
                    @dragover.prevent="isDragging = true"
                    @dragenter.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop($event)"
                >
                    <template x-if="avatarPreview">
                        <img :src="avatarPreview" alt="{{ $user->name }}" class="w-20 h-20 rounded-2xl object-cover ring-4 ring-indigo-500/30 shadow-xl shadow-indigo-500/10">
                    </template>
                    <template x-if="!avatarPreview">
                        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-extrabold text-2xl shadow-xl shadow-indigo-500/20 ring-4 ring-indigo-500/30" x-text="initials">
                            {{ $user->initials }}
                        </div>
                    </template>
                    <label for="quickAvatarInput" class="absolute inset-0 bg-black/60 rounded-2xl flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-all cursor-pointer backdrop-blur-xs">
                        <i class="fa-solid fa-camera text-base"></i>
                        <span class="text-[9px] font-bold mt-1 uppercase tracking-wider">Change</span>
                    </label>
                </div>

                <div>
                    <div class="flex items-center space-x-3">
                        <h1 class="text-xl lg:text-2xl font-black text-white tracking-tight">{{ $user->name }}</h1>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold tracking-wider uppercase bg-indigo-500/20 border border-indigo-500/30 text-indigo-300">
                            {{ $user->role->label ?? ($user->isDeveloperAdmin() ? 'Developer' : 'Employee') }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1 flex items-center space-x-2">
                        <i class="fa-solid fa-briefcase text-indigo-400"></i>
                        <span>{{ $user->designation->title ?? 'Team Member' }}</span>
                        <span>•</span>
                        <span>{{ $user->department->name ?? 'General Department' }}</span>
                        <span>•</span>
                        <span>{{ $user->organization->name ?? 'AxeCorp' }}</span>
                    </p>
                    <p class="text-[11px] text-slate-400/80 mt-0.5">
                        <i class="fa-solid fa-envelope text-slate-400 mr-1"></i> {{ $user->email }}
                        @if($user->phone)
                            <span class="mx-1">•</span> <i class="fa-solid fa-phone text-slate-400 mr-1"></i> {{ $user->phone }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <a href="{{ route('dashboard') }}" class="px-4 py-2.5 bg-slate-900/80 hover:bg-slate-800 border border-slate-700/80 rounded-xl text-xs font-bold text-slate-300 transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Active Pending Change Request Alert (If any) -->
    @if(isset($pendingRequest) && $pendingRequest)
    <div class="p-5 rounded-3xl bg-amber-500/10 border border-amber-500/30 text-slate-200 shadow-2xl relative overflow-hidden">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-start space-x-3.5">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0 mt-0.5">
                    <i class="fa-solid fa-clock-rotate-left text-base animate-pulse"></i>
                </div>
                <div class="space-y-1">
                    <div class="flex items-center space-x-2">
                        <h4 class="text-sm font-bold text-white">Bank & National ID Changes Pending System Admin Approval</h4>
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase">
                            Submitted {{ $pendingRequest->created_at->diffForHumans() }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-300">
                        You have submitted a request to modify sensitive identity/banking information. These details will be officially updated once authorized by the System Admin.
                    </p>

                    <!-- Requested Changes Summary Pills -->
                    <div class="pt-2 flex flex-wrap gap-2">
                        @foreach($pendingRequest->requested_changes as $fieldKey => $newVal)
                            @php
                                $fieldLabel = ucwords(str_replace('_', ' ', $fieldKey));
                                if ($fieldKey === 'national_id_number') $fieldLabel = 'National ID / CNIC';
                                if ($fieldKey === 'bank_account_holder_name') $fieldLabel = 'Account Title';
                                if ($fieldKey === 'bank_account_number') $fieldLabel = 'Account Number';
                            @endphp
                            <div class="px-2.5 py-1 rounded-xl bg-slate-950 border border-slate-800 text-[11px] flex items-center space-x-1.5">
                                <span class="text-slate-400 font-semibold">{{ $fieldLabel }}:</span>
                                <span class="text-amber-400 font-bold font-mono">{{ $newVal ?: '(Empty)' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <form action="{{ route('profile.requests.cancel', $pendingRequest->id) }}" method="POST" onsubmit="return confirm('Cancel this pending change request?');" class="shrink-0 self-end md:self-center">
                @csrf
                <button type="submit" class="px-3.5 py-2 bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/30 rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5">
                    <i class="fa-solid fa-xmark"></i>
                    <span>Cancel Request</span>
                </button>
            </form>
        </div>
    </div>
    @endif

    <!-- Main Profile Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Left Column: Profile Picture & Account Card -->
        <div class="space-y-6">
            <!-- Profile Photo Upload Card -->
            <div class="bg-slate-950 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-6">
                <div class="border-b border-slate-800/80 pb-4 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                        <i class="fa-solid fa-image text-indigo-400"></i>
                        <span>Profile Picture</span>
                    </h3>
                    @if($user->avatar)
                    <form action="{{ route('profile.avatar.delete') }}" method="POST" onsubmit="return confirm('Remove your profile photo and reset to initials?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-[11px] font-bold text-rose-400 hover:text-rose-300 transition-colors flex items-center space-x-1">
                            <i class="fa-solid fa-trash-can"></i>
                            <span>Remove</span>
                        </button>
                    </form>
                    @endif
                </div>

                <form action="{{ route('profile.avatar.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4" @submit="if (!selectedFileName && !document.getElementById('quickAvatarInput')?.files?.length) { $event.preventDefault(); alert('Please click or drag a new photo first before saving.'); }">
                    @csrf
                    <div 
                        class="relative flex flex-col items-center justify-center p-6 border-2 border-dashed rounded-2xl transition-all text-center group cursor-pointer overflow-hidden select-none"
                        :class="isDragging ? 'border-indigo-400 bg-indigo-500/20 ring-4 ring-indigo-500/20 scale-[1.01]' : 'border-slate-800 hover:border-indigo-500/50 bg-slate-900/30'"
                        @click="document.getElementById('quickAvatarInput').click()"
                        @dragover.prevent="isDragging = true"
                        @dragenter.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleDrop($event)"
                    >
                        <!-- Drag-Over Visual Overlay -->
                        <div x-show="isDragging" x-transition.opacity class="absolute inset-0 bg-slate-950/90 backdrop-blur-xs flex flex-col items-center justify-center text-white z-20 pointer-events-none">
                            <i class="fa-solid fa-cloud-arrow-up text-3xl text-indigo-400 animate-bounce mb-2"></i>
                            <p class="text-xs font-bold text-indigo-200">Drop your photo here</p>
                            <p class="text-[10px] text-indigo-300/70 mt-0.5">PNG, JPG, JPEG, WEBP or SVG (Max 4MB)</p>
                        </div>

                        <!-- Inner Content (pointer-events-none prevents child dragleave flicker) -->
                        <div class="pointer-events-none flex flex-col items-center justify-center w-full">
                            <template x-if="avatarPreview">
                                <img :src="avatarPreview" class="w-24 h-24 rounded-2xl object-cover ring-2 ring-indigo-500/40 mb-3 shadow-lg">
                            </template>
                            <template x-if="!avatarPreview">
                                <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-black text-3xl mb-3 shadow-lg ring-2 ring-indigo-500/30" x-text="initials">
                                    {{ $user->initials }}
                                </div>
                            </template>
                            <p class="text-xs font-bold text-slate-200 group-hover:text-indigo-400 transition-colors">
                                <span x-show="!isDragging">Click or Drag new photo here</span>
                                <span x-show="isDragging" class="text-indigo-300">Drop to upload photo</span>
                            </p>
                            <p class="text-[10px] text-slate-400 mt-1">PNG, JPG, JPEG, WEBP or SVG (Max 4MB)</p>

                            <!-- Selected File Feedback Chip -->
                            <template x-if="selectedFileName">
                                <div class="mt-3 inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-[10px] font-semibold shadow-xs">
                                    <i class="fa-solid fa-circle-check text-emerald-400"></i>
                                    <span x-text="selectedFileName" class="truncate max-w-[150px]"></span>
                                    <span x-text="'(' + selectedFileSize + ')'" class="text-emerald-400/70 text-[9px] font-mono"></span>
                                </div>
                            </template>
                        </div>

                        <input 
                            type="file" 
                            id="quickAvatarInput" 
                            name="avatar" 
                            accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml,image/*" 
                            class="hidden" 
                            @change="handleAvatarSelect($event)"
                        >
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center space-x-2">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span>Save Profile Photo</span>
                    </button>
                </form>
            </div>

            <!-- Employment & Company Meta -->
            <div class="bg-slate-950 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <div class="border-b border-slate-800/80 pb-3">
                    <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                        <i class="fa-solid fa-id-card text-indigo-400"></i>
                        <span>Corporate Profile</span>
                    </h3>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="flex justify-between py-1.5 border-b border-slate-900">
                        <span class="text-slate-400">Employee ID</span>
                        <span class="font-mono font-bold text-indigo-300">#{{ substr($user->id, 0, 8) }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-900">
                        <span class="text-slate-400">Organization</span>
                        <span class="font-bold text-white">{{ $user->organization->name ?? 'AxeCorp Corporate' }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-900">
                        <span class="text-slate-400">Department</span>
                        <span class="font-bold text-white">{{ $user->department->name ?? 'Not Assigned' }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-900">
                        <span class="text-slate-400">Designation</span>
                        <span class="font-bold text-white">{{ $user->designation->title ?? 'Team Member' }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-900">
                        <span class="text-slate-400">Employment Type</span>
                        <span class="font-bold text-white uppercase">{{ $user->employment_type ?? 'Full Time' }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-900">
                        <span class="text-slate-400">Date of Joining</span>
                        <span class="font-bold text-white">{{ $user->date_of_joining ? (is_string($user->date_of_joining) ? date('d M, Y', strtotime($user->date_of_joining)) : $user->date_of_joining->format('d M, Y')) : 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-900">
                        <span class="text-slate-400">Assigned Team</span>
                        <span class="font-bold text-amber-400">{{ $user->team->name ?? 'No Team Assigned' }}</span>
                    </div>
                    @if($user->team && $user->team->leader)
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-900">
                        <span class="text-slate-400">Team Leader</span>
                        <div class="flex items-center space-x-2 text-right">
                            <span class="font-bold text-indigo-400">{{ $user->team->leader->name }}</span>
                            <span class="px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 text-[9px] font-extrabold uppercase">Lead</span>
                        </div>
                    </div>
                    @endif
                    @if($user->manager)
                    <div class="flex justify-between py-1.5">
                        <span class="text-slate-400">Reporting Line Manager</span>
                        <span class="font-bold text-indigo-400">{{ $user->manager->name }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Two-Tier Approval Protocol Info Card -->
            <div class="p-5 rounded-3xl bg-slate-900/40 border border-slate-800 space-y-3">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-shield-halved text-indigo-400 text-sm"></i>
                    <h4 class="text-xs font-bold text-white uppercase tracking-wider">Security Policy</h4>
                </div>
                <p class="text-[11px] text-slate-400 leading-relaxed">
                    To prevent unauthorized salary redirection, any second modification to your <strong>Bank Account</strong> or <strong>National ID</strong> is locked and requires approval from the <strong>System Admin</strong>.
                </p>
                <div class="pt-1 flex items-center space-x-2 text-[10px] text-slate-500">
                    <i class="fa-solid fa-circle-check text-emerald-400"></i>
                    <span>Status: {{ (int)($user->sensitive_changes_count ?? 0) === 0 ? 'Initial Setup Allowed' : 'System Admin Clearance Active' }}</span>
                </div>
            </div>
        </div>

        <!-- Right Column: Personal Info, Bank Details & Password Settings Form -->
        <div class="lg:col-span-3 space-y-6">
            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Personal Information -->
                <div class="bg-slate-950 border border-slate-800 rounded-3xl p-6 lg:p-8 shadow-xl space-y-6">
                    <div class="border-b border-slate-800/80 pb-4">
                        <h3 class="text-base font-bold text-white flex items-center space-x-2">
                            <i class="fa-solid fa-user-pen text-indigo-400"></i>
                            <span>Personal Details</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">Manage your identity, personal contact details, and address.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-300">Full Legal Name *</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-300">Official Email Address</label>
                            <input type="email" value="{{ $user->email }}" disabled class="w-full px-4 py-2.5 bg-slate-900/50 border border-slate-800/60 rounded-xl text-xs text-slate-400 cursor-not-allowed">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-300">Contact Phone Number</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+1 (555) 019-2834" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-300">National CNIC / ID Number</label>
                                @if((int)($user->sensitive_changes_count ?? 0) >= 1 && !$user->isSuperAdmin() && !$user->isDeveloperAdmin())
                                    <span class="text-[10px] text-amber-400 font-semibold flex items-center space-x-1">
                                        <i class="fa-solid fa-lock text-[9px]"></i>
                                        <span>Approval Required</span>
                                    </span>
                                @endif
                            </div>
                            <input type="text" name="national_id_number"
                                   value="{{ old('national_id_number', $user->national_id_number) }}"
                                   oninput="this.value = (function(val){ if(!val) return ''; const d=String(val).replace(/\D/g,'').slice(0,13); if(d.length<=5) return d; if(d.length<=12) return d.slice(0,5)+'-'+d.slice(5); return d.slice(0,5)+'-'+d.slice(5,12)+'-'+d.slice(12,13); })(this.value)"
                                   maxlength="15"
                                   pattern="^\d{5}-\d{7}-\d{1}$"
                                   title="CNIC must follow the 13-digit pattern: XXXXX-XXXXXXX-X (e.g. 42101-1234567-1)"
                                   placeholder="XXXXX-XXXXXXX-X (e.g. 42101-1234567-1)"
                                   class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white font-mono tracking-wider focus:outline-none focus:border-indigo-500">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-300">Date of Birth</label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $user->date_of_birth ? (is_string($user->date_of_birth) ? date('Y-m-d', strtotime($user->date_of_birth)) : $user->date_of_birth->format('Y-m-d')) : '') }}" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-300">Gender</label>
                            <select name="gender" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                                <option value="">Select Gender</option>
                                <option value="male" {{ strtolower($user->gender ?? '') === 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ strtolower($user->gender ?? '') === 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ strtolower($user->gender ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-300">Marital Status</label>
                            <select name="marital_status" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                                <option value="">Select Status</option>
                                <option value="Single" {{ ($user->marital_status ?? '') === 'Single' ? 'selected' : '' }}>Single</option>
                                <option value="Married" {{ ($user->marital_status ?? '') === 'Married' ? 'selected' : '' }}>Married</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-300">Blood Group</label>
                            <input type="text" name="blood_group" value="{{ old('blood_group', $user->blood_group) }}" placeholder="e.g. O+, A+, B+" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        </div>

                        <div class="md:col-span-2 space-y-1.5">
                            <label class="block text-xs font-bold text-slate-300">Residential Address</label>
                            <textarea name="address" rows="2" placeholder="Full residential street, area, city..." class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">{{ old('address', $user->address) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Bank & Disbursement Details Card -->
                <div class="bg-slate-950 border border-slate-800 rounded-3xl p-6 lg:p-8 shadow-xl space-y-6">
                    <div class="border-b border-slate-800/80 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h3 class="text-base font-bold text-white flex items-center space-x-2">
                                <i class="fa-solid fa-building-columns text-indigo-400"></i>
                                <span>Bank & Disbursement Details</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-1">Official bank account coordinates used for direct deposit salary transfers & payslips.</p>
                        </div>

                        @if((int)($user->sensitive_changes_count ?? 0) === 0)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <i class="fa-solid fa-circle-check mr-1.5 text-[8px]"></i>
                                First-Time Setup (Direct Update)
                            </span>
                        @elseif($user->isSuperAdmin() || $user->isDeveloperAdmin())
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                <i class="fa-solid fa-shield mr-1.5 text-[8px]"></i>
                                System Admin Direct Access
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                <i class="fa-solid fa-lock mr-1.5 text-[8px]"></i>
                                2nd Change Requires System Admin Approval
                            </span>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-300">Disbursement Method</label>
                            <select name="payment_method" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                                <option value="BANK_TRANSFER" {{ ($user->payment_method ?? 'BANK_TRANSFER') === 'BANK_TRANSFER' ? 'selected' : '' }}>Bank Transfer (Direct Deposit)</option>
                                <option value="CASH" {{ ($user->payment_method ?? '') === 'CASH' ? 'selected' : '' }}>Cash in Hand</option>
                                <option value="CHEQUE" {{ ($user->payment_method ?? '') === 'CHEQUE' ? 'selected' : '' }}>Corporate Cheque</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-300">Bank Name</label>
                            <input type="text" name="bank_name" value="{{ old('bank_name', $user->bank_name) }}" placeholder="e.g. Global Bank / Silicon Valley Bank" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-300">Account Title / Holder Name</label>
                            <input type="text" name="bank_account_holder_name" value="{{ old('bank_account_holder_name', $user->bank_account_holder_name) }}" placeholder="e.g. John Doe" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-300">Bank Account Number / IBAN</label>
                            <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $user->bank_account_number) }}" placeholder="e.g. US1234567890123456" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white font-mono focus:outline-none focus:border-indigo-500">
                        </div>

                        <div class="md:col-span-2 space-y-1.5">
                            <label class="block text-xs font-bold text-slate-300">Bank Branch Name & Code</label>
                            <input type="text" name="bank_branch_name" value="{{ old('bank_branch_name', $user->bank_branch_name) }}" placeholder="e.g. Downtown Metro Branch (Code: 0142)" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        </div>

                        @if((int)($user->sensitive_changes_count ?? 0) >= 1 && !$user->isSuperAdmin() && !$user->isDeveloperAdmin())
                        <div class="md:col-span-2 space-y-1.5">
                            <label class="block text-xs font-bold text-amber-300">Reason for Requesting Bank / Identity Change (Optional)</label>
                            <textarea name="change_reason" rows="2" placeholder="Explain why you are changing your Bank details or National ID (e.g. Switched primary bank account, Updated payroll card)..." class="w-full px-4 py-2 bg-slate-900/80 border border-slate-800 rounded-xl text-xs text-slate-200 focus:outline-none focus:border-amber-500"></textarea>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Emergency Contact Details -->
                <div class="bg-slate-950 border border-slate-800 rounded-3xl p-6 lg:p-8 shadow-xl space-y-6">
                    <div class="border-b border-slate-800/80 pb-4">
                        <h3 class="text-base font-bold text-white flex items-center space-x-2">
                            <i class="fa-solid fa-phone-volume text-rose-400"></i>
                            <span>Emergency Contact</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">Designated person to reach out to in case of workplace emergencies.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-300">Emergency Contact Person</label>
                            <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $user->emergency_contact_name) }}" placeholder="e.g. Jane Doe (Spouse / Relative)" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-300">Emergency Phone Number</label>
                            <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $user->emergency_contact_phone) }}" placeholder="+1 (555) 987-6543" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- Security & Password Change -->
                <div class="bg-slate-950 border border-slate-800 rounded-3xl p-6 lg:p-8 shadow-xl space-y-6">
                    <div class="border-b border-slate-800/80 pb-4">
                        <h3 class="text-base font-bold text-white flex items-center space-x-2">
                            <i class="fa-solid fa-lock text-amber-400"></i>
                            <span>Change Security Password</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">Leave blank if you do not wish to update your login password. (Super Admins can set or update without requiring old password).</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-300">Current Password</label>
                            <input type="password" name="current_password" placeholder="••••••••" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-300">New Password</label>
                            <input type="password" name="new_password" placeholder="Min. 8 characters" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-300">Confirm New Password</label>
                            <input type="password" name="new_password_confirmation" placeholder="Repeat new password" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- Save Action Button -->
                <div class="flex justify-end pt-2">
                    <button type="submit" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-xl shadow-indigo-600/30 transition-all flex items-center space-x-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Save All Profile Changes</span>
                    </button>
                </div>
            </form>

            <!-- Payroll Section Security PIN Card (Super Admin & Developer Only) -->
            @if($user->isSuperAdmin() || $user->isDeveloperAdmin())
            <div id="payroll-pin" class="bg-slate-950 border border-slate-800 rounded-3xl p-6 lg:p-8 shadow-xl space-y-6" x-data="{ showRemoveModal: false }">
                <div class="border-b border-slate-800/80 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                                <i class="fa-solid fa-shield-halved text-sm"></i>
                            </div>
                            <h3 class="text-base font-bold text-white">Payroll Security PIN</h3>
                        </div>
                        <p class="text-xs text-slate-400">Enforce an extra numeric PIN challenge before accessing the confidential Payroll section.</p>
                    </div>

                    <div>
                        @if($user->hasPayrollPin())
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <i class="fa-solid fa-circle-check mr-1.5 text-[9px] animate-pulse"></i>
                                PIN Protection Active
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                <i class="fa-solid fa-triangle-exclamation mr-1.5 text-[9px]"></i>
                                No PIN Configured
                            </span>
                        @endif
                    </div>
                </div>

                @if($errors->has('pin') || $errors->has('pin_confirmation') || $errors->has('current_password') || $errors->has('pin_current_password') || $errors->has('current_pin_or_password'))
                    <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center space-x-2">
                        <i class="fa-solid fa-circle-exclamation shrink-0 text-sm"></i>
                        <span>{{ $errors->first('pin') ?: ($errors->first('pin_confirmation') ?: ($errors->first('pin_current_password') ?: ($errors->first('current_pin_or_password') ?: $errors->first('current_password')))) }}</span>
                    </div>
                @endif

                <div class="p-4 rounded-2xl bg-indigo-950/20 border border-indigo-500/20 text-xs text-slate-300 flex items-start space-x-3">
                    <i class="fa-solid fa-circle-info text-indigo-400 text-sm mt-0.5 shrink-0"></i>
                    <div>
                        <span class="font-bold text-white">How this works:</span> Even if your browser or account remains open/unattended on a shared workstation, accessing the <strong>Payroll</strong> tab will prompt for this 4-digit PIN before displaying salary ledgers and remuneration figures.
                    </div>
                </div>

                <form action="{{ route('profile.payroll-pin.update') }}" method="POST" autocomplete="off" class="space-y-5">
                    @csrf
                    @if(!$user->hasPayrollPin())
                        <!-- First-Time PIN Setup (No password requirement needed) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-300">
                                    Set Security PIN
                                    <span class="text-rose-400">*</span>
                                </label>
                                <input 
                                    type="password" 
                                    name="pin" 
                                    maxlength="4" 
                                    pattern="[0-9]{4}" 
                                    inputmode="numeric" 
                                    placeholder="4 digits (e.g. 1234)" 
                                    required 
                                    autocomplete="new-password"
                                    class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white tracking-widest font-mono focus:outline-none focus:border-indigo-500"
                                >
                                <span class="text-[10px] text-slate-500">Exactly 4 numeric digits (0–9)</span>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-300">
                                    Confirm PIN
                                    <span class="text-rose-400">*</span>
                                </label>
                                <input 
                                    type="password" 
                                    name="pin_confirmation" 
                                    maxlength="4" 
                                    pattern="[0-9]{4}" 
                                    inputmode="numeric" 
                                    placeholder="Re-enter 4 digits" 
                                    required 
                                    autocomplete="new-password"
                                    class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white tracking-widest font-mono focus:outline-none focus:border-indigo-500"
                                >
                                <span class="text-[10px] text-slate-500">Must match 4-digit PIN</span>
                            </div>
                        </div>
                    @else
                        <!-- Update / Reset Existing PIN -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-300">
                                    New Security PIN
                                    <span class="text-rose-400">*</span>
                                </label>
                                <input 
                                    type="password" 
                                    name="pin" 
                                    maxlength="4" 
                                    pattern="[0-9]{4}" 
                                    inputmode="numeric" 
                                    placeholder="4 digits (e.g. 1234)" 
                                    required 
                                    autocomplete="new-password"
                                    class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white tracking-widest font-mono focus:outline-none focus:border-indigo-500"
                                >
                                <span class="text-[10px] text-slate-500">Exactly 4 numeric digits (0-9)</span>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-300">
                                    Confirm New PIN
                                    <span class="text-rose-400">*</span>
                                </label>
                                <input 
                                    type="password" 
                                    name="pin_confirmation" 
                                    maxlength="4" 
                                    pattern="[0-9]{4}" 
                                    inputmode="numeric" 
                                    placeholder="Re-enter 4 digits" 
                                    required 
                                    autocomplete="new-password"
                                    class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white tracking-widest font-mono focus:outline-none focus:border-indigo-500"
                                >
                                <span class="text-[10px] text-slate-500">Must match 4-digit PIN</span>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-300">
                                    Verification <span class="text-slate-500 font-normal">(Optional)</span>
                                </label>
                                <input 
                                    type="password" 
                                    name="current_pin_or_password" 
                                    placeholder="Current PIN or Password" 
                                    autocomplete="new-password"
                                    class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500"
                                >
                                <span class="text-[10px] text-slate-500">Leave blank to reset directly as Super Admin</span>
                            </div>
                        </div>
                    @endif

                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
                        <div>
                            @if($user->hasPayrollPin())
                                <button 
                                    type="button" 
                                    @click="showRemoveModal = true" 
                                    class="text-xs font-bold text-rose-400 hover:text-rose-300 transition-colors flex items-center space-x-1.5"
                                >
                                    <i class="fa-solid fa-trash-can"></i>
                                    <span>Disable / Remove PIN</span>
                                </button>
                            @endif
                        </div>

                        <button 
                            type="submit" 
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center space-x-2"
                        >
                            <i class="fa-solid fa-key"></i>
                            <span>{{ $user->hasPayrollPin() ? 'Update / Reset Security PIN' : 'Activate Payroll PIN' }}</span>
                        </button>
                    </div>
                </form>

                <!-- Modal to Remove PIN -->
                @if($user->hasPayrollPin())
                <div 
                    x-show="showRemoveModal" 
                    x-cloak 
                    class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                >
                    <div 
                        @click.outside="showRemoveModal = false" 
                        class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-5"
                    >
                        <div class="flex items-center space-x-3 text-rose-400">
                            <div class="w-10 h-10 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center">
                                <i class="fa-solid fa-lock-open text-lg"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-white">Disable Payroll PIN?</h4>
                                <p class="text-xs text-slate-400">Payroll tab will open without any PIN prompt.</p>
                            </div>
                        </div>

                        <form action="{{ route('profile.payroll-pin.update') }}" method="POST" autocomplete="off" class="space-y-4">
                            @csrf
                            <input type="hidden" name="action" value="remove">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-300">Confirm Current PIN or Account Password</label>
                                <input 
                                    type="password" 
                                    name="current_pin_or_password" 
                                    placeholder="Enter current PIN or login password" 
                                    autocomplete="new-password"
                                    class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-rose-500"
                                >
                                <span class="text-[10px] text-slate-500">Enter your 4-digit PIN or account password to confirm removal</span>
                            </div>

                            <div class="flex items-center justify-end space-x-3 pt-2">
                                <button 
                                    type="button" 
                                    @click="showRemoveModal = false" 
                                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold rounded-xl transition-all"
                                >
                                    Cancel
                                </button>
                                <button 
                                    type="submit" 
                                    class="px-5 py-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-rose-600/30 transition-all flex items-center space-x-1.5"
                                >
                                    <i class="fa-solid fa-trash-can"></i>
                                    <span>Confirm Remove PIN</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
