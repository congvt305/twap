<?php

declare(strict_types=1);

namespace Amasty\ShopbySeo\Model\UrlParser\Utils\Attribute;

use Amasty\ShopbySeo\Helper\Data;
use Amasty\ShopbySeo\Model\Source\OptionSeparator;
use Amasty\ShopbySeo\Model\UrlParser\Utils\AliasesDelimiterProvider;

class ParsingResultValidator
{
    /**
     * @var Data
     */
    private $seoHelper;

    /**
     * @var AliasesDelimiterProvider
     */
    private $delimiterProvider;

    /**
     * @var OptionSeparator
     */
    private $optionSeparator;

    public function __construct(
        Data $seoHelper,
        AliasesDelimiterProvider $delimiterProvider,
        OptionSeparator $optionSeparator
    ) {
        $this->seoHelper = $seoHelper;
        $this->delimiterProvider = $delimiterProvider;
        $this->optionSeparator = $optionSeparator;
    }

    /**
     * Runs parsing results validation
     *
     * @param string $seoPart
     * @param array $parsedAttributeCodes
     * @param array $parsedAliases
     * @return bool
     */
    public function validate(string $seoPart, array $parsedAttributeCodes, array $parsedAliases): bool
    {
        $requestedAliases = $this->getRequestedAliases($seoPart, $parsedAttributeCodes);
        $parsedAliases = implode('', $parsedAliases);
        return str_replace($this->getCharsForReplace(), '', $parsedAliases) === $requestedAliases;
    }

    private function getRequestedAliases(string $seoPart, array $attributeCodes)
    {
        $aliases = $attributeCodes;
        $aliases[] = $this->seoHelper->getFilterWord();
        $aliases = array_merge($aliases, $this->getCharsForReplace());
        return str_replace($aliases, '', $seoPart);
    }

    private function getCharsForReplace(): array
    {
        $chars = $this->optionSeparator->toArray();

        return array_values($chars);
    }
}
