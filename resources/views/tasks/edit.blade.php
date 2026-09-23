@extends('layouts.app')

@section('title', 'Edit Task')

@section('content')
    <h2 style="margin-bottom: 12px;">Edit Task</h2>
    @include('tasks._form')
@endsection
