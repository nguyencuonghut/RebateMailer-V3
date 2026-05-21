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
    @include('mail.partials.campaign-recipient-table', ['table' => $table])
@endforeach

@if (! empty($signatureSnapshot) && is_array($signatureSnapshot))
    @include('mail.partials.campaign-recipient-signature', ['signatureSnapshot' => $signatureSnapshot])
@endif
