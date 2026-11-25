<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\OpenApi\Model\Operation;
use Dmstr\AppSettings\Merge\DeepMerge;
use Dmstr\AppSettings\Merge\MergePatch;
use Dmstr\AppSettings\OpenApi\SectionSchemaHolder;
use Dmstr\AppSettings\Registry\SectionDefinition;
use Dmstr\AppSettings\Security\AppSettingsPermissions;
use Dmstr\AppSettings\State\SectionCollectionProvider;
use Dmstr\AppSettings\State\SectionItemProvider;
use Dmstr\AppSettings\Validator\MatchesSectionSchema;
use Dmstr\OpenApiJsonSchema\Attribute\JsonSchema;
use Dmstr\OpenApiJsonSchema\Interface\JsonSchemaProviderInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Uid\Uuid;

/**
 * Stored values of one settings section. There is at most one row per
 * section; its id is derived deterministically from the section key (see
 * SectionRegistry::NAMESPACE), so a section has the same id before and
 * after its first write.
 *
 * Only the values that differ from or extend the defaults are stored
 * (`values_json`). The API exposes the effective values instead: stored
 * values merged over the section defaults, which the state providers
 * attach via {@see attachDefaults()}.
 *
 * The API reads every registered section through the state providers,
 * including sections without a stored row (effective values = defaults);
 * such a row is created on the first PATCH. The stored values are validated
 * against the section schema ({@see MatchesSectionSchema}), and the OpenAPI
 * schema of `values` lists one schema per section ({@see getJsonSchema()}).
 */
#[ORM\Entity]
#[ORM\Table(name: 'dmstr_app_setting')]
#[ORM\UniqueConstraint(name: 'uniq_dmstr_app_setting_section_key', columns: ['section_key'])]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\Loggable]
#[ApiResource(
    shortName: 'ApplicationSetting',
    operations: [
        new GetCollection(provider: SectionCollectionProvider::class),
        new Get(provider: SectionItemProvider::class),
        new Patch(provider: SectionItemProvider::class),
    ],
    routePrefix: '/admin',
    security: "is_granted('".AppSettingsPermissions::SETTING_MANAGE."')",
    paginationEnabled: false,
    description: <<<'MD'
        Application settings are grouped into sections; each section configures one area of the application and comes with defaults.

        Every section is listed, including ones that were never changed. You see the effective values (your changes on top of the defaults) and edit them here; only what differs from the defaults is stored, and changes are checked against the section's rules before they are saved.
        MD,
    openapi: new Operation(tags: ['System']),
)]
#[MatchesSectionSchema]
class ApplicationSetting implements JsonSchemaProviderInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[ApiProperty(identifier: true, writable: false, description: 'Deterministic identifier derived from the section key')]
    private Uuid $id;

    #[ORM\Column(name: 'section_key', type: Types::STRING, length: 100)]
    #[ApiProperty(writable: false, description: 'Section key')]
    private string $sectionKey;

    /** Stored user values only (without defaults). Never exposed by the API. */
    #[ORM\Column(name: 'values_json', type: Types::JSON, options: ['jsonb' => true])]
    #[Gedmo\Versioned]
    #[ApiProperty(readable: false, writable: false)]
    private array $storedValues = [];

    /** Not mapped: carries the API metadata of the effective values; see getValues()/setValues(). */
    #[JsonSchema(schemaName: 'values')]
    #[ApiProperty(description: 'Effective values: stored values merged over the section defaults. Written with JSON Merge Patch semantics (objects merge recursively, arrays are replaced, null removes a key).')]
    private array $values = [];

    /** Not mapped: section defaults, attached by the state providers. */
    private array $defaults = [];

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[ApiProperty(writable: false)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[ApiProperty(writable: false)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $sectionKey, Uuid $id)
    {
        $this->sectionKey = $sectionKey;
        $this->id = $id;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSectionKey(): string
    {
        return $this->sectionKey;
    }

    /**
     * @return array<string, mixed>
     */
    public function getStoredValues(): array
    {
        return $this->storedValues;
    }

    /**
     * @param array<string, mixed> $storedValues
     */
    public function setStoredValues(array $storedValues): void
    {
        $this->storedValues = $storedValues;
    }

    /**
     * Effective values: stored values merged over the attached defaults.
     *
     * @return array<string, mixed>
     */
    public function getValues(): array
    {
        return DeepMerge::merge($this->defaults, $this->storedValues);
    }

    /**
     * Applies $patch to the stored values with JSON Merge Patch semantics
     * (RFC 7396, see {@see MergePatch::apply()}).
     *
     * @param array<string, mixed> $patch
     */
    public function setValues(array $patch): void
    {
        $this->storedValues = MergePatch::apply($this->storedValues, $patch);
    }

    /**
     * @param array<string, mixed> $defaults
     */
    public function attachDefaults(array $defaults): void
    {
        $this->defaults = $defaults;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * OpenAPI schema of `values` (called statically by
     * JsonFieldSchemaDecorator): one branch per registered section, each
     * with `additionalProperties: false` and the `section_key` const, so an
     * admin panel can pick the form by `section_key`. Null while the holder
     * is not primed or for other fields; the decorator then keeps the
     * generated schema.
     *
     * @return array<string, mixed>|null
     */
    public static function getJsonSchema(string $fieldName): ?array
    {
        $registry = SectionSchemaHolder::getRegistry();
        if ('values' !== $fieldName || null === $registry) {
            return null;
        }

        return [
            'description' => 'Effective values of the section; one schema per section, discriminated by section_key.',
            'anyOf' => array_values(array_map(
                static fn (SectionDefinition $definition): array => $definition->schema,
                $registry->all(),
            )),
        ];
    }
}
