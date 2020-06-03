<?php
/**
* Extend this class into your enum class and simply declare your enums as constants
* Call getValue to get the value of the enum
* Example:  SCROLL DOWN
*/
class Enum
{
    protected static $__cache = null;
    public function __construct($Value)
    {
        if (self::$__cache === null) {
            self::$__cache = (new \ReflectionClass(get_class($this)))->getConstants();
        }
        if (!in_array($Value, self::$__cache)) {
            throw new \Exception('Invalid value in ENUM');
        }
        $this->scalar = $Value;
    }

    function getValue()
    {
        return $this->scalar;
    }

    function __serialize(): array
    {
        return self::$__cache;
    }

    function ToArray(): array
    {
        return self::$__cache;
    }

    function __toString()
    {
        return array_search($this->getValue(), self::$__cache);
    }
}

// ---------------------EXAMPLE---------------------

class Days extends \Lib\Types\Enum
{
    public const Monday = 1;
    public const Tuesday = 2;
}

$MyDay = new Days(Days::Tuesday);
// OUTPUT: int(2)
var_dump($MyDay->getValue());
// OUTPUT: string(7) "Tuesday"
var_dump((string) $MyDay);
