<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Models;

use Exception;
use IgniterLabs\ImportExport\Traits\UpsertsLocations;
use Illuminate\Support\Facades\Validator;
use Override;

/**
 * LocationImport Model Class
 *
 * @property bool $update_existing
 */
class LocationImport extends ImportModel
{
    use UpsertsLocations;

    protected $table = 'locations';

    protected $primaryKey = 'location_id';

    #[Override]
    public function importData(array $results): void
    {
        foreach ($results as $row => $data) {
            try {
                if (array_key_exists('location_status', $data)) {
                    $data['location_status'] = $this->castBoolean($data['location_status']);
                }

                $validated = Validator::validate($data, [
                    'location_name' => 'required|string|between:2,255',
                    'location_email' => 'nullable|email:filter|max:96',
                    'location_telephone' => 'nullable|string|max:32',
                    'description' => 'nullable|string',
                    'location_address_1' => 'nullable|string|max:255',
                    'location_address_2' => 'nullable|string|max:255',
                    'location_city' => 'nullable|string|max:255',
                    'location_state' => 'nullable|string|max:255',
                    'location_postcode' => 'nullable|string|max:15',
                    'location_country' => 'nullable|string|max:255',
                    'location_lat' => 'nullable|numeric|between:-90,90',
                    'location_lng' => 'nullable|numeric|between:-180,180',
                    'location_status' => 'nullable|boolean',
                ]);

                $location = $this->upsertLocation($validated, (bool)$this->update_existing);
                $location->wasRecentlyCreated ? $this->logCreated() : $this->logUpdated();
            } catch (Exception $ex) {
                $this->logError($row, $ex->getMessage());
            }
        }
    }
}
