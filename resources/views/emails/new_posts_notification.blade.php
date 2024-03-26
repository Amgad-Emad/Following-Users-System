<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>all posts</title>
</head>

<body>
    <h1>{{ $user->name }} posts</h1>
    @php
    $counter = 0

@endphp
    @foreach ($posts as $post)
    post number {{$counter+=1}}
    <br>
        <strong> {{ $post->body }}</strong>
        <br>
    @endforeach

</body>

</html>
