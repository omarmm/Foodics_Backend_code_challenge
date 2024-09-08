<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Alert for {{ $ingredient->name }}</title>
</head>
<body>
    <h1>Stock Alert!</h1>
    <p>The stock level for <strong>{{ $ingredient->name }}</strong> has dropped below 50%.</p>
    <p>Current stock: {{ $ingredient->stock }} {{ $ingredient->unit }}</p>
    <p>Please restock as soon as possible to avoid shortages.</p>
</body>
</html>
