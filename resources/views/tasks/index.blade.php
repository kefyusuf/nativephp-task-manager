@extends('layouts.app')

@section('content')
    @isset($lastTask)
        @if ($lastTask)
            <p class="hint">
                🔖 Last opened task:
                <a href="{{ route('tasks.edit', $lastTask) }}">{{ $lastTask->title }}</a>
                &mdash; stored with the <code>Settings</code> API.
            </p>
        @endif
    @endisset

    @include('tasks._form', ['task' => null])

    <h2 style="margin-bottom: 12px;">
        Tasks ({{ $tasks->count() }})
        <a href="#" style="font-size: 13px; font-weight: 400; margin-left: 8px;"
           onclick="event.preventDefault(); if (confirm('Delete all 📡 live feed tasks?')) document.getElementById('clear-feed-form').submit();">🧹 clear live feed</a>
    </h2>

    <form id="clear-feed-form" method="POST" action="{{ route('tasks.clearFeed') }}" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <form method="GET" action="{{ route('tasks.index') }}" class="card filter-bar">
        <label>Status
            <select name="status">
                <option value="">All</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
        </label>
        <label>Priority
            <select name="priority">
                <option value="">All</option>
                @foreach ($priorities as $priority)
                    <option value="{{ $priority->value }}" @selected(($filters['priority'] ?? null) === $priority->value)>
                        {{ $priority->label() }}
                    </option>
                @endforeach
            </select>
        </label>
        <label class="filter-check">
            <input type="checkbox" name="overdue" value="1" @checked($filters['overdue'] ?? false)>
            Overdue only
        </label>
        <div class="filter-actions">
            <button type="submit">Filter</button>
            <a href="{{ route('tasks.index') }}">Clear</a>
        </div>
    </form>

    <div id="tasks-list">
        @include('tasks._list')
    </div>
@endsection
