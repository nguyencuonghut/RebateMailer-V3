@if (filled($bodyHtml ?? null))
    {!! $bodyHtml !!}
@else
    <div style="font-size:15px; white-space:pre-line; color:#1e293b;">
        {{ data_get($preview, 'greeting.renderedText', 'Chưa có lời chào') }}
    </div>

    @if (! empty($preview['errors']) && is_array($preview['errors']))
        <div style="margin-top:24px; padding:12px 16px; border:1px solid rgba(239,68,68,0.32); border-radius:16px; background:rgba(239,68,68,0.08); color:#991b1b; font-size:14px;">
            @foreach ($preview['errors'] as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @foreach (($preview['tables'] ?? []) as $table)
        <div style="margin-top:28px;">
            <h2 style="margin:0 0 6px 0; font-size:20px; line-height:1.4; color:#0f172a;">{{ $table['title'] ?? $table['label'] ?? 'Bảng chi tiết' }}</h2>

            @if (! empty($table['errors']) && is_array($table['errors']))
                <div style="margin-bottom:16px; padding:12px 16px; border:1px solid rgba(245,158,11,0.32); border-radius:16px; background:rgba(245,158,11,0.08); color:#92400e; font-size:14px;">
                    @foreach ($table['errors'] as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @include('mail.partials.campaign-recipient-table', ['table' => $table])
        </div>
    @endforeach
@endif
