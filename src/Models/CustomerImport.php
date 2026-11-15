<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Models;

use Exception;
use IgniterLabs\ImportExport\Traits\UpsertsCustomers;
use Illuminate\Support\Facades\Validator;
use Override;

/**
 * CustomerImport Model Class
 *
 * @property bool $update_existing
 */
class CustomerImport extends ImportModel
{
    use UpsertsCustomers;

    protected $table = 'customers';

    protected $primaryKey = 'customer_id';

    #[Override]
    public function importData(array $results): void
    {
        foreach ($results as $row => $data) {
            try {
                foreach (['newsletter', 'status'] as $booleanField) {
                    if (array_key_exists($booleanField, $data)) {
                        $data[$booleanField] = $this->castBoolean($data[$booleanField]);
                    }
                }

                $validated = Validator::validate($data, [
                    'email' => 'required|email:filter|max:96',
                    'first_name' => 'nullable|string|max:255',
                    'last_name' => 'nullable|string|max:255',
                    'full_name' => 'nullable|string|max:255',
                    'telephone' => 'nullable|string|max:32',
                    'newsletter' => 'nullable|boolean',
                    'status' => 'nullable|boolean',
                    'customer_group' => 'nullable|string|max:255',
                    'address_1' => 'nullable|string|max:255',
                    'address_2' => 'nullable|string|max:255',
                    'city' => 'nullable|string|max:255',
                    'state' => 'nullable|string|max:255',
                    'postcode' => 'nullable|string|max:15',
                    'country' => 'nullable|string|max:255',
                    'addresses' => 'nullable|array',
                    'addresses.*.address_1' => 'nullable|string|max:255',
                    'addresses.*.address_2' => 'nullable|string|max:255',
                    'addresses.*.city' => 'nullable|string|max:255',
                    'addresses.*.state' => 'nullable|string|max:255',
                    'addresses.*.postcode' => 'nullable|string|max:15',
                    'addresses.*.country' => 'nullable|string|max:255',
                ]);

                $customer = $this->upsertCustomer($validated, (bool)$this->update_existing);
                $customer->wasRecentlyCreated ? $this->logCreated() : $this->logUpdated();
            } catch (Exception $ex) {
                $this->logError($row, $ex->getMessage());
            }
        }
    }
}
