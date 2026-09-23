{{-- List partial swapped in during live updates. --}}
@forelse ($tasks as $task)
    <article class="card">
        <div class="task-head">
            <h3>{{ $task->title }}</h3>
            <div class="badges">
                <span class="badge badge-{{ $task->status->value }}">{{ $task->status->label() }}</span>
                <span class="badge badge-priority-{{ $task->priority->value }}">{{ $task->priority->label() }}</span>
            </div>
        </div>
        @if ($task->body)
            <p style="margin-top: 6px;">{{ $task->body }}</p>
        @endif
        <p class="meta">
            Saved {{ $task->created_at->format('Y-m-d H:i') }}
            @if ($task->due_date)
                · due <strong>{{ $task->due_date->format('Y-m-d') }}</strong>
            @endif
        </p>
        <div class="actions">
            <a href="{{ route('tasks.edit', $task) }}">Edit</a>
            <a href="{{ route('tasks.copy', $task) }}">📋 Copy</a>
            <a href="{{ route('tasks.export', $task) }}">⬇ .txt</a>
            <form method="POST" action="{{ route('tasks.destroy', $task) }}"
                  onsubmit="return confirm('Delete this task?')">
                @csrf
                @method('DELETE')
                <button>Delete</button>
            </form>
        </div>
    </article>
@empty
    <p class="empty">No tasks yet. Add your first one with the form above! 🎉</p>
@endforelse
