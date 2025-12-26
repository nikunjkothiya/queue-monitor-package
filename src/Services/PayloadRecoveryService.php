<?php

namespace NikunjKothiya\QueueMonitor\Services;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use NikunjKothiya\QueueMonitor\Models\QueueFailure;

/**
 * Payload Recovery Service - The heart of data loss prevention.
 * 
 * This service ensures that failed jobs with important data can be:
 * 1. Validated before retry
 * 2. Fixed (payload modification)
 * 3. Safely re-dispatched
 * 4. Audited for compliance
 */
class PayloadRecoveryService
{
    /**
     * Validation errors from last operation.
     */
    protected array $errors = [];
    
    /**
     * Analyze a payload and return structured information.
     */
    public function analyzePayload(?string $payload): array
    {
        if (!$payload) {
            return [
                'valid' => false,
                'error' => 'No payload available',
                'recoverable' => false,
            ];
        }
        
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return [
                'valid' => false,
                'error' => 'Invalid JSON: ' . $e->getMessage(),
                'recoverable' => false,
            ];
        }
        
        // Check for Laravel job structure
        if (!isset($decoded['data']['command'])) {
            return [
                'valid' => false,
                'error' => 'Missing job command in payload',
                'recoverable' => false,
                'raw_data' => $decoded,
            ];
        }
        
        // Try to unserialize the job
        try {
            $job = @unserialize($decoded['data']['command'], ['allowed_classes' => true]);
            
            if (!is_object($job)) {
                return [
                    'valid' => false,
                    'error' => 'Failed to unserialize job - class may not exist',
                    'recoverable' => false,
                    'class_hint' => $this->extractClassFromSerialized($decoded['data']['command']),
                ];
            }
            
            $jobClass = get_class($job);
            $properties = $this->extractProperties($job);
            
            return [
                'valid' => true,
                'recoverable' => true,
                'job_class' => $jobClass,
                'job_class_exists' => class_exists($jobClass),
                'properties' => $properties,
                'editable_properties' => $this->getEditableProperties($properties),
                'queue_info' => [
                    'connection' => $decoded['data']['connection'] ?? null,
                    'queue' => $decoded['data']['queue'] ?? null,
                ],
                'metadata' => [
                    'uuid' => $decoded['uuid'] ?? null,
                    'displayName' => $decoded['displayName'] ?? null,
                    'maxTries' => $decoded['maxTries'] ?? null,
                    'timeout' => $decoded['timeout'] ?? null,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'error' => 'Unserialize error: ' . $e->getMessage(),
                'recoverable' => false,
                'class_hint' => $this->extractClassFromSerialized($decoded['data']['command'] ?? ''),
            ];
        }
    }
    
