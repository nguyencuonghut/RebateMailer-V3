<div style="margin-top:28px;">
    <h2 style="margin:0 0 6px 0; font-size:20px; line-height:1.4; color:#0f172a;">{{ $table['title'] ?? $table['label'] ?? 'Bảng chi tiết' }}</h2>

    @if (! empty($table['errors']) && is_array($table['errors']))
        <div style="margin-bottom:16px; padding:12px 16px; border:1px solid rgba(245,158,11,0.32); border-radius:16px; background:rgba(245,158,11,0.08); color:#92400e; font-size:14px;">
            @foreach ($table['errors'] as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @php($tableType = (string) ($table['type'] ?? ''))

    @if (in_array($tableType, ['khoan-npp-table', 'key-account-table'], true))
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; border:1px solid #dbe4ef; border-radius:16px; overflow:hidden;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th align="left" style="padding:12px 14px; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">STT</th>
                    <th align="left" style="padding:12px 14px; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">Nội dung</th>
                    <th align="right" style="padding:12px 14px; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">Sản lượng</th>
                    <th align="right" style="padding:12px 14px; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">Mức hỗ trợ</th>
                    <th align="right" style="padding:12px 14px; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">Tổng</th>
                </tr>
            </thead>
            <tbody>
                @foreach (($table['rows'] ?? []) as $row)
                    @php($fontWeight = (($row['fontWeight'] ?? 'regular') === 'bold') ? '700' : '400')
                    @if (in_array(($row['rowType'] ?? ''), ['total', 'in-words'], true))
                        <tr>
                            <td valign="top" style="padding:12px 14px; font-size:14px; color:#64748b; font-weight:{{ $fontWeight }}; border-top:1px solid #e2e8f0;">{{ $row['numbering'] ?? '' }}</td>
                            <td colspan="3" valign="top" style="padding:12px 14px; font-size:14px; color:#0f172a; font-weight:{{ $fontWeight }}; border-top:1px solid #e2e8f0;">{{ $row['content'] ?? '' }}</td>
                            <td align="right" valign="top" style="padding:12px 14px; font-size:14px; color:#0f172a; font-weight:{{ $fontWeight }}; border-top:1px solid #e2e8f0;">
                                @if (($row['rowType'] ?? '') === 'in-words')
                                    {{ $row['amount'] ?? '' }}
                                @else
                                    {{ ($row['amount'] ?? '') !== '' ? number_format((float) str_replace(',', '', (string) $row['amount'])) : '—' }}
                                @endif
                            </td>
                        </tr>
                    @else
                        @php($amountRaw = trim((string) ($row['amount'] ?? '')))
                        <tr>
                            <td valign="top" style="padding:12px 14px; font-size:14px; color:#64748b; font-weight:{{ $fontWeight }}; border-top:1px solid #e2e8f0;">{{ $row['numbering'] ?? '' }}</td>
                            <td valign="top" style="padding:12px 14px; font-size:14px; color:#0f172a; font-weight:{{ $fontWeight }}; border-top:1px solid #e2e8f0;">{{ $row['content'] ?? '' }}</td>
                            <td align="right" valign="top" style="padding:12px 14px; font-size:14px; color:#0f172a; border-top:1px solid #e2e8f0;">{{ ($row['quantity'] ?? '') !== '' ? number_format((float) str_replace(',', '', (string) $row['quantity'])) : '—' }}</td>
                            <td align="right" valign="top" style="padding:12px 14px; font-size:14px; color:#0f172a; border-top:1px solid #e2e8f0;">{{ ($row['supportRate'] ?? '') !== '' ? number_format((float) str_replace(',', '', (string) $row['supportRate'])) : '—' }}</td>
                            <td align="right" valign="top" style="padding:12px 14px; font-size:14px; color:#0f172a; font-weight:{{ $fontWeight }}; border-top:1px solid #e2e8f0;">
                                @if (($row['rowType'] ?? '') === 'in-words')
                                    {{ $row['amount'] ?? '' }}
                                @else
                                    {{ $amountRaw !== '' ? number_format((float) str_replace(',', '', $amountRaw)) : '—' }}
                                @endif
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @else
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; border:1px solid #dbe4ef; border-radius:16px; overflow:hidden;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th align="left" style="padding:12px 14px; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">STT</th>
                    <th align="left" style="padding:12px 14px; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">Nội dung</th>
                    <th align="right" style="padding:12px 14px; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">Tổng</th>
                </tr>
            </thead>
            <tbody>
                @foreach (($table['rows'] ?? []) as $row)
                    @php($fontWeight = (($row['fontWeight'] ?? 'regular') === 'bold') ? '700' : '400')
                    @php($valueRaw = trim((string) ($row['value'] ?? '')))
                    @php($isInWordsRow = in_array(($row['rowType'] ?? ''), ['in-words', 'text'], true)
                        || trim((string) ($row['columnKey'] ?? '')) === 'Bằng chữ'
                        || str_starts_with(trim((string) ($row['content'] ?? '')), 'Bằng chữ'))
                    <tr>
                        <td valign="top" style="padding:12px 14px; font-size:14px; color:#64748b; font-weight:{{ $fontWeight }}; border-top:1px solid #e2e8f0;">{{ $row['numbering'] ?? '' }}</td>
                        <td valign="top" style="padding:12px 14px; font-size:14px; color:#0f172a; font-weight:{{ $fontWeight }}; border-top:1px solid #e2e8f0;">{{ $row['content'] ?? '' }}</td>
                        <td align="right" valign="top" style="padding:12px 14px; font-size:14px; color:#0f172a; font-weight:{{ $fontWeight }}; border-top:1px solid #e2e8f0;">
                            @if ($isInWordsRow)
                                {{ $row['value'] ?? '' }}
                            @else
                                {{ $valueRaw !== '' ? number_format((float) str_replace(',', '', $valueRaw)) : '—' }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
