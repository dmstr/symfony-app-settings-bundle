<?php

// file generated with AI assistance: Claude Code - 2026-09-30 16:45:00 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\OpenApi;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryAwareInterface;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\Operation;
use Dmstr\AppSettings\Registry\SectionRegistry;

/**
 * Pass-through decorator of `api_platform.json_schema.schema_factory` whose
 * only job is to prime {@see SectionSchemaHolder} with the SectionRegistry.
 *
 * Why: `JsonFieldSchemaDecorator` (dmstr/openapi-json-schema-bundle) replaces
 * the `values` property of ApplicationSetting with the result of the
 * entity's static `getJsonSchema('values')`, which has no access to
 * services. Constructing the decorator chain constructs this decorator, so
 * the holder is primed before any schema is built. Registered with a higher
 * decoration priority (20) than `JsonFieldSchemaDecorator` (10), i.e. closer
 * to the inner factory; `buildSchema()` purely delegates.
 */
final class SectionSchemaFactoryDecorator implements SchemaFactoryInterface, SchemaFactoryAwareInterface
{
    public function __construct(
        private readonly SchemaFactoryInterface $decorated,
        SectionRegistry $registry,
    ) {
        SectionSchemaHolder::setRegistry($registry);
    }

    public function buildSchema(
        string $className,
        string $format = 'json',
        string $type = Schema::TYPE_OUTPUT,
        ?Operation $operation = null,
        ?Schema $schema = null,
        ?array $serializerContext = null,
        bool $forceCollection = false,
    ): Schema {
        return $this->decorated->buildSchema($className, $format, $type, $operation, $schema, $serializerContext, $forceCollection);
    }

    public function setSchemaFactory(SchemaFactoryInterface $schemaFactory): void
    {
        if ($this->decorated instanceof SchemaFactoryAwareInterface) {
            $this->decorated->setSchemaFactory($schemaFactory);
        }
    }
}
