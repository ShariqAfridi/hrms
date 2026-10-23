<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectWebController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::with(['organization', 'projectManager', 'members', 'tasks.assignee'])
            ->orderBy('created_at', 'desc')
            ->get();

        $employees = User::excludeDevs()->with(['department', 'designation', 'team'])->where('is_active', true)->get();
        $organizations = Organization::all();

        // Tasks for Kanban Board
        $selectedProjectId = $request->input('project_id');
        $taskQuery = Task::with(['project', 'assignee']);

        if ($selectedProjectId) {
            $taskQuery->where('project_id', $selectedProjectId);
        }

        $allTasks = $taskQuery->orderBy('due_date', 'asc')->get();

        $todoTasks = $allTasks->where('status', 'TO_DO');
        $inProgressTasks = $allTasks->where('status', 'IN_PROGRESS');
        $reviewTasks = $allTasks->where('status', 'REVIEW');
        $completedTasks = $allTasks->where('status', 'COMPLETED');

        // Summary Statistics
        $activeWebProjects = $projects->where('type', 'WEB')->where('status', 'IN_PROGRESS')->count();
        $activeDesignProjects = $projects->where('type', 'DESIGN')->where('status', 'IN_PROGRESS')->count();
        $totalTasksCount = Task::count();
        $completedTasksCount = Task::where('status', 'COMPLETED')->count();

        // Employee Workload & Progress Tracking (ClickUp Style)
        $employeeWorkload = $employees->map(function ($emp) use ($allTasks) {
            $empTasks = Task::where('user_id', $emp->id)->get();
            $total = count($empTasks);
            $done = $empTasks->where('status', 'COMPLETED')->count();
            $active = $empTasks->whereIn('status', ['TO_DO', 'IN_PROGRESS', 'REVIEW'])->count();
            $completionRate = $total > 0 ? round(($done / $total) * 100) : 0;

            return [
                'user' => $emp,
                'total_tasks' => $total,
                'active_tasks' => $active,
                'completed_tasks' => $done,
                'completion_rate' => $completionRate,
                'recent_tasks' => $empTasks->whereIn('status', ['IN_PROGRESS', 'REVIEW'])->take(3),
            ];
        })->filter(function ($item) {
            return $item['total_tasks'] > 0 || in_array($item['user']->department->name ?? '', ['Software Engineering & Design', 'Software Engineering', 'UI/UX Design', 'Design', 'QA & Testing', 'Engineering']);
        });

        return view('projects.index', compact(
            'projects',
            'employees',
            'organizations',
            'selectedProjectId',
            'todoTasks',
            'inProgressTasks',
            'reviewTasks',
            'completedTasks',
            'activeWebProjects',
            'activeDesignProjects',
            'totalTasksCount',
            'completedTasksCount',
            'employeeWorkload'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:WEB,DESIGN,FULL_STACK,MOBILE',
            'client_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'project_manager_id' => 'nullable',
            'end_date' => 'nullable|date',
        ]);

        $orgId = $request->input('organization_id') ?: Organization::first()?->id;

        $project = Project::create([
            'organization_id' => $orgId,
            'name' => $request->input('name'),
            'type' => $request->input('type'),
            'client_name' => $request->input('client_name'),
            'description' => $request->input('description'),
            'project_manager_id' => $request->filled('project_manager_id') ? $request->input('project_manager_id') : null,
            'status' => 'IN_PROGRESS',
            'progress_percent' => 0,
            'start_date' => now(),
            'end_date' => $request->input('end_date'),
        ]);

        if ($request->filled('members')) {
            $project->members()->sync($request->input('members'));
        }

        return redirect()->back()->with('success', "Project '{$project->name}' created successfully.");
    }

    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:WEB,DESIGN,FULL_STACK,MOBILE',
            'client_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|in:PLANNING,IN_PROGRESS,IN_REVIEW,COMPLETED,ON_HOLD',
            'progress_percent' => 'nullable|integer|min:0|max:100',
            'project_manager_id' => 'nullable',
            'end_date' => 'nullable|date',
        ]);

        $validated['project_manager_id'] = $request->filled('project_manager_id') ? $request->input('project_manager_id') : null;

        $project->update($validated);

        return redirect()->back()->with('success', "Project '{$project->name}' updated successfully.");
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        $project->delete();

        return redirect()->back()->with('success', 'Project deleted successfully.');
    }

    public function storeTask(Request $request)
    {
        $request->validate([
            'project_id' => 'required',
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|in:WEB_DEV,UI_UX,GRAPHIC_DESIGN,QA',
            'priority' => 'nullable|string|in:URGENT,HIGH,NORMAL,LOW',
            'user_id' => 'nullable',
            'estimated_hours' => 'nullable|integer|min:1',
            'due_date' => 'nullable|date',
        ]);

        $task = Task::create([
            'project_id' => $request->input('project_id'),
            'user_id' => $request->filled('user_id') ? $request->input('user_id') : null,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'category' => $request->input('category', 'WEB_DEV'),
            'priority' => $request->input('priority', 'NORMAL'),
            'status' => 'TO_DO',
            'progress_percent' => 0,
            'estimated_hours' => $request->input('estimated_hours', 8),
            'due_date' => $request->input('due_date'),
        ]);

        return redirect()->back()->with('success', "Task '{$task->title}' assigned successfully.");
    }

    public function updateTaskStatus(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $newStatus = $request->input('status');
        
        $progress = $task->progress_percent;
        if ($newStatus === 'COMPLETED') {
            $progress = 100;
        } elseif ($newStatus === 'TO_DO') {
            $progress = 0;
        } elseif ($newStatus === 'IN_PROGRESS' && $progress == 0) {
            $progress = 50;
        }

        $task->update([
            'status' => $newStatus,
            'progress_percent' => $progress,
        ]);

        // Auto-update project overall progress percentage
        $project = $task->project;
        if ($project) {
            $totalProjectTasks = $project->tasks()->count();
            $completedProjectTasks = $project->tasks()->where('status', 'COMPLETED')->count();
            $projPercent = $totalProjectTasks > 0 ? round(($completedProjectTasks / $totalProjectTasks) * 100) : 0;
            $project->update(['progress_percent' => $projPercent]);
        }

        return redirect()->back()->with('success', "Task status updated to {$newStatus}.");
    }

    public function updateProgress(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $percent = min(100, max(0, (int)$request->input('progress_percent', 0)));
        
        $status = $task->status;
        if ($percent == 100) {
            $status = 'COMPLETED';
        } elseif ($percent > 0 && $status == 'TO_DO') {
            $status = 'IN_PROGRESS';
        }

        $task->update([
            'progress_percent' => $percent,
            'status' => $status,
        ]);

        return redirect()->back()->with('success', "Task progress updated to {$percent}%.");
    }

    public function destroyTask($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();

        return redirect()->back()->with('success', 'Task deleted successfully.');
    }
}
