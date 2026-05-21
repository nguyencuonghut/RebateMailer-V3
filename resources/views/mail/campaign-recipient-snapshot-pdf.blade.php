<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $campaignName ?: ($page['subjectLine'] ?? 'Export PDF mail') }}</title>
        <style>
            @page {
                margin: 16mm 12mm;
            }

            body {
                margin: 0;
                font-family: 'DejaVu Sans', sans-serif;
                color: #0f172a;
            }
        </style>
    </head>
    <body>
        @include('mail.partials.campaign-export-pdf-page', ['page' => $page])
    </body>
</html>
