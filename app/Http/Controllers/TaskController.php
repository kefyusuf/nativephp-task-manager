<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Events\TasksChanged;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Native\Desktop\Dialog;
use Native\Desktop\Facades\Clipboard;
use Native\Desktop\Facades\Notification;
use Native\Desktop\Facades\Settings;

class TaskController extends Controller
{
    /**
     * Task list + new task form.
     */
    public function index(Request $request)
    {
        $lastTaskId = Settings::get('last_opened_task');
        $filters = $this->filtersFrom($request);

        return view('tasks.index', [
            'tasks' => $this->filteredTasks($filters),
            'lastTask' => $lastTaskId ? Task::find($lastTaskId) : null,
            'filters' => $filters,
            'statuses' => TaskStatus::cases(),
            'priorities' => TaskPriority::cases(),
        ]);
    }

    public function create(Request $request)
    {
        return view('tasks.create', [
            'task' => null,
            'quick' => $request->boolean('quick'),
            'statuses' => TaskStatus::cases(),
            'priorities' => TaskPriority::cases(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $task = Task::create($data);

        Notification::title('Task saved')
            ->message($task->title)
            ->show();

        event(new TasksChanged('created', $task->title));

        if ($request->boolean('quick')) {
            return redirect()->route('tasks.create', ['quick' => 1]);
        }

        return redirect()->route('tasks.index');
    }

    public function edit(Task $task)
    {
        Settings::set('last_opened_task', $task->id);

        return view('tasks.edit', [
            'task' => $task,
            'statuses' => TaskStatus::cases(),
            'priorities' => TaskPriority::cases(),
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $data = $this->validated($request);

        $task->update($data);

        Notification::title('Task updated')
            ->message($task->title)
            ->show();

        event(new TasksChanged('updated', $task->title));

        return redirect()->route('tasks.index');
    }

    public function destroy(Task $task)
    {
        $task->delete();

        event(new TasksChanged('deleted', $task->title));

        return redirect()->route('tasks.index');
    }

    /**
     * List partial for live updates.
     */
    public function fragment(Request $request)
    {
        $filters = $this->filtersFrom($request);

        return view('tasks._list', [
            'tasks' => $this->filteredTasks($filters),
            'filters' => $filters,
        ]);
    }

    /**
     * Delete all live-feed tasks (titles starting with 📡).
     */
    public function clearFeed()
    {
        $count = Task::where('title', 'like', '📡%')->delete();

        Notification::title('Live feed cleared 🧹')
            ->message($count.' tasks deleted')
            ->show();

        event(new TasksChanged('deleted', $count.' live tasks'));

        return back();
    }

    /**
     * Copy the task text to the system clipboard.
     */
    public function copy(Task $task)
    {
        Clipboard::text($task->body ?: $task->title);

        Notification::title('Copied to clipboard 📋')
            ->message($task->title)
            ->show();

        return back();
    }

    /**
     * Open a native save dialog; PHP writes the file.
     */
    public function export(Task $task)
    {
        $path = Dialog::new()
            ->title('Save task as .txt')
            ->filter('Text file', ['txt'])
            ->save();

        if (! $path) {
            return back();
        }

        $lines = [
            $task->title,
            str_repeat('=', mb_strlen($task->title)),
            '',
            'Status: '.$task->status->label(),
            'Priority: '.$task->priority->label(),
            'Due date: '.($task->due_date?->format('Y-m-d') ?? '—'),
            '',
            $task->body ?? '',
        ];

        file_put_contents($path, implode("\n", $lines));

        Notification::title('Exported ⬇')
            ->message(basename($path))
            ->show();

        return back();
    }

    /**
     * @return array{status: ?string, priority: ?string, overdue: bool}
     */
    private function filtersFrom(Request $request): array
    {
        $status = $request->query('status');
        $priority = $request->query('priority');

        return [
            'status' => TaskStatus::tryFrom((string) $status)?->value,
            'priority' => TaskPriority::tryFrom((string) $priority)?->value,
            'overdue' => $request->boolean('overdue'),
        ];
    }

    /**
     * @param  array{status: ?string, priority: ?string, overdue: bool}  $filters
     */
    private function filteredTasks(array $filters)
    {
        return Task::query()
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['priority'], fn ($query, $priority) => $query->where('priority', $priority))
            ->when($filters['overdue'], fn ($query) => $query
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->where('status', '!=', TaskStatus::Done->value))
            ->latest()
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'due_date' => ['nullable', 'date'],
        ]);
    }
}
