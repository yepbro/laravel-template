<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class QualityGateScriptsTest extends TestCase
{
    public function test_composer_defines_expected_ci_scripts(): void
    {
        $composerPath = dirname(__DIR__, 2) . '/composer.json';

        $this->assertFileExists($composerPath);

        /** @var array{scripts?: array<string, mixed>} $composer */
        $composer = json_decode((string) file_get_contents($composerPath), true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($composer['scripts'] ?? null);

        foreach (['back:check', 'front:check', 'tests:coverage', 'tests:check', 'ci:check', 'quality:check', 'security:audit'] as $script) {
            $this->assertArrayHasKey($script, $composer['scripts'], sprintf('Missing composer script: %s', $script));
        }

        $this->assertSame(
            ['@prettier', '@eslint', '@front:check'],
            $composer['scripts']['front'],
            'The local `front` script should keep fixers and then run the full frontend gate.',
        );
    }

    public function test_package_json_defines_frontend_and_audit_scripts(): void
    {
        $packagePath = dirname(__DIR__, 2) . '/package.json';

        $this->assertFileExists($packagePath);

        /** @var array{scripts?: array<string, string>} $package */
        $package = json_decode((string) file_get_contents($packagePath), true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($package['scripts'] ?? null);

        foreach (['ci:frontend', 'test:unit:coverage', 'audit:high'] as $script) {
            $this->assertArrayHasKey($script, $package['scripts'], sprintf('Missing npm script: %s', $script));
        }
    }

    public function test_coverage_directory_keeps_only_ignore_file(): void
    {
        $coverageGitignorePath = dirname(__DIR__, 2) . '/coverage/.gitignore';

        $this->assertFileExists($coverageGitignorePath);
        $this->assertSame(
            "*\n!.gitignore\n",
            (string) file_get_contents($coverageGitignorePath),
        );
    }

    public function test_ci_workflow_uses_composer_script_groups(): void
    {
        $workflowPath = dirname(__DIR__, 2) . '/.github/workflows/ci.yml';

        $this->assertFileExists($workflowPath);

        $workflow = (string) file_get_contents($workflowPath);

        $this->assertStringContainsString('run: composer run-script front:check', $workflow);
        $this->assertStringContainsString('run: composer run-script security:audit', $workflow);
        $this->assertStringNotContainsString('run: npm run ci:frontend', $workflow);
        $this->assertStringNotContainsString('run: npm run audit:high', $workflow);
    }
}
