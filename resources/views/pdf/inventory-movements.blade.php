<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Movimientos de Inventario</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; margin: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1e3a8a; padding-bottom: 8px; }
        .header h1 { margin: 0; font-size: 18px; color: #1e3a8a; text-transform: uppercase; }
        .header p { margin: 4px 0 0; color: #64748b; font-size: 10px; }
        .meta-table { width: 100%; margin-bottom: 12px; }
        .meta-table td { font-size: 10px; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        table.data-table th, table.data-table td { border: 1px solid #cbd5e1; padding: 6px; text-align: left; }
        table.data-table th { background-color: #f1f5f9; font-weight: bold; color: #1e293b; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 25px; text-align: right; font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Reporte de Movimientos de Inventario</h1>
        <p>Generado el: {{ $generatedAt }}</p>
    </div>

    <table class="meta-table">
        <tr>
            <td><strong>Total Movimientos:</strong> {{ $movements->count() }}</td>
            <td class="text-right"><strong>Monto Acumulado:</strong> ${{ number_format($movements->sum('total_amount'), 2) }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 5%;">ID</th>
                <th style="width: 18%;">Tipo</th>
                <th style="width: 16%;">Operador</th>
                <th style="width: 16%;">Cliente</th>
                <th>Motivo / Observación</th>
                <th class="text-right" style="width: 12%;">Monto ($)</th>
                <th class="text-center" style="width: 15%;">Fecha</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movements as $movement)
                <tr>
                    <td class="text-center">{{ $movement->id }}</td>
                    <td>
                        @switch($movement->type)
                            @case('abastecimiento')
                                Entrada (Abastecimiento)
                                @break
                            @case('venta')
                                Salida (Venta)
                                @break
                            @case('ajuste_manual')
                                Ajuste Manual
                                @break
                            @default
                                {{ $movement->type }}
                        @endswitch
                    </td>
                    <td>{{ $movement->user->name ?? 'Sistema' }}</td>
                    <td>{{ $movement->client->name ?? '-' }}</td>
                    <td>{{ $movement->reason ?? '-' }}</td>
                    <td class="text-right">${{ number_format($movement->total_amount, 2) }}</td>
                    <td class="text-center">{{ $movement->created_at->format('d/m/Y h:i A') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No se encontraron movimientos registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Documento generado automáticamente por el sistema.
    </div>

</body>
</html>