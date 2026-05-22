<div style="margin-top:0; width:100%; text-align:right; page-break-inside:avoid; break-inside:avoid; page-break-before:avoid;">
    <div style="display:inline-block; min-width:240px; text-align:center; color:#0f172a; page-break-inside:avoid; break-inside:avoid;">
        <div style="font-size:15px; font-weight:600;">
            {{ data_get($signatureSnapshot, 'title', 'Đại diện công ty') }}
        </div>

        @if (filled(data_get($signatureSnapshot, 'signatureImageDataUrl')))
            <div style="margin-top:12px; page-break-inside:avoid; break-inside:avoid;">
                <img
                    src="{{ data_get($signatureSnapshot, 'signatureImageDataUrl') }}"
                    alt="Chữ ký người đại diện"
                    style="max-width:180px; max-height:80px; object-fit:contain;"
                >
            </div>
        @endif

        @if (filled(data_get($signatureSnapshot, 'representativeRole')))
            <div style="margin-top:10px; font-size:14px;">
                {{ data_get($signatureSnapshot, 'representativeRole') }}
            </div>
        @endif

        @if (filled(data_get($signatureSnapshot, 'representativeName')))
            <div style="margin-top:6px; font-size:15px; font-weight:700;">
                {{ data_get($signatureSnapshot, 'representativeName') }}
            </div>
        @endif
    </div>
</div>
