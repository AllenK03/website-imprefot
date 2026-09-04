<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cierre de Inventario - {{ str_pad($record->month, 2, '0', STR_PAD_LEFT) }}/{{ $record->year }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #2563eb; padding-bottom: 8px; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; color: #1e3a8a; }
        .header p { margin: 3px 0 0; color: #666; font-size: 11px; }
        .meta-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .meta-table td { padding: 4px 0; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        table.data-table th { background-color: #f1f5f9; font-weight: bold; color: #1e293b; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary-box { margin-top: 15px; float: right; width: 40%; border: 1px solid #cbd5e1; padding: 10px; background-color: #f8fafc; }
        .summary-box p { margin: 3px 0; }
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Cierre Mensual de Inventario</h1>
        <p>Periodo: {{ str_pad($record->month, 2, '0', STR_PAD_LEFT) }}/{{ $record->year }}</p>
    </div>

    <table class="meta-table">
        <tr>
            <td><strong>Fecha de Cierre:</strong> {{ $record->created_at->format('d/m/Y h:i A') }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 5%;">#</th>
                <th>Producto</th>
                <th class="text-center" style="width: 15%;">Stock Congelado</th>
                <th class="text-right" style="width: 20%;">Precio Unit. ($)</th>
                <th class="text-right" style="width: 20%;">Valor Total ($)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($record->snapshot_data ?? [] as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item['name'] ?? 'N/A' }}</td>
                    <td class="text-center">{{ $item['stock'] ?? 0 }}</td>
                    <td class="text-right">${{ number_format($item['price'] ?? 0, 2) }}</td>
                    <td class="text-right">${{ number_format($item['total'] ?? (($item['stock'] ?? 0) * ($item['price'] ?? 0)), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No hay datos en la fotografía del cierre.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="clearfix">
        <div class="summary-box">
            <p><strong>Tipos de Productos:</strong> {{ $record->total_products }}</p>
            <p><strong>Unidades Totales:</strong> {{ $record->total_stock }}</p>
            <p><strong>Valorización Total:</strong> ${{ number_format($record->total_value, 2) }}</p>
        </div>
    </div>

</body>
</html>