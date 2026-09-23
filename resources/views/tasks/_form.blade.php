{{-- Shared form for create and edit. $task null = create, set = update. --}}
@php
    $statuses = $statuses ?? \App\Enums\TaskStatus::cases();
    $priorities = $priorities ?? \App\Enums\TaskPriority::cases();
@endphp
<form method="POST"
      action="{{ isset($task) && $task ? route('tasks.update', $task) : route('tasks.store') }}"
      class="card">
    @csrf
    @if (isset($task) && $task)
        @method('PUT')
    @endif
    @if (isset($quick) && $quick)
        <input type="hidden" name="quick" value="1">
    @endif

    <label>Title
        <input type="text" name="title" required maxlength="100"
               value="{{ old('title', $task->title ?? '') }}">
    </label>
    @error('title')
        <p class="error">{{ $message }}</p>
    @enderror

    <label>Description
        <textarea name="body" rows="4" maxlength="5000">{{ old('body', $task->body ?? '') }}</textarea>
    </label>
    @error('body')
        <p class="error">{{ $message }}</p>
    @enderror

    <div class="field-row">
        <label>Status
            <select name="status">
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}"
                        @selected(old('status', $task->status->value ?? 'pending') === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
        </label>
        @error('status')
            <p class="error">{{ $message }}</p>
        @enderror

        <label>Priority
            <select name="priority">
                @foreach ($priorities as $priority)
                    <option value="{{ $priority->value }}"
                        @selected(old('priority', $task->priority->value ?? 'medium') === $priority->value)>
                        {{ $priority->label() }}
                    </option>
                @endforeach
            </select>
        </label>
        @error('priority')
            <p class="error">{{ $message }}</p>
        @enderror

        <label>Due date
            <input type="date" name="due_date"
                   value="{{ old('due_date', isset($task) && $task?->due_date ? $task->due_date->format('Y-m-d') : '') }}">
        </label>
        @error('due_date')
            <p class="error">{{ $message }}</p>
        @enderror
    </div>

    <button>{{ isset($task) && $task ? 'Update' : 'Save' }}</button>
</form>
