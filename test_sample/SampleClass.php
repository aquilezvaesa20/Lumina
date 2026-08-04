<?php

namespace App\Test;

use stdClass;

/**
 * Sample class for testing
 */
abstract class SampleClass extends stdClass implements \Countable
{
    /**
     * @var string
     */
    public string $name;
    
    protected static int $counter = 0;
    
    final public const VERSION = '1.0';
    
    /**
     * Constructor
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    
    /**
     * Get the name
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    public function count(): int
    {
        return 1;
    }
}

/**
 * Global function
 */
function helper_function(array $data, ?string $prefix = null): array
{
    return $data;
}
