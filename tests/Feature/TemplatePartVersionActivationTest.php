<?php

namespace Tests\Feature;

use App\Models\TemplatePart;
use App\Models\TemplatePartVersion;
use App\Services\Templates\ActivateTemplatePartVersionService;
use App\Services\Templates\EnsureTemplatePartCatalogPersistedService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplatePartVersionActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        app(EnsureTemplatePartCatalogPersistedService::class)->ensure();
    }

    public function test_activation_service_keeps_only_one_active_subject_version(): void
    {
        $subjectPart = TemplatePart::query()->where('type', 'subject')->firstOrFail();

        $subjectVersionOne = $this->createVersion($subjectPart, 1, 'Subject v1');
        $subjectVersionTwo = $this->createVersion($subjectPart, 2, 'Subject v2');

        $service = app(ActivateTemplatePartVersionService::class);

        $service->activate($subjectVersionOne);
        $service->activate($subjectVersionTwo);

        $this->assertFalse($subjectVersionOne->fresh()->is_active);
        $this->assertTrue($subjectVersionTwo->fresh()->is_active);
        $this->assertSame(1, TemplatePartVersion::query()->where('template_part_id', $subjectPart->id)->where('is_active', true)->count());
    }

    public function test_activation_service_keeps_latest_two_active_greeting_versions(): void
    {
        $greetingPart = TemplatePart::query()->where('type', 'greeting')->firstOrFail();

        $greetingVersionOne = $this->createVersion($greetingPart, 1, 'Greeting v1');
        $greetingVersionTwo = $this->createVersion($greetingPart, 2, 'Greeting v2');
        $greetingVersionThree = $this->createVersion($greetingPart, 3, 'Greeting v3');

        $service = app(ActivateTemplatePartVersionService::class);

        $service->activate($greetingVersionOne);
        $service->activate($greetingVersionTwo);
        $service->activate($greetingVersionThree);

        $activeVersions = TemplatePartVersion::query()
            ->where('template_part_id', $greetingPart->id)
            ->where('is_active', true)
            ->orderBy('version_no')
            ->pluck('version_no')
            ->all();

        $this->assertFalse($greetingVersionOne->fresh()->is_active);
        $this->assertSame([2, 3], $activeVersions);
        $this->assertSame(2, TemplatePartVersion::query()->where('template_part_id', $greetingPart->id)->where('is_active', true)->count());
    }

    private function createVersion(TemplatePart $templatePart, int $versionNo, string $textTemplate): TemplatePartVersion
    {
        return TemplatePartVersion::query()->create([
            'template_part_id' => $templatePart->id,
            'version_no' => $versionNo,
            'version_label' => sprintf('Version %d', $versionNo),
            'text_template' => $textTemplate,
            'is_active' => false,
        ]);
    }
}
