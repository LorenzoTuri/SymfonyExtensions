<?php declare(strict_types = 1);

namespace Lturi\SymfonyExtensions\GraphQLApi\Type;

use DateTime;
use DateTimeImmutable;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

class DateTimeType extends ScalarType
{
    /**
     * @var string
     */
    public $name = 'DateTime';

    /**
     * @var string
     */
    public $description = 'The `DateTime` scalar type represents time data, represented as an ISO-8601 (ATOM) encoded UTC date string.';

    /**
     * @param mixed $value
     */
    public function serialize($value): string
    {
        if (! $value instanceof DateTimeImmutable) {
            throw new InvariantViolation('DateTime is not an instance of DateTimeImmutable: ' . Utils::printSafe($value));
        }
        return $value->format(DateTime::ATOM);
    }

    /**
     * @param mixed $value
     */
    public function parseValue($value): ?DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat(DateTime::ATOM, $value) ?: null;
    }

    /**
     * @param ValueNode $ast
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null)
    {
        if ($ast instanceof StringValueNode) {
            return $ast->value;
        }
        return null;
    }
}