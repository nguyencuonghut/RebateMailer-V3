<?php

namespace App\Support\Templates;

enum TemplatePartType: string
{
    case Subject = 'subject';
    case Greeting = 'greeting';
    case RepresentativeSignature = 'representative-signature';
    case TongHopTable = 'tong-hop-table';
    case KhoanNppTable = 'khoan-npp-table';
    case CamCaTable = 'cam-ca-table';
    case KeyAccountTable = 'key-account-table';

    public function code(): string
    {
        return match ($this) {
            self::Subject => 'subject',
            self::Greeting => 'greeting',
            self::RepresentativeSignature => 'representative-signature',
            self::TongHopTable => 'tong-hop',
            self::KhoanNppTable => 'khoan-npp',
            self::CamCaTable => 'cam-ca',
            self::KeyAccountTable => 'key-account',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Subject => 'Subject',
            self::Greeting => 'Lời chào',
            self::RepresentativeSignature => 'Chữ ký đại diện',
            self::TongHopTable => 'Table Chế độ tháng',
            self::KhoanNppTable => 'Table Chương trình khoán đặc biệt',
            self::CamCaTable => 'Table Chiết khấu cám cá',
            self::KeyAccountTable => 'Table Chiết khấu Key Account',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Subject => 'Dòng tiêu đề email với biến tháng và khách hàng.',
            self::Greeting => 'Khối lời chào và thông tin khách hàng ở đầu body email.',
            self::RepresentativeSignature => 'Chữ ký người đại diện theo 2 block Khách thường và Key Account.',
            self::TongHopTable => 'Lấy dữ liệu từ sheet Tổng hợp.',
            self::KhoanNppTable => 'Lấy dữ liệu từ sheet Khoán NPP.',
            self::CamCaTable => 'Lấy dữ liệu từ sheet Cám cá.',
            self::KeyAccountTable => 'Lấy dữ liệu từ sheet Key Account.',
        };
    }

    public function kind(): string
    {
        return match ($this) {
            self::Subject, self::Greeting => 'text',
            self::RepresentativeSignature => 'composite',
            self::TongHopTable, self::KhoanNppTable, self::CamCaTable, self::KeyAccountTable => 'table',
        };
    }

    public function sourceSheet(): ?string
    {
        return match ($this) {
            self::Subject, self::Greeting, self::RepresentativeSignature => null,
            self::TongHopTable => 'Tổng hợp',
            self::KhoanNppTable => 'Khoán NPP',
            self::CamCaTable => 'Cám cá',
            self::KeyAccountTable => 'Key Account',
        };
    }

    public function maxActiveVersions(): int
    {
        return match ($this) {
            self::Subject => 1,
            self::Greeting => 2,
            self::RepresentativeSignature => 1,
            self::TongHopTable, self::KhoanNppTable, self::CamCaTable, self::KeyAccountTable => 1,
        };
    }
}
