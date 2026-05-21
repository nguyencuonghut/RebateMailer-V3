<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $campaign->name ?? 'Export PDF mail chiến dịch' }}</title>
        <style>
            @page {
                margin: 16mm 12mm;
            }

            body {
                margin: 0;
                font-family: Arial, Helvetica, sans-serif;
                color: #0f172a;
            }

            .pdf-page {
                page-break-after: always;
            }

            .pdf-page:last-child {
                page-break-after: auto;
            }
        </style>
    </head>
    <body>
        @foreach ($pages as $page)
            <div class="pdf-page">
                <div style="font-size: 11px; color: #64748b; margin-bottom: 10px;">
                    {{ $page['recipient']['customerCode'] ?? '' }} - {{ $page['recipient']['customerFullName'] ?? '' }}
                </div>

                <div style="font-size: 18px; font-weight: 700; margin-bottom: 16px;">
                    {{ $page['subjectLine'] ?? '' }}
                </div>

                <div>
                    {!! $page['bodyHtml'] !!}
                </div>

                @include('mail.partials.campaign-recipient-signature', ['signatureSnapshot' => $page['signatureSnapshot'] ?? null])
            </div>
        @endforeach
    </body>
</html>
