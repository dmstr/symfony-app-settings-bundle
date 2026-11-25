<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Dmstr\AppSettings\Tests\Fixtures\GeneralSection;

/**
 * Admin-panel discovery: the Hydra entrypoint lists the settings resource,
 * and the OpenAPI document describes `values` with one schema per section
 * (anyOf, discriminated by the `section_key` const). Needs no database: the
 * kernel never connects for these requests.
 */
final class OpenApiExposureTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    private const string COLLECTION_PATH = '/api/admin/application_settings';
    private const string ITEM_PATH = '/api/admin/application_settings/{id}';

    public function testEntrypointListsTheSettingsResource(): void
    {
        $data = static::createClient()->request('GET', '/api', ['headers' => ['Accept' => 'application/ld+json']])->toArray();

        self::assertResponseIsSuccessful();
        self::assertSame(self::COLLECTION_PATH, $data['applicationSetting'] ?? null);
    }

    public function testOnlyReadAndPatchOperationsAreDocumented(): void
    {
        $paths = $this->openApi()['paths'];

        self::assertSame(['get'], self::methods($paths[self::COLLECTION_PATH]));
        self::assertSame(['get', 'patch'], self::methods($paths[self::ITEM_PATH]));
    }

    public function testValuesHaveOneSchemaPerSection(): void
    {
        $schemas = $this->settingSchemas();
        self::assertNotEmpty($schemas);

        foreach ($schemas as $name => $schema) {
            $properties = self::properties($schema);
            self::assertArrayNotHasKey('storedValues', $properties, $name);
            self::assertArrayHasKey('values', $properties, $name);

            $branches = $properties['values']['anyOf'] ?? null;
            self::assertIsArray($branches, $name);
            self::assertCount(3, $branches, $name);

            $bySection = [];
            foreach ($branches as $branch) {
                self::assertFalse($branch['additionalProperties'], $name);
                $bySection[$branch['properties']['section_key']['const']] = $branch;
            }
            self::assertSame(['general', 'limits', 'retention'], array_keys($bySection), $name);
            // A null default may be dropped by the OpenAPI normalizer; either
            // way the document must not announce a non-null default.
            foreach (['max_requests', 'max_rounds'] as $limit) {
                self::assertSame(['integer', 'null'], $bySection['limits']['properties'][$limit]['type'], $name);
                self::assertNull($bySection['limits']['properties'][$limit]['default'] ?? null, $name);
            }
            self::assertSame('integer', $bySection['retention']['properties']['retention_days']['type'], $name);
            self::assertSame(30, $bySection['retention']['properties']['retention_days']['default'], $name);
            self::assertSame(GeneralSection::DEFAULT_NAME, $bySection['general']['properties']['name']['default'], $name);
        }
    }

    public function testOtherSchemasDoNotContainSettingsSections(): void
    {
        foreach ($this->openApi()['components']['schemas'] as $name => $schema) {
            if (str_starts_with($name, 'ApplicationSetting')) {
                continue;
            }

            foreach (self::properties($schema) as $property) {
                foreach (\is_array($property) ? $property['anyOf'] ?? [] : [] as $branch) {
                    self::assertArrayNotHasKey('section_key', $branch['properties'] ?? [], $name);
                }
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function openApi(): array
    {
        $response = static::createClient()->request('GET', '/api/docs.jsonopenapi', ['headers' => ['Accept' => 'application/vnd.openapi+json']]);
        self::assertResponseIsSuccessful();

        return json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function settingSchemas(): array
    {
        return array_filter(
            $this->openApi()['components']['schemas'],
            static fn (string $name): bool => str_starts_with($name, 'ApplicationSetting'),
            \ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Properties of a component schema; JSON-LD schemas wrap them in allOf.
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    private static function properties(array $schema): array
    {
        $properties = $schema['properties'] ?? [];
        foreach ($schema['allOf'] ?? [] as $part) {
            $properties += $part['properties'] ?? [];
        }

        return $properties;
    }

    /**
     * @param array<string, mixed> $pathItem
     *
     * @return list<string>
     */
    private static function methods(array $pathItem): array
    {
        $methods = array_values(array_intersect(['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'], array_keys($pathItem)));
        sort($methods);

        return $methods;
    }
}
