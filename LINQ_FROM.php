<?php

namespace LINQ {

    class From
    {
        public const OP_EQUALS = 1;
        public const OP_NOT_EQUALS = 2;
        public const OP_IN = 3;
        public const OP_NOT_IN = 4;
        public const OP_LESS_THAN = 5;
        public const OP_GREATER_THAN = 6;
        public const OP_BETWEEN = 7;

        private ?array $__select = null;
        private ?array $__collection = null;

        private $__pathCache = [];

        public function __construct(private string $selector)
        {
        }

        public function __destruct()
        {
            unset($this->__select);
            unset($this->__collection);
            unset($this->__pathCache);
            gc_collect_cycles();
        }

        public function IN(array $Collection): self
        {
            $this->__collection = $Collection;
            return $this;
        }

        public function SELECT(string $SelectPaths): self
        {
            $SelectPaths = trim($SelectPaths);
            if ($SelectPaths === '*') {
                $this->__select = [$SelectPaths];
            } else {
                $this->__select = array_map(function ($sel) {
                    return trim($sel, " *\t\r\n\0\x0B");
                }, explode(',', $SelectPaths));
            }
            return $this;
        }

        public function Generate()
        {
            if (count($this->__select) === 1 && $this->__select[0] === '*') {
                foreach ($this->__collection as $Value) {
                    yield $Value;
                }
            } else {
                foreach ($this->__collection as $value) {
                    $yieldValue = [];
                    foreach ($this->__select as $pathString) {
                        $colVal = $this->AtPath($pathString, $value);
                        $yieldValue[$this->__pathCache[$pathString][0]] = $colVal;
                    }
                    yield $yieldValue;
                }
            }
        }

        public function Count(): int
        {
            return count($this->__collection);
        }

        public function Result(): array
        {
            $result = [];
            foreach ($this->Generate() as $Value) {
                $result[] = $Value;
            }
            return $result;
        }

        public function Sum(string $Selector): int|float
        {
            $sum = 0.0;
            foreach ($this->__collection as $value) {
                $sum += $this->AtPath($Selector, $value);
            }
            return $sum;
        }

        public function OrderBy(string $Selector, int $ArraySortType = SORT_ASC)
        {
            if ($ArraySortType === SORT_ASC) {
                if (!uasort($this->__collection, function (mixed $a, mixed $b) use ($Selector) {
                    if ($this->AtPath($Selector, $a) === $this->AtPath($Selector, $b)) {
                        return 0;
                    }
                    return $this->AtPath($Selector, $a) < $this->AtPath($Selector, $b) ? -1 : 1;
                })) {
                    throw new \Exception('Unable to sort');
                }
            } else if ($ArraySortType === SORT_DESC) {
                if (!uasort($this->__collection, function (mixed $a, mixed $b) use ($Selector) {
                    if ($this->AtPath($Selector, $a) === $this->AtPath($Selector, $b)) {
                        return 0;
                    }
                    return $this->AtPath($Selector, $a) < $this->AtPath($Selector, $b) ? 1 : -1;
                })) {
                    throw new \Exception('Unable to sort');
                }
            }
            return $this;
        }

        public function Where(string $Selector, int $Operator, mixed $Value): self
        {
            switch ($Operator) {
                case self::OP_EQUALS:
                    foreach ($this->__collection as $Key => $comp) {
                        if ($this->AtPath($Selector, $comp) != $Value) {
                            unset($this->__collection[$Key]);
                        }
                    }
                    break;
                case self::OP_NOT_EQUALS:
                    foreach ($this->__collection as $Key => $comp) {
                        if ($this->AtPath($Selector, $comp) == $Value) {
                            unset($this->__collection[$Key]);
                        }
                    }
                    break;
                case self::OP_IN:
                    foreach ($this->__collection as $Key => $comp) {
                        if (!in_array($this->AtPath($Selector, $comp), $Value)) {
                            unset($this->__collection[$Key]);
                        }
                    }
                    break;
                case self::OP_NOT_IN:
                    foreach ($this->__collection as $Key => $comp) {
                        if (in_array($this->AtPath($Selector, $comp), $Value)) {
                            unset($this->__collection[$Key]);
                        }
                    }
                    break;
                case self::OP_GREATER_THAN:
                    foreach ($this->__collection as $Key => $comp) {
                        if ($this->AtPath($Selector, $comp) <= $Value) {
                            unset($this->__collection[$Key]);
                        }
                    }
                    break;
                case self::OP_LESS_THAN:
                    foreach ($this->__collection as $Key => $comp) {
                        if ($this->AtPath($Selector, $comp) >= $Value) {
                            unset($this->__collection[$Key]);
                        }
                    }
                    break;
                case self::OP_BETWEEN:
                    if (!is_array($Value) || count($Value) !== 2) {
                        throw new \Exception('Range query requires an array with start and end range');
                    }
                    foreach ($this->__collection as $Key => $comp) {
                        $AtVal = $this->AtPath($Selector, $comp);
                        if ($AtVal <= $Value[0] || $AtVal >= $Value[1]) {
                            unset($this->__collection[$Key]);
                        }
                    }
                    break;
                default:
                    throw new \Exception('Unknown operation');
            }
            $this->__collection = array_values($this->__collection);
            var_dump(['GC' => gc_collect_cycles()]);
            return $this;
        }

        private function AtPath(string $path, $collection): mixed
        {
            $Paths = $this->SplitPaths($path);
            foreach ($Paths as $sec) {
                if ($sec === '*') {
                    break;
                }
                $collection = $collection[$sec];
            }
            return $collection;
        }

        private function SplitPaths(string $Path): array
        {
            if (!str_starts_with($Path, $this->selector)) {
                throw new \Exception('No selectors found');
            }
            if (isset($this->__pathCache[$Path])) {
                return $this->__pathCache[$Path][1];
            }
            $Comps = [];
            $Escape = false;
            for ($ind = 0, $i = 0; $i < strlen($Path); $i++) {
                switch ($Path[$i]) {
                    case '\\':
                        $Escape = true;
                        break;
                    case '.':
                        if (!$Escape) {
                            $ind++;
                            $Escape = false;
                            break;
                        }
                    default: {
                            $Escape = false;
                            if (!isset($Comps[$ind])) {
                                $Comps[$ind] = '';
                            }
                            $Comps[$ind] .= $Path[$i];
                            break;
                        }
                }
            }
            array_shift($Comps);
            $name = end($Comps);
            $this->__pathCache[$Path] = [$name, $Comps];
            return $Comps;
        }
    }




    $TestArray = [
        [
            'Bio' => [
                'Id' => 1,
                'Name' => 'John',
                'Age' => 25,
            ],
            'Credit' => 800
        ],
        [
            'Bio' => [
                'Id' => 1,
                'Name' => 'Wesley',
                'Age' => 26,
            ],
            'Credit' => 900
        ]
    ];

    $from = new From('List');
    var_dump($from->IN($TestArray)->OrderBy('List.Bio.Name', SORT_DESC)->SELECT('List.Bio.*, List.Credit')->Result());
}
