@php($forPdf = (bool) ($forPdf ?? false))
@php($tableType = (string) ($table['type'] ?? ''))
@php($tableFontSize = $forPdf ? '12px' : '14px')
@php($cellPadding = $forPdf ? '8px 10px' : '12px 14px')
@php($headerPadding = $forPdf ? '9px 10px' : '12px 14px')
@php($tableRadius = $forPdf ? '12px' : '16px')
@php($cellLineHeight = $forPdf ? '1.4' : '1.6')

@if (in_array($tableType, ['khoan-npp-table', 'key-account-table'], true))
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; border:1px solid #dbe4ef; border-radius:{{ $tableRadius }}; overflow:hidden;">
        <thead>
            <tr style="background:#f8fafc;">
                <th align="left" style="padding:{{ $headerPadding }}; font-size:{{ $tableFontSize }}; color:#0f172a; border-bottom:1px solid #e2e8f0;">STT</th>
                <th align="left" style="padding:{{ $headerPadding }}; font-size:{{ $tableFontSize }}; color:#0f172a; border-bottom:1px solid #e2e8f0;">Nội dung</th>
                <th align="right" style="padding:{{ $headerPadding }}; font-size:{{ $tableFontSize }}; color:#0f172a; border-bottom:1px solid #e2e8f0;">Sản lượng</th>
                <th align="right" style="padding:{{ $headerPadding }}; font-size:{{ $tableFontSize }}; color:#0f172a; border-bottom:1px solid #e2e8f0;">Mức hỗ trợ</th>
                <th align="right" style="padding:{{ $headerPadding }}; font-size:{{ $tableFontSize }}; color:#0f172a; border-bottom:1px solid #e2e8f0;">Tổng</th>
            </tr>
        </thead>
        <tbody>
            @foreach (($table['rows'] ?? []) as $row)
                @php($fontWeight = (($row['fontWeight'] ?? 'regular') === 'bold') ? '700' : '400')
                @if (($row['rowType'] ?? '') === 'in-words')
                    <tr>
                        <td valign="top" style="padding:{{ $cellPadding }}; font-size:{{ $tableFontSize }}; color:#64748b; font-weight:{{ $fontWeight }}; border-top:1px solid #e2e8f0;">{{ $row['numbering'] ?? '' }}</td>
                        <td colspan="4" valign="top" style="padding:{{ $cellPadding }}; font-size:{{ $tableFontSize }}; color:#0f172a; font-weight:{{ $fontWeight }}; line-height:{{ $cellLineHeight }}; border-top:1px solid #e2e8f0;">
                            {{ trim((string) ($row['content'] ?? '')) }}
                            @if (trim((string) ($row['amount'] ?? '')) !== '')
                                {{ ' ' . trim((string) ($row['amount'] ?? '')) }}
                            @endif
                        </td>
                    </tr>
                @elseif (($row['rowType'] ?? '') === 'total')
                    <tr>
                        <td valign="top" style="padding:{{ $cellPadding }}; font-size:{{ $tableFontSize }}; color:#64748b; font-weight:{{ $fontWeight }}; border-top:1px solid #e2e8f0;">{{ $row['numbering'] ?? '' }}</td>
                        <td colspan="3" valign="top" style="padding:{{ $cellPadding }}; font-size:{{ $tableFontSize }}; color:#0f172a; font-weight:{{ $fontWeight }}; line-height:{{ $cellLineHeight }}; border-top:1px solid #e2e8f0;">{{ $row['content'] ?? '' }}</td>
                        <td align="right" valign="top" style="padding:{{ $cellPadding }}; font-size:{{ $tableFontSize }}; color:#0f172a; font-weight:{{ $fontWeight }}; border-top:1px solid #e2e8f0;">
                            {{ ($row['amount'] ?? '') !== '' ? number_format((float) str_replace(',', '', (string) $row['amount'])) : '—' }}
                        </td>
                    </tr>
                @else
                    @php($amountRaw = trim((string) ($row['amount'] ?? '')))
                    <tr>
                        <td valign="top" style="padding:{{ $cellPadding }}; font-size:{{ $tableFontSize }}; color:#64748b; font-weight:{{ $fontWeight }}; border-top:1px solid #e2e8f0;">{{ $row['numbering'] ?? '' }}</td>
                        <td valign="top" style="padding:{{ $cellPadding }}; font-size:{{ $tableFontSize }}; color:#0f172a; font-weight:{{ $fontWeight }}; line-height:{{ $cellLineHeight }}; border-top:1px solid #e2e8f0;">{{ $row['content'] ?? '' }}</td>
                        <td align="right" valign="top" style="padding:{{ $cellPadding }}; font-size:{{ $tableFontSize }}; color:#0f172a; border-top:1px solid #e2e8f0;">{{ ($row['quantity'] ?? '') !== '' ? number_format((float) str_replace(',', '', (string) $row['quantity'])) : '—' }}</td>
                        <td align="right" valign="top" style="padding:{{ $cellPadding }}; font-size:{{ $tableFontSize }}; color:#0f172a; border-top:1px solid #e2e8f0;">{{ ($row['supportRate'] ?? '') !== '' ? number_format((float) str_replace(',', '', (string) $row['supportRate'])) : '—' }}</td>
                        <td align="right" valign="top" style="padding:{{ $cellPadding }}; font-size:{{ $tableFontSize }}; color:#0f172a; font-weight:{{ $fontWeight }}; border-top:1px solid #e2e8f0;">
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
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; border:1px solid #dbe4ef; border-radius:{{ $tableRadius }}; overflow:hidden;">
        <thead>
            <tr style="background:#f8fafc;">
                <th align="left" style="padding:{{ $headerPadding }}; font-size:{{ $tableFontSize }}; color:#0f172a; border-bottom:1px solid #e2e8f0;">STT</th>
                <th align="left" style="padding:{{ $headerPadding }}; font-size:{{ $tableFontSize }}; color:#0f172a; border-bottom:1px solid #e2e8f0;">Nội dung</th>
                <th align="right" style="padding:{{ $headerPadding }}; font-size:{{ $tableFontSize }}; color:#0f172a; border-bottom:1px solid #e2e8f0;">Tổng</th>
            </tr>
        </thead>
        <tbody>
            @foreach (($table['rows'] ?? []) as $row)
                @php($fontWeight = (($row['fontWeight'] ?? 'regular') === 'bold') ? '700' : '400')
                @php($valueRaw = trim((string) ($row['value'] ?? '')))
                @php($isInWordsRow = in_array(($row['rowType'] ?? ''), ['in-words', 'text'], true)
                    || trim((string) ($row['columnKey'] ?? '')) === 'Bằng chữ'
                    || str_starts_with(trim((string) ($row['content'] ?? '')), 'Bằng chữ'))
                @if ($isInWordsRow)
                    <tr>
                        <td valign="top" style="padding:{{ $cellPadding }}; font-size:{{ $tableFontSize }}; color:#64748b; font-weight:{{ $fontWeight }}; border-top:1px solid #e2e8f0;">{{ $row['numbering'] ?? '' }}</td>
                        <td colspan="2" valign="top" style="padding:{{ $cellPadding }}; font-size:{{ $tableFontSize }}; color:#0f172a; font-weight:{{ $fontWeight }}; line-height:{{ $cellLineHeight }}; border-top:1px solid #e2e8f0;">
                            {{ trim((string) ($row['content'] ?? '')) }}
                            @if ($valueRaw !== '')
                                {{ ' ' . $valueRaw }}
                            @endif
                        </td>
                    </tr>
                @else
                    <tr>
                        <td valign="top" style="padding:{{ $cellPadding }}; font-size:{{ $tableFontSize }}; color:#64748b; font-weight:{{ $fontWeight }}; border-top:1px solid #e2e8f0;">{{ $row['numbering'] ?? '' }}</td>
                        <td valign="top" style="padding:{{ $cellPadding }}; font-size:{{ $tableFontSize }}; color:#0f172a; font-weight:{{ $fontWeight }}; line-height:{{ $cellLineHeight }}; border-top:1px solid #e2e8f0;">{{ $row['content'] ?? '' }}</td>
                        <td align="right" valign="top" style="padding:{{ $cellPadding }}; font-size:{{ $tableFontSize }}; color:#0f172a; font-weight:{{ $fontWeight }}; border-top:1px solid #e2e8f0;">
                            {{ $valueRaw !== '' ? number_format((float) str_replace(',', '', $valueRaw)) : '—' }}
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
@endif
