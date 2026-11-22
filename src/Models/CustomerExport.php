<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Models;

use Igniter\User\Models\Address;
use Igniter\User\Models\Customer;
use Override;

/**
 * CustomerExport Model Class
 *
 * Exports one row per customer using the same columns as CustomerImport,
 * so an exported file can be imported again without remapping columns.
 */
class CustomerExport extends ExportModel
{
    protected $table = 'customers';

    protected $primaryKey = 'customer_id';

    #[Override]
    public function exportData(array $columns, array $options = []): array
    {
        $query = Customer::query()->with(['group', 'address', 'addresses'])->orderBy('customer_id');

        if ($offset = array_get($options, 'offset')) {
            $query->offset($offset);
        }

        if ($limit = array_get($options, 'limit')) {
            $query->limit($limit);
        }

        return $query->get()->map(fn(Customer $customer): array => $this->mapCustomer($customer))->all();
    }

    protected function mapCustomer(Customer $customer): array
    {
        /** @var Address|null $address */
        $address = $customer->address ?? $customer->addresses->first();

        return [
            'email' => $customer->email,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'full_name' => trim($customer->first_name.' '.$customer->last_name),
            'telephone' => $customer->telephone,
            'newsletter' => (int)$customer->newsletter,
            'status' => (int)$customer->status,
            'customer_group' => $customer->group?->group_name,
            'address_1' => $address?->address_1,
            'address_2' => $address?->address_2,
            'city' => $address?->city,
            'state' => $address?->state,
            'postcode' => $address?->postcode,
            'country' => $this->countryCode($address?->country_id),
        ];
    }
}
