<?php

declare(strict_types=1);

namespace Modules\V1\AI\Services;

use Modules\V1\AI\Contracts\AIToolHandler;
use Modules\V1\AI\DTO\AIToolDefinition;
use Modules\V1\AI\DTO\AIToolValidationResult;
use Modules\V1\AI\Tools\ApplicationInfoTool;
use Modules\V1\AI\Tools\AuthenticatedUserTool;
use Modules\V1\AI\Tools\CurrentTimeTool;
use Modules\V1\AI\Tools\NavigateTool;

final class AIToolRegistry
{
    /** @var array<string, AIToolHandler> */
    private array $handlers = [];

    public function __construct(
        CurrentTimeTool $currentTimeTool,
        AuthenticatedUserTool $authenticatedUserTool,
        ApplicationInfoTool $applicationInfoTool,
        NavigateTool $navigateTool,
    ) {
        foreach ([$currentTimeTool, $authenticatedUserTool, $applicationInfoTool, $navigateTool] as $handler) {
            $this->handlers[$handler->name()] = $handler;
        }
    }

    public function handler(string $name): ?AIToolHandler
    {
        return $this->handlers[$name] ?? null;
    }

    /** @return array<string, AIToolDefinition> */
    public function definitions(): array
    {
        $definitions = [];
        foreach ($this->handlers as $name => $handler) {
            $definitions[$name] = $handler->definition();
        }

        return $definitions;
    }

    /** @return list<array<string, mixed>> */
    public function nativeToolDefinitions(): array
    {
        return array_values(array_map(
            static fn (AIToolDefinition $definition): array => $definition->toNativeTool(),
            $this->definitions(),
        ));
    }

    /** @return list<string> */
    public function supportedTools(): array
    {
        return array_keys($this->handlers);
    }

    /** @param array<string, mixed> $arguments */
    public function validate(string $name, array $arguments): AIToolValidationResult
    {
        $definition = $this->definitions()[$name] ?? null;
        if (null === $definition) {
            return new AIToolValidationResult(false, ['Unsupported tool requested.']);
        }

        $required = $definition->inputSchema['required'] ?? [];
        if ( ! is_array($required)) {
            return new AIToolValidationResult(true);
        }

        $errors = [];
        foreach ($required as $field) {
            if (is_string($field) && ! array_key_exists($field, $arguments)) {
                $errors[] = "Missing required argument: {$field}.";
            }
        }

        $properties = $definition->inputSchema['properties'] ?? $definition->inputSchema;
        if (is_array($properties)) {
            foreach ($arguments as $field => $value) {
                if ( ! is_string($field) || ! isset($properties[$field]) || ! is_array($properties[$field])) {
                    continue;
                }

                $schema = $properties[$field];
                $type = $schema['type'] ?? null;
                if (is_string($type) && ! $this->matchesType($value, $type)) {
                    $errors[] = "Invalid argument type for {$field}. Expected {$type}.";
                }

                $enum = $schema['enum'] ?? null;
                if (is_array($enum) && ! in_array($value, $enum, true)) {
                    $errors[] = "Invalid value for {$field}.";
                }
            }
        }

        return new AIToolValidationResult([] === $errors, $errors);
    }

    private function matchesType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            'object' => is_array($value),
            default => true,
        };
    }
}
