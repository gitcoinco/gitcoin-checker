@extends('public')

@section('title')
Gitcoin: Gross Measurable Value
@endsection


@section('content')
<div class="container-fluid bg-light ml-0 mr-0 pl-0 pr-0">
    <div class="container py-3 ml-0 mr-0 pl-0 pr-0">
        <div class="card mb-3">
            <div class="card-body">
                @include('public.breadcrumb')

                <?php
                foreach ($totalGMV as $key => $value) {
                    echo $key . ": $" . number_format($value, 2) . "<br>";
                }
                ?>

            </div>
        </div>
    </div>
</div>
@endsection