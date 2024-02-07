@extends('beautymail::templates.widgets')

@section('content')

@include('beautymail::templates.widgets.articleStart')

<h4 class="secondary"><strong>New Applications</strong></h4>

<?php
$notificationLogApplications = $notificationLog->notificationLogApplications;
?>

<p>Applications: {{ $notificationLogApplications->count() }}</p>
@include('beautymail::templates.widgets.articleEnd')

@foreach ($notificationLogApplications as $notificationLogApplication)
@include('beautymail::templates.widgets.newfeatureStart')

<p>
    <a href="{{ route('application.show', $notificationLogApplication->application->uuid) }}">
        {{ $notificationLogApplication->application->project->title }}
    </a>
    in
    <a href="{{ route('round.show', $notificationLogApplication->application->round->uuid) }}">
        {{ $notificationLogApplication->application->round->name }}
    </a> (status: {{ $notificationLogApplication->application->status }})
</p>

@include('beautymail::templates.widgets.newfeatureEnd')
@endforeach

@stop