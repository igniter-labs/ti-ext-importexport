<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Models;

use Igniter\Local\Models\Location;
use Override;

/**
 * LocationExport Model Class
 *
 * Exports locations using the same columns as LocationImport,
 * so an exported file can be imported again without remapping columns.
 */
class LocationExport extends ExportModel
{
    protected $table = 'locations';

    protected $primaryKey = 'location_id';

    #[Override]
    public function exportData(array $columns, array $options = []): array
    {
        $query = Location::query()->orderBy('location_id');

        if ($offset = array_get($options, 'offset')) {
            $query->offset($offset);
        }

        if ($limit = array_get($options, 'limit')) {
            $query->limit($limit);
        }

        return $query->get()->map(fn(Location $location): array => [
            'location_name' => $location->location_name,
            'location_email' => $location->location_email,
            'location_telephone' => $location->location_telephone,
            'description' => $location->description,
            'location_address_1' => $location->location_address_1,
            'location_address_2' => $location->location_address_2,
            'location_city' => $location->location_city,
            'location_state' => $location->location_state,
            'location_postcode' => $location->location_postcode,
            'location_country' => $this->countryCode($location->location_country_id),
            'location_lat' => $location->location_lat,
            'location_lng' => $location->location_lng,
            'location_status' => (int)$location->location_status,
        ])->all();
    }
}
