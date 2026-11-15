<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Traits;

use Igniter\Flame\Exception\SystemException;
use Igniter\Local\Models\Location;

trait UpsertsLocations
{
    use ResolvesCountries;

    /**
     * Create or update a location from an import row. The default location is never changed.
     *
     * @param array{
     *     location_name: string,
     *     location_email?: ?string,
     *     location_telephone?: ?string,
     *     description?: ?string,
     *     location_address_1?: ?string,
     *     location_address_2?: ?string,
     *     location_city?: ?string,
     *     location_state?: ?string,
     *     location_postcode?: ?string,
     *     location_country?: ?string,
     *     location_lat?: float|string|null,
     *     location_lng?: float|string|null,
     *     location_status?: ?bool,
     * } $data
     */
    protected function upsertLocation(array $data, bool $updateExisting): Location
    {
        $name = trim((string)$data['location_name']);

        /** @var Location|null $location */
        $location = Location::query()->firstWhere('location_name', $name);
        throw_if($location && !$updateExisting, new SystemException(sprintf(
            lang('igniterlabs.importexport::default.error_location_exists'), $name,
        )));

        $location ??= (new Location)->forceFill(['location_name' => $name]);

        $attributes = array_filter(array_only($data, [
            'location_email', 'location_telephone', 'description',
            'location_address_1', 'location_address_2', 'location_city',
            'location_state', 'location_postcode', 'location_lat', 'location_lng',
            'location_status',
        ]), fn($value): bool => !is_null($value) && $value !== '');

        if (array_key_exists('location_country', $data) || !$location->exists) {
            $attributes['location_country_id'] = $this->resolveCountryId($data['location_country'] ?? null);
        }

        $location->forceFill($attributes);

        $hasCoordinates = filled($data['location_lat'] ?? null) && filled($data['location_lng'] ?? null);
        $location->is_auto_lat_lng = $hasCoordinates ? 0 : 1;

        if (!$location->exists) {
            $location->location_email ??= '';
            $location->location_status ??= true;
        }

        $location->save();

        return $location;
    }
}
