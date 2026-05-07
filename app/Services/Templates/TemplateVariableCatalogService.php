<?php

namespace App\Services\Templates;

class TemplateVariableCatalogService
{
    /**
     * @return array<int, array<string, string>>
     */
    public function all(): array
    {
        return [
            [
                'token' => '{{tháng}}',
                'label' => 'Tháng',
                'description' => 'Biến tháng chiết khấu dùng cho subject và lời chào.',
            ],
            [
                'token' => '{{mã & tên khách hàng}}',
                'label' => 'Mã & tên khách hàng',
                'description' => 'Hiển thị mã số và tên khách hàng trong email.',
            ],
            [
                'token' => '{{địa chỉ}}',
                'label' => 'Địa chỉ',
                'description' => 'Dùng trong lời chào để hiển thị địa chỉ khách hàng.',
            ],
            [
                'token' => '{{thức ăn chăn nuôi}}',
                'label' => 'Thức ăn chăn nuôi',
                'description' => 'Dùng trong lời chào để hiển thị nhóm thức ăn chăn nuôi.',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function allowedTokens(): array
    {
        return array_column($this->all(), 'token');
    }
}
