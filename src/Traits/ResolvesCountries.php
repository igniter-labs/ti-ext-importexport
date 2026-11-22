<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Traits;

use Igniter\System\Models\Country;

trait ResolvesCountries
{
    protected array $countryIdCache = [];

    /**
     * Resolve a country ISO code (2 or 3 letters) or name to a country ID,
     * falling back to the default country when no match is found.
     */
    protected function resolveCountryId(mixed $country): ?int
    {
        $country = strtolower(trim((string)$country));
        if ($country === '') {
            return $this->defaultCountryId();
        }

        if (!array_key_exists($country, $this->countryIdCache)) {
            $countryId = Country::query()
                ->whereRaw('LOWER(iso_code_2) = ?', [$country])
                ->orWhereRaw('LOWER(iso_code_3) = ?', [$country])
                ->orWhereRaw('LOWER(country_name) = ?', [$country])
                ->value('country_id');

            $this->countryIdCache[$country] = $countryId ? (int)$countryId : $this->defaultCountryId();
        }

        return $this->countryIdCache[$country];
    }

    protected function defaultCountryId(): ?int
    {
        $countryId = Country::getDefaultKey();

        return $countryId ? (int)$countryId : null;
    }
}
