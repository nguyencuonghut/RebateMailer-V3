@php($forPdf = (bool) ($forPdf ?? false))
@php($greetingFontSize = $forPdf ? '12px' : '15px')
@php($sectionGap = $forPdf ? '12px' : '28px')
@php($sectionTitleFontSize = $forPdf ? '14px' : '20px')
@php($errorMarginTop = $forPdf ? '10px' : '24px')
@php($errorMarginBottom = $forPdf ? '8px' : '16px')
@php($errorFontSize = $forPdf ? '10px' : '14px')

@if (filled($bodyHtml ?? null))
    {!! $forPdf ? app(\App\Services\Mail\NormalizeMailCampaignRecipientPdfBodyHtmlService::class)->normalize((string) $bodyHtml) : $bodyHtml !!}
@else
    <div style="font-size:{{ $greetingFontSize }}; white-space:pre-line; color:#1e293b; line-height:{{ $forPdf ? '1.3' : '1.6' }};">
        {{ data_get($preview, 'greeting.renderedText', 'Chưa có lời chào') }}
    </div>

    @if (! empty($preview['errors']) && is_array($preview['errors']))
        <div style="margin-top:{{ $errorMarginTop }}; padding:{{ $forPdf ? '5px 8px' : '12px 16px' }}; border:1px solid rgba(239,68,68,0.32); border-radius:16px; background:rgba(239,68,68,0.08); color:#991b1b; font-size:{{ $errorFontSize }};">
            @foreach ($preview['errors'] as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @foreach (($preview['tables'] ?? []) as $table)
        <div style="margin-top:{{ $sectionGap }};">
            <h2 style="margin:0 0 {{ $forPdf ? '2px' : '6px' }} 0; font-size:{{ $sectionTitleFontSize }}; line-height:1.2; color:#0f172a;">{{ $table['title'] ?? $table['label'] ?? 'Bảng chi tiết' }}</h2>

            @if (! empty($table['errors']) && is_array($table['errors']))
                <div style="margin-bottom:{{ $errorMarginBottom }}; padding:{{ $forPdf ? '5px 8px' : '12px 16px' }}; border:1px solid rgba(245,158,11,0.32); border-radius:16px; background:rgba(245,158,11,0.08); color:#92400e; font-size:{{ $errorFontSize }};">
                    @foreach ($table['errors'] as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @include('mail.partials.campaign-recipient-table', ['table' => $table, 'forPdf' => $forPdf])
        </div>
    @endforeach
@endif
