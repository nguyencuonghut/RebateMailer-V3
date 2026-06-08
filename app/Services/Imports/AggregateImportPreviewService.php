<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;

class AggregateImportPreviewService
{
    /**
     * @return array<string, mixed>
     */
    public function aggregate(ImportBatch $importBatch): array
    {
        $recordsByCustomerCode = [];

        foreach ($this->sheetRecords($importBatch, 'Tổng hợp') as $record) {
            $code = (string) $record['customerCode'];
            $recordsByCustomerCode[$code] ??= $this->makeBaseRecord($code, 'Khách thường');
            $recordsByCustomerCode[$code]['customerFullName'] = $this->resolveCustomerFullName(
                $recordsByCustomerCode[$code]['customerFullName'],
                $record['customerFullName'] ?? null,
            );
            $recordsByCustomerCode[$code] = $this->mergeRecordEmails($recordsByCustomerCode[$code], $record);
            $recordsByCustomerCode[$code]['sourceSheets'][] = 'Tổng hợp';
            $recordsByCustomerCode[$code]['tongHop'] = $record;
        }

        foreach ($this->sheetRecords($importBatch, 'Khoán NPP') as $record) {
            $code = (string) $record['customerCode'];
            $recordsByCustomerCode[$code] ??= $this->makeBaseRecord($code, 'Khách thường');
            $recordsByCustomerCode[$code]['customerFullName'] = $this->resolveCustomerFullName(
                $recordsByCustomerCode[$code]['customerFullName'],
                $record['customerFullName'] ?? null,
            );
            $recordsByCustomerCode[$code] = $this->mergeRecordEmails($recordsByCustomerCode[$code], $record);
            $recordsByCustomerCode[$code]['sourceSheets'][] = 'Khoán NPP';
            $recordsByCustomerCode[$code]['khoanNpp'] = $record;
        }

        foreach ($this->sheetRecords($importBatch, 'Cám cá') as $record) {
            $code = (string) $record['customerCode'];
            $recordsByCustomerCode[$code] ??= $this->makeBaseRecord($code, 'Khách thường');
            $recordsByCustomerCode[$code]['customerFullName'] = $this->resolveCustomerFullName(
                $recordsByCustomerCode[$code]['customerFullName'],
                $record['customerFullName'] ?? null,
            );
            $recordsByCustomerCode[$code] = $this->mergeRecordEmails($recordsByCustomerCode[$code], $record);
            $recordsByCustomerCode[$code]['sourceSheets'][] = 'Cám cá';
            $recordsByCustomerCode[$code]['camCa'] = $record;
        }

        foreach ($this->sheetRecords($importBatch, 'Key Account') as $record) {
            $code = (string) $record['customerCode'];
            $recordsByCustomerCode[$code] ??= $this->makeBaseRecord($code, 'Key Account');
            $recordsByCustomerCode[$code]['customerType'] = 'Key Account';
            $recordsByCustomerCode[$code]['customerFullName'] = $this->resolveCustomerFullName(
                $recordsByCustomerCode[$code]['customerFullName'],
                $record['customerFullName'] ?? null,
            );
            $recordsByCustomerCode[$code] = $this->mergeRecordEmails($recordsByCustomerCode[$code], $record);
            $recordsByCustomerCode[$code]['sourceSheets'][] = 'Key Account';
            $recordsByCustomerCode[$code]['keyAccount'] = $record;
        }

        $records = array_values(array_map(function (array $record): array {
            $record['sourceSheets'] = array_values(array_unique($record['sourceSheets']));

            $validationErrors = [];
            if ($record['tongHop'] !== null) {
                $validationErrors = array_merge($validationErrors, $this->validateSheetRecord('Tổng hợp', $record['tongHop']));
            }
            if ($record['khoanNpp'] !== null) {
                $validationErrors = array_merge($validationErrors, $this->validateSheetRecord('Khoán NPP', $record['khoanNpp']));
            }
            if ($record['camCa'] !== null) {
                $validationErrors = array_merge($validationErrors, $this->validateSheetRecord('Cám cá', $record['camCa']));
            }
            if ($record['keyAccount'] !== null) {
                $validationErrors = array_merge($validationErrors, $this->validateSheetRecord('Key Account', $record['keyAccount']));
            }
            $record['validationErrors'] = $validationErrors;

            return $record;
        }, $recordsByCustomerCode));

        usort($records, static fn (array $left, array $right): int => strcmp($left['customerCode'], $right['customerCode']));

        $normalCustomerCount = count(array_filter(
            $records,
            static fn (array $record): bool => $record['customerType'] === 'Khách thường',
        ));
        $keyAccountCustomerCount = count(array_filter(
            $records,
            static fn (array $record): bool => $record['customerType'] === 'Key Account',
        ));
        $errorCount = count(array_filter(
            $records,
            static fn (array $record): bool => ! empty($record['validationErrors']),
        ));

        return [
            'summary' => [
                'totalCustomerCount' => count($records),
                'normalCustomerCount' => $normalCustomerCount,
                'keyAccountCustomerCount' => $keyAccountCustomerCount,
                'errorCount' => $errorCount,
            ],
            'records' => $records,
            'nextStep' => 'Dữ liệu đã được gom theo Mã số. Bước kế tiếp sẽ thêm validation để đánh dấu thiếu email, xung đột phân loại và các bất thường dữ liệu.',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sheetRecords(ImportBatch $importBatch, string $sheetName): array
    {
        return $importBatch->sheetRecords()
            ->where('sheet_name', $sheetName)
            ->orderBy('row_number')
            ->get()
            ->map(static fn ($record): array => $record->parsed_payload)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function makeBaseRecord(string $customerCode, string $customerType): array
    {
        return [
            'customerCode' => $customerCode,
            'customerFullName' => '',
            'customerType' => $customerType,
            'email' => '',
            'emails' => [],
            'sourceSheets' => [],
            'tongHop' => null,
            'khoanNpp' => null,
            'camCa' => null,
            'keyAccount' => null,
        ];
    }

    private function resolveCustomerFullName(mixed $currentValue, mixed $candidateValue): string
    {
        $current = trim((string) $currentValue);

        if ($current !== '') {
            return $current;
        }

        return trim((string) $candidateValue);
    }

    /**
     * @param  array<string, mixed>  $aggregateRecord
     * @param  array<string, mixed>  $sheetRecord
     * @return array<string, mixed>
     */
    private function mergeRecordEmails(array $aggregateRecord, array $sheetRecord): array
    {
        $currentEmails = is_array($aggregateRecord['emails'] ?? null) ? $aggregateRecord['emails'] : [];
        $sheetEmails = $this->extractEmails($sheetRecord);
        $merged = [];
        $seen = [];

        foreach (array_merge($currentEmails, $sheetEmails) as $email) {
            $value = trim((string) $email);

            if ($value === '') {
                continue;
            }

            $key = mb_strtolower($value);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $merged[] = $value;
        }

        $aggregateRecord['emails'] = $merged;

        if (($aggregateRecord['email'] ?? '') === '' && $merged !== []) {
            $aggregateRecord['email'] = $merged[0];
        }

        return $aggregateRecord;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return list<string>
     */
    private function validateSheetRecord(string $sheetName, array $record): array
    {
        $errors = [];

        $emails = $this->extractEmails($record);

        if ($emails === []) {
            $errors[] = "[{$sheetName}] Email: không được để trống";
        } else {
            foreach ($emails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    $errors[] = count($emails) === 1
                        ? "[{$sheetName}] Email: không đúng định dạng"
                        : "[{$sheetName}] Email: \"{$email}\" không đúng định dạng";
                }
            }
        }

        $grandTotal = trim((string) ($record['grandTotal'] ?? ''));
        if ($grandTotal === '') {
            $errors[] = "[{$sheetName}] Tổng cộng: thiếu dữ liệu";
        }

        $totalInWords = trim((string) ($record['totalInWords'] ?? ''));
        if ($totalInWords === '') {
            $errors[] = "[{$sheetName}] Bằng chữ: thiếu dữ liệu";
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return list<string>
     */
    private function extractEmails(array $record): array
    {
        $emails = $record['emails'] ?? null;

        if (is_array($emails)) {
            return array_values(array_filter(array_map(
                static fn (mixed $email): string => trim((string) $email),
                $emails,
            ), static fn (string $email): bool => $email !== ''));
        }

        $email = trim((string) ($record['email'] ?? ''));

        return $email === '' ? [] : [$email];
    }
}
