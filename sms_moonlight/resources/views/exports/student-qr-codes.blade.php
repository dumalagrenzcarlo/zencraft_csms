<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 8mm; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
        }

        .page {
            width: 100%;
            page-break-after: always;
        }

        .page:last-child { page-break-after: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td {
            width: 50%;
            height: 68mm;
            padding: 4mm;
            border: 0.35mm dashed #6b7280;
            vertical-align: middle;
            text-align: center;
        }

        .qr {
            display: block;
            width: 43mm;
            height: 43mm;
            margin: 0 auto 3mm;
        }

        .label {
            min-height: 8mm;
            font-size: 10pt;
            font-weight: 700;
            line-height: 1.25;
            overflow-wrap: break-word;
        }

        .empty { border-color: transparent; }
    </style>
</head>
<body>
    @foreach ($pages as $page)
        <div class="page">
            <table>
                @foreach ($page->chunk(2) as $row)
                    <tr>
                        @foreach ($row as $card)
                            <td>
                                <img class="qr" src="{{ $card['qr'] }}" alt="QR Code">
                                <div class="label">{{ $card['label'] }}</div>
                            </td>
                        @endforeach

                        @if ($row->count() === 1)
                            <td class="empty"></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach
</body>
</html>
