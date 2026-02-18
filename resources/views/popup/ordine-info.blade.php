<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>DISTINTA BASE SCALARE CON GIACENZE A MAGAZZINO</title>
    <style>
        body { font-family: Arial, sans-serif; background: #fff; color: #111; }
        .dbase-title { font-weight: bold; font-size: 20px; text-align: center; margin-top: 20px; }
        .dbase-prod { font-size: 16px; text-align: center; margin-bottom: 12px; }
        .dbase-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .dbase-table th, .dbase-table td {
            border: 1px solid #222;
            padding: 4px 8px;
            font-size: 14px;
        }
        .dbase-table th {
            background: #f8f8f8;
            text-align: center;
            font-weight: bold;
        }
        .row-highlight {
            background: #c7f2ff !important;
        }
        .barcode-cell {
            text-align: center;
            color: #aaa;
            font-size: 18px;
        }

        .dbase-table th.codice, .dbase-table td.codice    { width: 10%; }
        .dbase-table th.descrizione, .dbase-table td.descrizione { width: 14%; }
        .dbase-table th.tipo, .dbase-table td.tipo        { width: 6%; }
        .dbase-table th.um, .dbase-table td.um            { width: 6%; }
        .dbase-table th.giacenza, .dbase-table td.giacenza{ width: 8%; }
        .dbase-table th.status, .dbase-table td.status    { width: 7%; }
        .dbase-table th.commento, .dbase-table td.commento{ width: 11%; }
            .dbase-table th.barcode, .dbase-table td.barcode {
            width: 20%; /* più stretta, ma leggibile */
            padding: 5px;
        }


        .barcode-cell img {
            max-width: 95%; 
            height: auto;
            display: block;
            margin: 0 auto;
        }

    @media print {
        @page {
            size: A4 portrait;
            margin: 20mm;
        }

        body {
            background: #fff !important;
        }

        .barcode-cell img {
            width: 240px !important;
            height: 60px !important;
            display: block;
            margin: 0 auto;
        }

        .dbase-table th.barcode, .dbase-table td.barcode {
            width: 25%;
        }

        .dbase-table th, .dbase-table td {
            font-size: 11px;
            padding: 4px 6px;
        }
    }



    </style>
</head>
<body style="min-height:100vh; display: flex; flex-direction: column; align-items: center;">
    
    <?php
        $totale_bom =0;
        /* foreach($datiOrdine["bom"] as $r){
            $totale_bom += $r['QtyPer'];
        } */
    ?>
    
    <div class="dbase-title">
        DISTINTA BASE SCALARE CON GIACENZE A MAGAZZINO
        <button onclick="window.print()" style="margin: 15px 0 10px 10px; padding: 7px 18px; font-size: 15px; border-radius: 6px; background: #222; color: white; border: none; cursor: pointer;">
            🖨️ Stampa / 📄 Genera PDF
        </button>
    </div>
    <div class="dbase-prod">
        <b>{{ $componenti[0]['componentNo'] ?? '' }}</b> {{ $componenti[0]['compDescription'] ?? '' }}<br>
    </div>
    <div style="width: 90%; max-width:1400px; margin:0 auto;">
        <table class="dbase-table">
            <thead>
                <tr>
                    <th>Codice</th>
                    <th>Descrizione</th>
                    <th>Tipo</th>
                    <th>UM</th>
                    <th>Giacenza</th>
                    <th>Status</th>
                    <th>Commento allo Status</th>
                    <th style="width: 400px">Barcode</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    unset($componenti[0]);
                ?>
               
                @forelse ($componenti as $row)
                
                <tr class="">
                    <td>{{ $row['componentNo'] ?? '' }}</td>
                    <td>{{ $row['compDescription'] ?? '' }}</td>
                    <td>{{ $row['productype']['productype'] ?? '' }}</td>
                    <td>{{ $row['UM']['UM'] ?? '' }}</td>
                    <td>
                        @if(isset($row['ds']) && $row['ds']>0)
                            {{ number_format($row['ds'], 2, '.', '') }}<br>
                        @endif
                        @if(isset($row['ok']) && $row['ok']>0)
                            {{ number_format($row['ok'], 2, '.', '') }}<br>
                        @endif
                        @if(isset($row['ss']) && $row['ss']>0)
                            {{ number_format($row['ss'], 2, '.', '') }}
                        @endif
                    </td>
                     <td>
                        @if(isset($row['ds']) && $row['ds']>0)
                            DS<br>
                        @endif
                        @if(isset($row['ok']) && $row['ok']>0)
                            OK<br>
                        @endif
                        @if(isset($row['ss']) && $row['ss']>0)
                            SS
                        @endif
                    </td>
                    <td>{{ $row['commento'] ?? '' }}</td>
                    <td class="barcode-cell">
                        @if(isset($row['levelCode']) && $row['levelCode'] == 1)
                            <img src="{{ route('barcode', ['code' => $row['componentNo'] ?? '']) }}" alt="{{$row['componentNo']}}" style="height: 60px; width: 240px;">
                        @endif
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="8" class="no-data">Nessun dato BOM trovato per i criteri specificati.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
