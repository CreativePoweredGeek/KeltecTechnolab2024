<?php

declare (strict_types=1);
namespace CartThrob\Dependency\Money\Parser;

use CartThrob\Dependency\Money\Currency;
use CartThrob\Dependency\Money\Exception;
use CartThrob\Dependency\Money\Money;
use CartThrob\Dependency\Money\MoneyParser;
use function sprintf;
/**
 * Parses a string into a Money object using other parsers.
 */
final class AggregateMoneyParser implements MoneyParser
{
    /**
     * @var MoneyParser[]
     * @psalm-var non-empty-array<MoneyParser>
     */
    private array $parsers;
    /**
     * @param MoneyParser[] $parsers
     * @psalm-param non-empty-array<MoneyParser> $parsers
     */
    public function __construct(array $parsers)
    {
        $this->parsers = $parsers;
    }
    public function parse(string $money, Currency|null $fallbackCurrency = null) : Money
    {
        foreach ($this->parsers as $parser) {
            try {
                return $parser->parse($money, $fallbackCurrency);
            } catch (Exception\ParserException $e) {
            }
        }
        throw new Exception\ParserException(sprintf('Unable to parse %s', $money));
    }
}
