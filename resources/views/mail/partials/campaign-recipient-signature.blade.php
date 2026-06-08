@php($forPdf = (bool) ($forPdf ?? false))
@if (is_array($signature ?? null) && ($signature['title'] ?? null || $signature['signatureImageDataUrl'] ?? null || $signature['representativeRole'] ?? null || $signature['representativeName'] ?? null))
    <div style="margin-top:{{ $forPdf ? '10px' : '32px' }}; text-align:right;">
        @if (! empty($signature['title']))
            <div style="font-size:{{ $forPdf ? '11px' : '15px' }}; font-weight:700; color:#0f172a;">{{ $signature['title'] }}</div>
        @endif

        @if (! empty($signature['signatureImageDataUrl']))
            <div style="margin-top:{{ $forPdf ? '4px' : '10px' }};">
                <img
                    src="{{ $signature['signatureImageDataUrl'] }}"
                    alt="Chữ ký đại diện"
                    style="max-width:{{ $forPdf ? '108px' : '180px' }}; max-height:{{ $forPdf ? '54px' : '96px' }}; object-fit:contain;"
                >
            </div>
        @endif

        @if (! empty($signature['representativeRole']))
            <div style="margin-top:{{ $forPdf ? '4px' : '10px' }}; font-size:{{ $forPdf ? '11px' : '15px' }}; color:#0f172a;">{{ $signature['representativeRole'] }}</div>
        @endif

        @if (! empty($signature['representativeName']))
            <div style="margin-top:1px; font-size:{{ $forPdf ? '11px' : '15px' }}; font-weight:700; color:#0f172a;">{{ $signature['representativeName'] }}</div>
        @endif
    </div>
@endif
