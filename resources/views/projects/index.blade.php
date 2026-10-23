@extends('layouts.app')

@section('title', 'Project & Task Management (ClickUp Style)')

@section('content')

<div x-data="projectManager()" class="space-y-6 lg:space-y-8">

    <!-- Header Banner -->
    <div class="p-6 lg:p-8 rounded-3xl bg-gradient-to-r from-blue-900/60 via-slate-900 to-slate-900 border border-blue-500/20 relative overflow-hidden shadow-2xl mb-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/30 text-blue-400 text-xs font-semibold uppercase tracking-wider mb-2">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Software House Task & Sprint Hub</span>
                </span>
                <h1 class="text-2xl lg:text-3xl font-extrabold text-white tracking-tight">Web & Design Project Management</h1>
                <p class="text-sm font-medium text-slate-400 mt-1">ClickUp-style task boards, sprint deliverables, employee progress tracking, and project manager workflows.</p>
            </div>
            <div class="flex items-center space-x-3">
                <button @click="newTaskModalOpen = true" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 transition-all flex items-center space-x-2 shadow-lg">
                    <i class="fa-solid fa-plus text-blue-400"></i>
                    <span>+ New Task</span>
                </button>
                @manager
                <button @click="newProjectModalOpen = true" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-blue-600/30 transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-folder-plus"></i>
                    <span>+ New Project</span>
                </button>
                @endmanager
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex items-center space-x-2 mt-6 pt-4 border-t border-slate-800/80">
            <button @click="activeTab = 'kanban'" :class="activeTab === 'kanban' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'bg-slate-950/60 text-slate-400 hover:text-white border border-slate-800'" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-2">
                <i class="fa-solid fa-table-columns"></i>
                <span>Kanban Board</span>
            </button>
            <button @click="activeTab = 'projects'" :class="activeTab === 'projects' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'bg-slate-950/60 text-slate-400 hover:text-white border border-slate-800'" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-2">
                <i class="fa-solid fa-folder"></i>
                <span>All Projects ({{ $projects->count() }})</span>
            </button>
            @manager
            <button @click="activeTab = 'workload'" :class="activeTab === 'workload' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'bg-slate-950/60 text-slate-400 hover:text-white border border-slate-800'" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-2">
                <i class="fa-solid fa-users-gear"></i>
                <span>Employee Progress & Workload</span>
            </button>
            @endmanager
        </div>
    </div>

    <!-- Quick Stats Bar -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-400">Web Projects</p>
                <i class="fa-solid fa-code text-blue-400"></i>
            </div>
            <h3 class="text-2xl font-black text-white mt-1">{{ $activeWebProjects }} Active</h3>
        </div>
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-400">Design Projects</p>
                <i class="fa-solid fa-palette text-purple-400"></i>
            </div>
            <h3 class="text-2xl font-black text-white mt-1">{{ $activeDesignProjects }} Active</h3>
        </div>
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-400">Total Tasks</p>
                <i class="fa-solid fa-list-check text-cyan-400"></i>
            </div>
            <h3 class="text-2xl font-black text-white mt-1">{{ $totalTasksCount }}</h3>
        </div>
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-400">Completed Deliverables</p>
                <i class="fa-solid fa-circle-check text-emerald-400"></i>
            </div>
            <h3 class="text-2xl font-black text-emerald-400 mt-1">{{ $completedTasksCount }}</h3>
        </div>
    </div>

    <!-- TAB 1: CLICKUP KANBAN BOARD -->
    <div x-show="activeTab === 'kanban'" class="space-y-6">
        <!-- Project Filter Bar -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <form action="{{ route('projects.index') }}" method="GET" class="flex items-center space-x-3">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Filter By Project:</span>
                <select name="project_id" onchange="this.form.submit()" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white">
                    <option value="">All Projects</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" {{ $selectedProjectId == $p->id ? 'selected' : '' }}>
                            {{ $p->name }} ({{ $p->type }})
                        </option>
                    @endforeach
                </select>
            </form>
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-400">
                <span class="inline-flex items-center space-x-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span><span>Urgent</span></span>
                <span class="inline-flex items-center space-x-1 ml-2"><span class="w-2 h-2 rounded-full bg-amber-500"></span><span>High</span></span>
                <span class="inline-flex items-center space-x-1 ml-2"><span class="w-2 h-2 rounded-full bg-blue-500"></span><span>Normal</span></span>
            </div>
        </div>

        <!-- 4-Column Kanban Columns -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            <!-- 1. TO DO COLUMN -->
            <div class="bg-slate-900/50 border border-slate-800 rounded-3xl p-4 flex flex-col min-h-[500px]">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300">To Do</h3>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 text-xs font-black">{{ $todoTasks->count() }}</span>
                </div>

                <div class="space-y-3 flex-1 overflow-y-auto max-h-[600px] pr-1">
                    @forelse($todoTasks as $task)
                        <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-4 shadow-lg hover:border-slate-700 transition-all">
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase {{ $task->priority == 'URGENT' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : ($task->priority == 'HIGH' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-blue-500/10 text-blue-400 border border-blue-500/20') }}">
                                    {{ $task->priority }}
                                </span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-[10px] text-slate-400 font-semibold">{{ $task->category }}</span>
                                    <form action="{{ route('projects.tasks.destroy', $task->id) }}" method="POST" onsubmit="return confirm('Delete this task?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-slate-600 hover:text-rose-400 text-xs"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                </div>
                            </div>

                            <h4 class="text-sm font-bold text-white mb-1">{{ $task->title }}</h4>
                            <p class="text-xs text-slate-400 mb-3 line-clamp-2">{{ $task->description ?: 'No detailed description.' }}</p>

                            <div class="text-[11px] text-slate-400 mb-3 flex items-center justify-between">
                                <span><i class="fa-solid fa-folder text-indigo-400 mr-1"></i> {{ $task->project->name ?? 'General' }}</span>
                                <span><i class="fa-solid fa-clock text-slate-500 mr-1"></i> {{ $task->estimated_hours }}h</span>
                            </div>

                            <div class="pt-3 border-t border-slate-900 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="w-6 h-6 rounded-full bg-indigo-600 flex items-center justify-center text-white text-[10px] font-bold">
                                        {{ strtoupper(substr($task->assignee->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <span class="text-xs text-slate-300 font-medium">{{ $task->assignee->name ?? 'Unassigned' }}</span>
                                </div>

                                <form action="{{ route('projects.tasks.status', $task->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="IN_PROGRESS">
                                    <button type="submit" class="px-2.5 py-1 bg-blue-600/20 hover:bg-blue-600/40 text-blue-400 text-xs font-bold rounded-lg transition-all">
                                        Start →
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-slate-600 text-xs italic">No tasks in To-Do.</div>
                    @endforelse
                </div>
            </div>

            <!-- 2. IN PROGRESS COLUMN -->
            <div class="bg-slate-900/50 border border-slate-800 rounded-3xl p-4 flex flex-col min-h-[500px]">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse"></span>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-blue-400">In Progress</h3>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-400 text-xs font-black">{{ $inProgressTasks->count() }}</span>
                </div>

                <div class="space-y-3 flex-1 overflow-y-auto max-h-[600px] pr-1">
                    @forelse($inProgressTasks as $task)
                        <div class="bg-slate-950/80 border border-blue-500/30 rounded-2xl p-4 shadow-lg hover:border-blue-500/60 transition-all">
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                    IN SPRINT
                                </span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-[10px] text-slate-400 font-semibold">{{ $task->category }}</span>
                                    <form action="{{ route('projects.tasks.destroy', $task->id) }}" method="POST" onsubmit="return confirm('Delete this task?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-slate-600 hover:text-rose-400 text-xs"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                </div>
                            </div>

                            <h4 class="text-sm font-bold text-white mb-1">{{ $task->title }}</h4>
                            <p class="text-xs text-slate-400 mb-3">{{ $task->project->name ?? 'General' }}</p>

                            <!-- Progress Slider Indicator -->
                            <div class="mb-3">
                                <div class="flex items-center justify-between text-[11px] font-semibold text-slate-400 mb-1">
                                    <span>Progress</span>
                                    <span class="text-blue-400 font-bold">{{ $task->progress_percent }}%</span>
                                </div>
                                <div class="w-full bg-slate-900 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $task->progress_percent }}%"></div>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-slate-900 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="w-6 h-6 rounded-full bg-blue-600 flex items-center justify-center text-white text-[10px] font-bold">
                                        {{ strtoupper(substr($task->assignee->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <span class="text-xs text-slate-300 font-medium">{{ $task->assignee->name ?? 'Unassigned' }}</span>
                                </div>

                                <form action="{{ route('projects.tasks.status', $task->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="REVIEW">
                                    <button type="submit" class="px-2.5 py-1 bg-purple-600/20 hover:bg-purple-600/40 text-purple-400 text-xs font-bold rounded-lg transition-all">
                                        Review →
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-slate-600 text-xs italic">No tasks currently in progress.</div>
                    @endforelse
                </div>
            </div>

            <!-- 3. REVIEW COLUMN -->
            <div class="bg-slate-900/50 border border-slate-800 rounded-3xl p-4 flex flex-col min-h-[500px]">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-purple-400">Review / QA</h3>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-400 text-xs font-black">{{ $reviewTasks->count() }}</span>
                </div>

                <div class="space-y-3 flex-1 overflow-y-auto max-h-[600px] pr-1">
                    @forelse($reviewTasks as $task)
                        <div class="bg-slate-950/80 border border-purple-500/30 rounded-2xl p-4 shadow-lg hover:border-purple-500/60 transition-all">
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-purple-500/10 text-purple-400 border border-purple-500/20">
                                    READY FOR QA
                                </span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-[10px] text-slate-400 font-semibold">{{ $task->category }}</span>
                                    <form action="{{ route('projects.tasks.destroy', $task->id) }}" method="POST" onsubmit="return confirm('Delete this task?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-slate-600 hover:text-rose-400 text-xs"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                </div>
                            </div>

                            <h4 class="text-sm font-bold text-white mb-1">{{ $task->title }}</h4>
                            <p class="text-xs text-slate-400 mb-3">{{ $task->project->name ?? 'General' }}</p>

                            <div class="pt-3 border-t border-slate-900 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="w-6 h-6 rounded-full bg-purple-600 flex items-center justify-center text-white text-[10px] font-bold">
                                        {{ strtoupper(substr($task->assignee->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <span class="text-xs text-slate-300 font-medium">{{ $task->assignee->name ?? 'Unassigned' }}</span>
                                </div>

                                <form action="{{ route('projects.tasks.status', $task->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="COMPLETED">
                                    <button type="submit" class="px-2.5 py-1 bg-emerald-600/20 hover:bg-emerald-600/40 text-emerald-400 text-xs font-bold rounded-lg transition-all">
                                        Approve ✓
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-slate-600 text-xs italic">No tasks in review.</div>
                    @endforelse
                </div>
            </div>

            <!-- 4. COMPLETED COLUMN -->
            <div class="bg-slate-900/50 border border-slate-800 rounded-3xl p-4 flex flex-col min-h-[500px]">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-400">Completed</h3>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-black">{{ $completedTasks->count() }}</span>
                </div>

                <div class="space-y-3 flex-1 overflow-y-auto max-h-[600px] pr-1">
                    @forelse($completedTasks as $task)
                        <div class="bg-slate-950/80 border border-emerald-500/20 rounded-2xl p-4 shadow-lg opacity-90 hover:opacity-100 transition-all">
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    DONE ✓
                                </span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-[10px] text-slate-400 font-semibold">{{ $task->category }}</span>
                                    <form action="{{ route('projects.tasks.destroy', $task->id) }}" method="POST" onsubmit="return confirm('Delete this task?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-slate-600 hover:text-rose-400 text-xs"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                </div>
                            </div>

                            <h4 class="text-sm font-bold text-white mb-1 line-through text-slate-400">{{ $task->title }}</h4>
                            <p class="text-xs text-slate-500 mb-3">{{ $task->project->name ?? 'General' }}</p>

                            <div class="pt-3 border-t border-slate-900 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="w-6 h-6 rounded-full bg-emerald-600 flex items-center justify-center text-white text-[10px] font-bold">
                                        {{ strtoupper(substr($task->assignee->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <span class="text-xs text-slate-400 font-medium">{{ $task->assignee->name ?? 'Unassigned' }}</span>
                                </div>
                                <span class="text-[10px] font-bold text-emerald-400">100% Done</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-slate-600 text-xs italic">No completed tasks yet.</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

    <!-- TAB 2: ALL PROJECTS OVERVIEW -->
    <div x-show="activeTab === 'projects'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @forelse($projects as $project)
            <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="px-3 py-1 rounded-full text-xs font-extrabold uppercase {{ $project->type == 'WEB' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/30' : 'bg-purple-500/10 text-purple-400 border border-purple-500/30' }}">
                            <i class="{{ $project->type == 'WEB' ? 'fa-solid fa-code' : 'fa-solid fa-palette' }} mr-1"></i> {{ $project->type }} PROJECT
                        </span>
                        <div class="flex items-center space-x-2">
                            <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-bold uppercase">
                                {{ $project->status }}
                            </span>
                            <button @click="openEditProject({{ json_encode($project) }})" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors" title="Edit Project">
                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                            </button>
                            <form action="{{ route('projects.destroy', $project->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this project and all associated tasks?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-500 hover:text-rose-400 rounded-lg hover:bg-rose-500/10 transition-colors" title="Delete Project">
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <h3 class="text-xl font-bold text-white mb-1">{{ $project->name }}</h3>
                    <p class="text-xs font-semibold text-slate-400 mb-4">Client: {{ $project->client_name ?: 'Internal Client' }}</p>

                    <!-- Overall Project Progress -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between text-xs font-bold mb-1">
                            <span class="text-slate-400">Project Progress</span>
                            <span class="text-blue-400">{{ $project->progress_percent ?: 0 }}% Completed</span>
                        </div>
                        <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden border border-slate-800">
                            <div class="bg-gradient-to-r from-blue-500 to-indigo-500 h-2 rounded-full" style="width: {{ $project->progress_percent ?: 0 }}%"></div>
                        </div>
                    </div>

                    <div class="bg-slate-950/60 rounded-2xl p-4 border border-slate-800/80 mb-4 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400 font-medium">Project Manager:</span>
                            <span class="font-bold text-white">{{ $project->projectManager->name ?? 'Unassigned' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400 font-medium">Assigned Team:</span>
                            <span class="font-bold text-blue-400">{{ $project->members->count() }} Designers & Devs</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400 font-medium">Total Sprints / Tasks:</span>
                            <span class="font-bold text-purple-400">{{ $project->tasks->count() }} Tasks</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        <i class="fa-solid fa-calendar-check mr-1"></i> Due: {{ $project->end_date ? date('d M Y', strtotime($project->end_date)) : 'Ongoing' }}
                    </span>
                    <a href="{{ route('projects.index', ['project_id' => $project->id]) }}" @click="activeTab = 'kanban'" class="text-xs font-bold text-blue-400 hover:text-blue-300">
                        Open Project Kanban →
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-slate-900/70 border border-slate-800 rounded-3xl p-12 text-center text-slate-500">
                No projects found. Click '+ New Project' to create one.
            </div>
        @endforelse
    </div>

    <!-- TAB 3: EMPLOYEE PROGRESS & WORKLOAD TRACKER -->
    <div x-show="activeTab === 'workload'" class="bg-slate-900/70 border border-slate-800 rounded-3xl p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-bold text-white">Employee Task Progress & Workload Tracking</h3>
                <p class="text-xs text-slate-400">Track task completion rates, active work, and productivity across developers and designers.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="text-xs uppercase bg-slate-950/60 text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Employee</th>
                        <th class="py-3 px-4">Role / Department</th>
                        <th class="py-3 px-4 text-center">Active Tasks</th>
                        <th class="py-3 px-4 text-center">Completed</th>
                        <th class="py-3 px-4">Completion Progress</th>
                        <th class="py-3 px-4">Active Sprint Focus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($employeeWorkload as $item)
                        <tr class="hover:bg-slate-800/30 transition-all">
                            <td class="py-4 px-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shadow-md">
                                        {{ strtoupper(substr($item['user']->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-white">{{ $item['user']->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $item['user']->designation->title ?? 'Team Member' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-xs font-semibold text-slate-300">{{ $item['user']->department->name ?? 'Engineering' }}</span>
                                <p class="text-xs text-slate-500">{{ $item['user']->team->name ?? 'Core Team' }}</p>
                            </td>
                            <td class="py-4 px-4 text-center font-bold text-amber-400">
                                {{ $item['active_tasks'] }}
                            </td>
                            <td class="py-4 px-4 text-center font-bold text-emerald-400">
                                {{ $item['completed_tasks'] }}
                            </td>
                            <td class="py-4 px-4 w-48">
                                <div class="flex items-center justify-between text-xs font-bold mb-1">
                                    <span class="text-slate-400">{{ $item['completion_rate'] }}%</span>
                                    <span class="text-slate-500">{{ $item['completed_tasks'] }}/{{ $item['total_tasks'] }}</span>
                                </div>
                                <div class="w-full bg-slate-950 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-gradient-to-r from-blue-500 to-emerald-400 h-1.5 rounded-full" style="width: {{ $item['completion_rate'] }}%"></div>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="space-y-1">
                                    @forelse($item['recent_tasks'] as $t)
                                        <span class="inline-block px-2 py-0.5 rounded-md bg-slate-950 border border-slate-800 text-[11px] text-slate-300">
                                            {{ Str::limit($t->title, 25) }} ({{ $t->status }})
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-500 italic">No tasks currently in progress.</span>
                                    @endforelse
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">No employee records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: New Task (ClickUp Style) -->
    <div x-show="newTaskModalOpen" x-cloak class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="newTaskModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-lg shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-white flex items-center space-x-2">
                    <i class="fa-solid fa-list-check text-blue-400"></i>
                    <span>Assign New Task</span>
                </h3>
                <button @click="newTaskModalOpen = false" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('projects.tasks.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-400 mb-1">Target Project *</label>
                    <select name="project_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white">
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" {{ $selectedProjectId == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->type }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-400 mb-1">Task Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Design Figma Homepage & Navigation" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-400 mb-1">Category</label>
                        <select name="category" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white">
                            <option value="WEB_DEV">Web Development</option>
                            <option value="UI_UX">UI/UX Design</option>
                            <option value="GRAPHIC_DESIGN">Graphic Design</option>
                            <option value="QA">QA & Testing</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-400 mb-1">Priority</label>
                        <select name="priority" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white">
                            <option value="NORMAL">Normal</option>
                            <option value="HIGH">High Priority</option>
                            <option value="URGENT">Urgent Priority</option>
                            <option value="LOW">Low</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-400 mb-1">Assign Employee (Dev/Designer)</label>
                        <select name="user_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white">
                            <option value="">Select Assignee</option>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}">{{ $e->name }} ({{ $e->designation->title ?? 'Staff' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-400 mb-1">Estimated Hours</label>
                        <input type="number" name="estimated_hours" value="8" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-400 mb-1">Task Description</label>
                    <textarea name="description" rows="2" placeholder="Task requirements, acceptance criteria..." class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-blue-500"></textarea>
                </div>
                <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-blue-600/30">
                    Create & Assign Task
                </button>
            </form>
        </div>
    </div>

    <!-- Modal: New Project -->
    <div x-show="newProjectModalOpen" x-cloak class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="newProjectModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-white flex items-center space-x-2">
                    <i class="fa-solid fa-folder-plus text-blue-400"></i>
                    <span>Create New Project</span>
                </h3>
                <button @click="newProjectModalOpen = false" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('projects.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-400 mb-1">Project Name *</label>
                    <input type="text" name="name" required placeholder="e.g. NextGen Web Platform" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-400 mb-1">Client Name</label>
                    <input type="text" name="client_name" placeholder="e.g. Enterprise Client" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-400 mb-1">Category</label>
                        <select name="type" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white">
                            <option value="WEB">Web Project</option>
                            <option value="DESIGN">Design Project</option>
                            <option value="FULL_STACK">Full Stack</option>
                            <option value="MOBILE">Mobile App</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-400 mb-1">Project Manager</label>
                        <select name="project_manager_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white">
                            <option value="">Select PM</option>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}">{{ $e->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-400 mb-1">Project Description</label>
                    <textarea name="description" rows="2" placeholder="Brief scope and deliverables..." class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-blue-500"></textarea>
                </div>
                <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-blue-600/30">
                    Create Project
                </button>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Project -->
    <div x-show="editProjectModalOpen" x-cloak class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="editProjectModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-white flex items-center space-x-2">
                    <i class="fa-solid fa-pen-to-square text-blue-400"></i>
                    <span>Edit Project Details</span>
                </h3>
                <button @click="editProjectModalOpen = false" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form :action="'{{ url('projects') }}/' + activeProj.id" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-semibold text-slate-400 mb-1">Project Name *</label>
                    <input type="text" name="name" x-model="activeProj.name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-400 mb-1">Client Name</label>
                    <input type="text" name="client_name" x-model="activeProj.client_name" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-400 mb-1">Category</label>
                        <select name="type" x-model="activeProj.type" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white">
                            <option value="WEB">Web Project</option>
                            <option value="DESIGN">Design Project</option>
                            <option value="FULL_STACK">Full Stack</option>
                            <option value="MOBILE">Mobile App</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-400 mb-1">Status</label>
                        <select name="status" x-model="activeProj.status" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white">
                            <option value="PLANNING">Planning</option>
                            <option value="IN_PROGRESS">In Progress</option>
                            <option value="IN_REVIEW">In Review</option>
                            <option value="COMPLETED">Completed</option>
                            <option value="ON_HOLD">On Hold</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-400 mb-1">Progress (%)</label>
                        <input type="number" min="0" max="100" name="progress_percent" x-model="activeProj.progress_percent" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-400 mb-1">Project Manager</label>
                        <select name="project_manager_id" x-model="activeProj.project_manager_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white">
                            <option value="">Select PM</option>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}">{{ $e->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-400 mb-1">Project Description</label>
                    <textarea name="description" x-model="activeProj.description" rows="2" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-blue-500"></textarea>
                </div>
                <div class="pt-2 flex justify-end space-x-3">
                    <button type="button" @click="editProjectModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl shadow-lg">Update Project</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function projectManager() {
    return {
        activeTab: 'kanban',
        newTaskModalOpen: false,
        newProjectModalOpen: false,
        editProjectModalOpen: false,
        activeProj: {},
        openEditProject(proj) {
            this.activeProj = JSON.parse(JSON.stringify(proj));
            this.editProjectModalOpen = true;
        }
    };
}
</script>

@endsection
