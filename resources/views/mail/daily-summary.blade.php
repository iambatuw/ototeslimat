<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>{{ $brand }} günlük özet</title>
</head>
<body style="margin:0;padding:24px;background:#f4f4f6;color:#15151b;font-family:-apple-system,'Segoe UI',Arial,sans-serif;font-size:15px;line-height:1.5;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;">
        <tr>
            <td style="padding:28px 28px 8px;">
                <h1 style="margin:0;font-size:22px;">{{ $day['date']->format('d.m.Y') }} özeti</h1>
                <p style="margin:6px 0 0;color:#5b5b62;">{{ $brand }}</p>
            </td>
        </tr>
        <tr>
            <td style="padding:16px 28px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    @foreach ([
                        'Verilen kod' => $day['issued'],
                        'Verilemeyen kod' => $day['failed'],
                        'Eklenen anahtar' => $day['redeemed'],
                        'Yeni müşteri' => $day['registered'],
                        'Satılmayı bekleyen anahtar' => $day['unused'],
                    ] as $label => $value)
                        <tr>
                            <td style="padding:8px 0;border-bottom:1px solid #ececf0;">{{ $label }}</td>
                            <td align="right" style="padding:8px 0;border-bottom:1px solid #ececf0;font-weight:700;">{{ number_format($value, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>

        @if ($troubled->isNotEmpty())
            <tr>
                <td style="padding:8px 28px;">
                    <h2 style="margin:0 0 6px;font-size:16px;">Kod veremeyen hesaplar</h2>
                    @foreach ($troubled as $row)
                        <p style="margin:0 0 4px;">{{ $row->account->title }}: {{ $row->failed }} hata{{ $row->reason ? ', '.$row->reason : '' }}</p>
                    @endforeach
                </td>
            </tr>
        @endif

        @if ($lowStock->isNotEmpty())
            <tr>
                <td style="padding:8px 28px;">
                    <h2 style="margin:0 0 6px;font-size:16px;">Stoğu azalanlar</h2>
                    @foreach ($lowStock as $item)
                        <p style="margin:0 0 4px;">{{ $item->title }}: {{ $item->left }} anahtar kaldı</p>
                    @endforeach
                </td>
            </tr>
        @endif

        <tr>
            <td style="padding:16px 28px 28px;">
                <a href="{{ route('admin.dashboard') }}" style="display:inline-block;padding:10px 20px;border-radius:999px;background:#0a84ff;color:#ffffff;text-decoration:none;font-weight:600;">Paneli aç</a>
                <p style="margin:16px 0 0;color:#5b5b62;font-size:13px;">Bu e-postayı Ayarlar &gt; Özellikler bölümünden kapatabilirsin.</p>
            </td>
        </tr>
    </table>
</body>
</html>
