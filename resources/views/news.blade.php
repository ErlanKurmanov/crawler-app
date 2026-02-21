<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Новости — kaktus.media</title>
    @vite(['resources/js/app.js', 'resources/css/variables.css'])
</head>
<body>
<div id="app"></div>
</body>
</html>
