<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $documentTitle ?: 'Export PDF mail chiến dịch' }}</title>
    </head>
    <body style="margin:0; padding:0; background:#ffffff; font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color:#0f172a; line-height:1.6;">
        <div style="padding:24px 24px 32px 24px;">
            @foreach ($pages as $page)
                @include('mail.partials.campaign-export-pdf-page', [
                    'subjectLine' => $page['subjectLine'] ?? '',
                    'bodyHtml' => $page['bodyHtml'] ?? null,
                    'preview' => $page['preview'] ?? null,
                    'signature' => $page['signature'] ?? null,
                    'pageBreakBefore' => $loop->index > 0,
                ])
            @endforeach
        </div>
    </body>
</html>
