@extends('layouts.app')

@section('title', 'New Task')

@section('content')
    <h2 style="margin-bottom: 12px;">New Task</h2>
    @include('tasks._form', ['task' => null])
@endsection