    /**
     * Extract class name from serialized string without unserializing.
     */
    protected function extractClassFromSerialized(string $serialized): ?string
    {
        if (preg_match('/^O:\d+:"([^"]+)"/', $serialized, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    /**
     * Extract all properties from a job object.
     */
    protected function extractProperties(object $job): array
    {
        $reflection = new \ReflectionClass($job);
        $properties = [];
        
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $name = $property->getName();
            
            try {
                $value = $property->getValue($job);
                $type = $this->getValueType($value);
                
                $properties[$name] = [
                    'value' => $this->serializeValue($value),
                    'type' => $type,
                    'original_type' => gettype($value),
                    'is_object' => is_object($value),
                    'class' => is_object($value) ? get_class($value) : null,
                ];
            } catch (\Throwable $e) {
                $properties[$name] = [
                    'value' => null,
                    'type' => 'unreadable',
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $properties;
    }
    
    /**
     * Get properties that can be safely edited.
     */
    protected function getEditableProperties(array $properties): array
    {
        $editable = [];
        
        // Laravel internal properties that shouldn't be edited
        $internalProps = [
            'job', 'connection', 'queue', 'chainConnection', 'chainQueue',
            'delay', 'middleware', 'chained', 'afterCommit', 'tries',
            'maxExceptions', 'backoff', 'timeout', 'failOnTimeout',
        ];
        
        foreach ($properties as $name => $info) {
            if (in_array($name, $internalProps)) {
                continue;
            }
            
            // Only allow editing of scalar types and simple arrays
            if (in_array($info['type'], ['string', 'integer', 'float', 'boolean', 'null', 'array'])) {
                if ($info['type'] === 'array' && $info['is_object']) {
                    continue; // Skip arrays containing objects
                }
                $editable[$name] = $info;
            }
        }
        
        return $editable;
    }
    
    /**
     * Get a simplified type for UI display.
     */
    protected function getValueType($value): string
    {
        if (is_null($value)) return 'null';
        if (is_bool($value)) return 'boolean';
        if (is_int($value)) return 'integer';
        if (is_float($value)) return 'float';
        if (is_string($value)) return 'string';
        if (is_array($value)) return 'array';
        if (is_object($value)) return 'object';
        return 'unknown';
    }
    
    /**
     * Serialize a value for JSON transport.
     */
    protected function serializeValue($value)
    {
        if (is_scalar($value) || is_null($value)) {
            return $value;
        }
        
        if (is_array($value)) {
            return array_map([$this, 'serializeValue'], $value);
        }
        
        if (is_object($value)) {
            // For Eloquent models, return identifying info
            if ($value instanceof \Illuminate\Database\Eloquent\Model) {
                return [
                    '__type' => 'model',
                    '__class' => get_class($value),
                    '__key' => $value->getKey(),
                    '__exists' => $value->exists,
                ];
            }
            
            // For other objects, return class info
            return [
                '__type' => 'object',
                '__class' => get_class($value),
            ];
        }
        
        return null;
    }
    
    /**
     * Validate modified properties before applying.
     */
    public function validateModifications(array $original, array $modifications): bool
    {
        $this->errors = [];
        
        foreach ($modifications as $key => $newValue) {
            if (!isset($original[$key])) {
                $this->errors[$key] = "Property '{$key}' does not exist on the job";
                continue;
            }
            
            $originalType = $original[$key]['type'];
            $newType = $this->getValueType($newValue);
            
            // Allow null for any type
            if ($newType === 'null') {
                continue;
            }
            
            // Type coercion rules
            $compatible = match ($originalType) {
                'integer' => in_array($newType, ['integer', 'string']) && is_numeric($newValue),
                'float' => in_array($newType, ['float', 'integer', 'string']) && is_numeric($newValue),
                'string' => true, // Anything can become a string
                'boolean' => in_array($newType, ['boolean', 'integer', 'string']),
                'array' => $newType === 'array',
                default => $newType === $originalType,
            };
            
            if (!$compatible) {
                $this->errors[$key] = "Type mismatch for '{$key}': expected {$originalType}, got {$newType}";
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Get validation errors from last operation.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Reconstruct a job with modified properties.
     */
    public function reconstructJob(string $originalPayload, array $modifications): ?object
    {
        try {
            $decoded = json_decode($originalPayload, true, 512, JSON_THROW_ON_ERROR);
            
            if (!isset($decoded['data']['command'])) {
                $this->errors['payload'] = 'Invalid payload structure';
                return null;
            }
            
            $job = unserialize($decoded['data']['command'], ['allowed_classes' => true]);
            
            if (!is_object($job)) {
                $this->errors['job'] = 'Failed to unserialize job';
                return null;
            }
            
            $reflection = new \ReflectionClass($job);
            
            foreach ($modifications as $key => $value) {
                if (!$reflection->hasProperty($key)) {
                    continue;
                }
                
                $property = $reflection->getProperty($key);
                $property->setAccessible(true);
                
                // Type coercion
                $originalValue = $property->getValue($job);
                $coercedValue = $this->coerceValue($value, gettype($originalValue));
                
                $property->setValue($job, $coercedValue);
            }
            
            return $job;
        } catch (\Throwable $e) {
            $this->errors['reconstruction'] = $e->getMessage();
            return null;
        }
    }
    
    /**
     * Coerce a value to the expected type.
     */
    protected function coerceValue($value, string $targetType)
    {
        if (is_null($value)) {
            return null;
        }
        
        return match ($targetType) {
            'integer' => (int) $value,
            'double', 'float' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string) $value,
            'array' => (array) $value,
            default => $value,
        };
    }
    
    /**
     * Safely retry a failure with optional modifications.
     */
    public function retryWithModifications(
        QueueFailure $failure,
        array $modifications = [],
        ?int $userId = null,
        ?string $notes = null
    ): array {
        // Analyze current payload
        $analysis = $this->analyzePayload($failure->payload);
        
        if (!$analysis['valid'] || !$analysis['recoverable']) {
            return [
                'success' => false,
                'error' => $analysis['error'] ?? 'Payload is not recoverable',
            ];
        }
        
        // Validate modifications if any
        if (!empty($modifications)) {
            if (!$this->validateModifications($analysis['editable_properties'], $modifications)) {
                return [
                    'success' => false,
                    'error' => 'Validation failed',
                    'validation_errors' => $this->errors,
                ];
            }
        }
        
        // Reconstruct job
        $job = $this->reconstructJob($failure->payload, $modifications);
        
        if (!$job) {
            return [
                'success' => false,
                'error' => 'Failed to reconstruct job',
                'reconstruction_errors' => $this->errors,
            ];
        }
        
        // Dispatch
        try {
            Bus::dispatch($job);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Failed to dispatch: ' . $e->getMessage(),
            ];
        }
        
        // Update failure record
        $updateData = [
            'retry_count' => $failure->retry_count + 1,
            'last_retried_at' => now(),
            'retried_by' => $userId,
        ];
        
        if (!empty($modifications)) {
            // Store the modified payload for audit
            $decoded = json_decode($failure->payload, true);
            $decoded['data']['command'] = serialize($job);
            $updateData['modified_payload'] = json_encode($decoded);
        }
        
        if ($notes) {
            $updateData['retry_notes'] = $notes;
        }
        
        $failure->update($updateData);
        
        // Log for audit
        Log::info('Queue Monitor: Job retried', [
            'failure_id' => $failure->id,
            'job_class' => get_class($job),
            'modifications' => array_keys($modifications),
            'user_id' => $userId,
        ]);
        
        return [
            'success' => true,
            'message' => 'Job dispatched successfully',
            'retry_count' => $failure->retry_count,
            'modifications_applied' => !empty($modifications),
        ];
    }
    
    /**
     * Check if a job class still exists and is valid.
     */
    public function validateJobClass(string $className): array
    {
        if (!class_exists($className)) {
            return [
                'valid' => false,
                'error' => "Class '{$className}' does not exist",
                'suggestion' => 'The job class may have been renamed or deleted',
            ];
        }
        
        $reflection = new \ReflectionClass($className);
        
        // Check if it implements ShouldQueue
        $implementsQueue = $reflection->implementsInterface(\Illuminate\Contracts\Queue\ShouldQueue::class);
        
        // Check for handle method
        $hasHandle = $reflection->hasMethod('handle');
        
        return [
            'valid' => true,
            'implements_should_queue' => $implementsQueue,
            'has_handle_method' => $hasHandle,
            'is_abstract' => $reflection->isAbstract(),
            'constructor_params' => $this->getConstructorParams($reflection),
        ];
    }
    
    /**
     * Get constructor parameters for a class.
     */
    protected function getConstructorParams(\ReflectionClass $reflection): array
    {
        $constructor = $reflection->getConstructor();
        
        if (!$constructor) {
            return [];
        }
        
        $params = [];
        foreach ($constructor->getParameters() as $param) {
            $params[] = [
                'name' => $param->getName(),
                'type' => $param->getType()?->getName(),
                'required' => !$param->isOptional(),
                'default' => $param->isOptional() ? $param->getDefaultValue() : null,
            ];
        }
        
        return $params;
    }
}
