<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario Actual</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 20px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { margin-top: 20px; text-align: right; font-size: 13px; }
        .badge-low { color: #dc2626; font-weight: bold; }
        .badge-ok { color: #16a34a; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Reporte de Inventario Actual</h1>
        <p>Generado el: {{ $generatedAt }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>Producto</th>
                <th>Rubro / Categoría</th>
                <th class="text-right">Precio Unit. ($)</th>
                <th class="text-center">Existencia</th>
                <th class="text-right">Valor Total ($)</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalItems = 0;
                $totalStock = 0;
                $totalValue = 0;
            @endphp
            @foreach ($products as $index => $product)
                @php 
                    $itemValue = $product->price * $product->stock;
                    $totalItems++;
                    $totalStock += $product->stock;
                    $totalValue += $itemValue;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category->name ?? 'N/A' }}</td>
                    <td class="text-right">${{ number_format($product->price, 2) }}</td>
                    <td class="text-center {{ $product->stock < 5 ? 'badge-low' : 'badge-ok' }}">
                        {{ $product->stock }}
                    </td>
                    <td class="text-right">${{ number_format($itemValue, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <p><strong>Total Productos Distintos:</strong> {{ $totalItems }}</p>
        <p><strong>Total Unidades en Stock:</strong> {{ $totalStock }}</p>
        <p><strong>Valor Total del Inventario:</strong> ${{ number_format($totalValue, 2) }}</p>
    </div>

</body>
</html>