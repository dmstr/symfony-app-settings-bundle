<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Unit\Validator;

use Dmstr\AppSettings\Entity\ApplicationSetting;
use Dmstr\AppSettings\Registry\SectionRegistry;
use Dmstr\AppSettings\Tests\Fixtures\GeneralSection;
use Dmstr\AppSettings\Tests\Fixtures\RetentionSection;
use Dmstr\AppSettings\Validator\MatchesSectionSchema;
use Dmstr\AppSettings\Validator\MatchesSectionSchemaValidator;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * Kernel-free checks of the schema validation of stored settings values,
 * against two test sections.
 *
 * @extends ConstraintValidatorTestCase<MatchesSectionSchemaValidator>
 */
final class MatchesSectionSchemaValidatorTest extends ConstraintValidatorTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Class constraint on the root object: paths start at the entity.
        $this->setPropertyPath('');
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new MatchesSectionSchemaValidator(new SectionRegistry([
            new RetentionSection(),
            new GeneralSection(),
        ]));
    }

    public function testValidValuesHaveNoViolation(): void
    {
        $this->validator->validate(self::setting('retention', [
            'retention_days' => 45,
            'max_items' => 12,
        ]), new MatchesSectionSchema());

        $this->assertNoViolation();
    }

    public function testEmptyValuesHaveNoViolation(): void
    {
        $this->validator->validate(self::setting('retention', []), new MatchesSectionSchema());

        $this->assertNoViolation();
    }

    public function testMatchingSectionKeyHasNoViolation(): void
    {
        $this->validator->validate(self::setting('general', ['section_key' => 'general', 'maintenance_mode' => true]), new MatchesSectionSchema());

        $this->assertNoViolation();
    }

    public function testWrongTypeIsReportedAtThePropertyPath(): void
    {
        $this->validator->validate(self::setting('retention', ['retention_days' => 'abc']), new MatchesSectionSchema());

        self::assertSame(['values/retention_days'], $this->paths());
        $violation = $this->context->getViolations()->get(0);
        self::assertSame(MatchesSectionSchema::SCHEMA_MISMATCH_ERROR, $violation->getCode());
        self::assertSame('/retention_days', $violation->getParameters()['{{ path }}']);
        self::assertStringContainsString('integer', $violation->getParameters()['{{ error }}']);
    }

    public function testValueBelowMinimumIsReportedAtThePropertyPath(): void
    {
        $this->validator->validate(self::setting('retention', ['retention_days' => 0]), new MatchesSectionSchema());

        self::assertSame(['values/retention_days'], $this->paths());
    }

    public function testEveryInvalidPropertyIsReported(): void
    {
        $this->validator->validate(self::setting('retention', [
            'retention_days' => 0,
            'max_items' => 'many',
        ]), new MatchesSectionSchema());

        $paths = $this->paths();
        sort($paths);
        self::assertSame(['values/max_items', 'values/retention_days'], $paths);
    }

    public function testUnknownPropertyIsReportedBelowValues(): void
    {
        $this->validator->validate(self::setting('retention', ['foo' => 1]), new MatchesSectionSchema());

        $paths = $this->paths();
        self::assertNotEmpty($paths);
        foreach ($paths as $path) {
            self::assertStringStartsWith('values', $path);
        }
        self::assertSame(['values/foo'], $paths);
        self::assertSame('The property "foo" is not allowed.', $this->context->getViolations()->get(0)->getParameters()['{{ error }}']);
    }

    public function testInvalidDeclaredPropertyIsNotAlsoReportedAsAdditional(): void
    {
        $this->validator->validate(self::setting('retention', ['retention_days' => 'abc', 'foo' => 1]), new MatchesSectionSchema());

        $paths = $this->paths();
        sort($paths);
        self::assertSame(['values/foo', 'values/retention_days'], $paths);
    }

    public function testSectionKeyMismatchIsReported(): void
    {
        $this->validator->validate(self::setting('retention', ['section_key' => 'general']), new MatchesSectionSchema());

        self::assertSame(['values/section_key'], $this->paths());
    }

    public function testUnknownSectionIsReportedAtSectionKey(): void
    {
        $this->validator->validate(self::setting('unknown', ['a' => 1]), new MatchesSectionSchema());

        self::assertSame(['sectionKey'], $this->paths());
        self::assertSame(MatchesSectionSchema::UNKNOWN_SECTION_ERROR, $this->context->getViolations()->get(0)->getCode());
    }

    public function testNullIsIgnored(): void
    {
        $this->validator->validate(null, new MatchesSectionSchema());

        $this->assertNoViolation();
    }

    /**
     * @param array<string, mixed> $stored
     */
    private static function setting(string $sectionKey, array $stored): ApplicationSetting
    {
        $setting = new ApplicationSetting($sectionKey, Uuid::v5(Uuid::fromString(SectionRegistry::NAMESPACE), $sectionKey));
        $setting->setStoredValues($stored);

        return $setting;
    }

    /**
     * @return list<string>
     */
    private function paths(): array
    {
        $paths = [];
        foreach ($this->context->getViolations() as $violation) {
            $paths[] = $violation->getPropertyPath();
        }

        return $paths;
    }
}
