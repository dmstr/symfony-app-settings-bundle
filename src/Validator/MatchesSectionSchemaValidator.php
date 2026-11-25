<?php

// file generated with AI assistance: Claude Code - 2026-09-30 16:45:00 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Validator;

use Dmstr\AppSettings\Entity\ApplicationSetting;
use Dmstr\AppSettings\Registry\SectionRegistry;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\JsonPointer;
use Opis\JsonSchema\Validator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validates {@see MatchesSectionSchema}: the stored values of a settings
 * row (after the merge patch was applied) against the JSON schema of its
 * section, with opis/json-schema.
 *
 * Only the stored values are validated, not the effective values: the
 * defaults come from the schema itself, and an empty set of stored values
 * (all defaults) is valid.
 *
 * Every leaf error becomes one violation whose property path is `values`
 * followed by the JSON pointer of the offending value, e.g.
 * `values/retention_days`; API Platform reports it as
 * `violations[].propertyPath`. A property rejected by
 * `additionalProperties: false` is reported at its own path
 * (`values/<name>`) instead of at the containing object.
 */
final class MatchesSectionSchemaValidator extends ConstraintValidator
{
    /** Upper bound of collected schema errors per validation. */
    private const int MAX_ERRORS = 50;

    public function __construct(
        private readonly SectionRegistry $registry,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MatchesSectionSchema) {
            throw new UnexpectedTypeException($constraint, MatchesSectionSchema::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof ApplicationSetting) {
            throw new UnexpectedValueException($value, ApplicationSetting::class);
        }

        $sectionKey = $value->getSectionKey();
        if (!$this->registry->has($sectionKey)) {
            $this->context->buildViolation($constraint->unknownSectionMessage)
                ->atPath('sectionKey')
                ->setParameter('{{ section }}', $this->formatValue($sectionKey))
                ->setCode(MatchesSectionSchema::UNKNOWN_SECTION_ERROR)
                ->addViolation();

            return;
        }

        $schema = $this->registry->get($sectionKey)->schema;

        // The schema never contains an empty array where an object is meant
        // (the registry always adds `properties.section_key`), so the plain
        // JSON round trip is safe here. The data needs the schema-guided
        // conversion, see JsonValue.
        $schemaObject = json_decode(json_encode($schema, \JSON_THROW_ON_ERROR), false, 512, \JSON_THROW_ON_ERROR);
        $data = JsonValue::fromPhp($value->getStoredValues(), $schema);

        $result = (new Validator(null, self::MAX_ERRORS, false))->validate($data, $schemaObject);
        $error = $result->error();
        if (null === $error) {
            return;
        }

        $formatter = new ErrorFormatter();
        foreach (self::leafErrors($error) as $leaf) {
            foreach (self::describe($leaf, $formatter) as [$path, $message]) {
                $this->context->buildViolation($constraint->message)
                    ->atPath('/' === $path ? 'values' : 'values'.$path)
                    ->setParameter('{{ path }}', $path)
                    ->setParameter('{{ error }}', $message)
                    ->setInvalidValue($value->getStoredValues())
                    ->setCode(MatchesSectionSchema::SCHEMA_MISMATCH_ERROR)
                    ->addViolation();
            }
        }
    }

    /**
     * @return iterable<ValidationError>
     */
    private static function leafErrors(ValidationError $error): iterable
    {
        $subErrors = $error->subErrors();
        if ([] === $subErrors) {
            yield $error;

            return;
        }

        foreach ($subErrors as $subError) {
            yield from self::leafErrors($subError);
        }
    }

    /**
     * JSON pointers and messages the error is reported with: the path of
     * the offending value, or for rejected additional properties the path of
     * each undeclared property (possibly none, see below).
     *
     * @return list<array{0: string, 1: string}>
     */
    private static function describe(ValidationError $error, ErrorFormatter $formatter): array
    {
        $path = $error->data()->fullPath();
        $properties = $error->args()['properties'] ?? null;

        if ('additionalProperties' === $error->keyword() && \is_array($properties) && [] !== $properties) {
            // When opis does not stop at the first error, a declared
            // property whose value failed its own schema is also listed as
            // "additional" (it never counted as evaluated). Its own error is
            // already reported, so only undeclared properties are kept.
            $declared = $error->schema()->info()->data();
            $declared = \is_object($declared) && \is_object($declared->properties ?? null) ? $declared->properties : new \stdClass();

            $paths = [];
            foreach ($properties as $property) {
                if (!property_exists($declared, (string) $property)) {
                    $paths[] = [
                        JsonPointer::pathToString([...$path, (string) $property]),
                        \sprintf('The property "%s" is not allowed.', $property),
                    ];
                }
            }

            return $paths;
        }

        return [[JsonPointer::pathToString($path), $formatter->formatErrorMessage($error)]];
    }
}
