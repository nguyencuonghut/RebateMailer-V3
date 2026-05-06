import type { KhoanNppPreview, KhoanNppPreviewRecord } from './useKhoanNppPreviewFlow';

export type KhoanNppProgramItemPresentation = {
    programIndex: number;
    title: string;
    content: string;
    quantityLabel: string;
    supportRateLabel: string;
    amountLabel: string;
};

export type KhoanNppPreviewRecordPresentation = {
    customerCode: string;
    usedProgramCount: number;
    maxProgramIndex: number | null;
    usageSummary: string;
    programItems: KhoanNppProgramItemPresentation[];
};

export type KhoanNppPreviewPresentation = {
    programBlockSummary: string;
    usageCoverageSummary: string;
    records: KhoanNppPreviewRecordPresentation[];
    recordsByCustomerCode: Record<string, KhoanNppPreviewRecordPresentation>;
};

const buildProgramItemPresentation = (record: KhoanNppPreviewRecord): KhoanNppProgramItemPresentation[] =>
    record.programItems.map((item) => ({
        programIndex: item.programIndex,
        title: `CT ${item.programIndex}`,
        content: item.content,
        quantityLabel: `SL: ${item.quantity || '-'}`,
        supportRateLabel: `đ/kg: ${item.supportRate || '-'}`,
        amountLabel: `Thành tiền: ${item.amount || '-'}`,
    }));

const buildRecordPresentation = (record: KhoanNppPreviewRecord): KhoanNppPreviewRecordPresentation => {
    const maxProgramIndex = record.programItems.length > 0
        ? Math.max(...record.programItems.map((item) => item.programIndex))
        : null;

    const usedProgramCount = record.programItems.length;
    const usageSummary = maxProgramIndex === null
        ? 'Bản ghi này chưa có CT nào có dữ liệu.'
        : `Bản ghi này đang dùng ${usedProgramCount} CT, cao nhất tới CT${maxProgramIndex}.`;

    return {
        customerCode: record.customerCode,
        usedProgramCount,
        maxProgramIndex,
        usageSummary,
        programItems: buildProgramItemPresentation(record),
    };
};

export const useKhoanNppPreviewPresentation = (preview: KhoanNppPreview | null): KhoanNppPreviewPresentation | null => {
    if (!preview) {
        return null;
    }

    const records = preview.records.map(buildRecordPresentation);
    const usedProgramIndexes = records
        .map((record) => record.maxProgramIndex)
        .filter((index): index is number => index !== null);
    const highestUsedProgramIndex = usedProgramIndexes.length > 0
        ? Math.max(...usedProgramIndexes)
        : null;

    return {
        programBlockSummary: `Sheet này có ${preview.programBlockCount} block CT được định nghĩa, từ CT1 đến CT${preview.programBlockCount}.`,
        usageCoverageSummary: highestUsedProgramIndex === null
            ? 'Chưa có bản ghi nào dùng dữ liệu CT.'
            : `Trong workbook hiện tại, bản ghi dùng CT cao nhất đang tới CT${highestUsedProgramIndex}.`,
        records,
        recordsByCustomerCode: Object.fromEntries(
            records.map((record) => [record.customerCode, record]),
        ),
    };
};
