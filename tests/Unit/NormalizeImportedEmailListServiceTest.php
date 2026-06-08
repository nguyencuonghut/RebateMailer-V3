<?php

namespace Tests\Unit;

use App\Services\Imports\NormalizeImportedEmailListService;
use PHPUnit\Framework\TestCase;

class NormalizeImportedEmailListServiceTest extends TestCase
{
    public function test_it_normalizes_semicolon_delimited_email_list(): void
    {
        $result = (new NormalizeImportedEmailListService())->normalize(' a@example.com; b@example.com ; ; A@example.com; c@example.com ');

        $this->assertSame([
            'a@example.com',
            'b@example.com',
            'c@example.com',
        ], $result);
    }

    public function test_it_returns_empty_list_for_blank_input(): void
    {
        $result = (new NormalizeImportedEmailListService())->normalize(' ;  ; ');

        $this->assertSame([], $result);
    }
}
