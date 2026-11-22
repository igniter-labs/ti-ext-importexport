<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Traits;

use Igniter\Flame\Exception\SystemException;
use Igniter\User\Models\Customer;
use Igniter\User\Models\CustomerGroup;

trait UpsertsCustomers
{
    use ResolvesCountries;

    protected array $customerGroupIdCache = [];

    /**
     * Create or update a customer from an import row. Passwords are never set and
     * no invite or registration emails are sent to imported customers.
     *
     * @param array{
     *     email: string,
     *     first_name?: ?string,
     *     last_name?: ?string,
     *     full_name?: ?string,
     *     telephone?: ?string,
     *     newsletter?: ?bool,
     *     status?: ?bool,
     *     customer_group?: ?string,
     *     address_1?: ?string,
     *     address_2?: ?string,
     *     city?: ?string,
     *     state?: ?string,
     *     postcode?: ?string,
     *     country?: ?string,
     *     addresses?: array<int, array<string, mixed>>,
     * } $data
     */
    protected function upsertCustomer(array $data, bool $updateExisting): Customer
    {
        $email = strtolower(trim((string)$data['email']));

        /** @var Customer|null $customer */
        $customer = Customer::query()->firstWhere('email', $email);
        throw_if($customer && !$updateExisting, new SystemException(sprintf(
            lang('igniterlabs.importexport::default.error_customer_exists'), $email,
        )));

        $customer ??= (new Customer)->forceFill(['email' => $email]);

        [$firstName, $lastName] = $this->resolveCustomerName($data);
        $attributes = array_filter([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'telephone' => $data['telephone'] ?? null,
            'newsletter' => $data['newsletter'] ?? null,
            'status' => $data['status'] ?? null,
            'customer_group_id' => $this->resolveCustomerGroupId($data['customer_group'] ?? null),
        ], fn($value): bool => !is_null($value) && $value !== '');

        $customer->forceFill($attributes);

        if (!$customer->exists) {
            $customer->first_name ??= '';
            $customer->last_name ??= '';
            $customer->status ??= true;
            $customer->last_location_area = '';
        }

        $customer->save();

        $addresses = $data['addresses'] ?? [array_only($data, ['address_1', 'address_2', 'city', 'state', 'postcode', 'country'])];
        foreach ($addresses as $address) {
            $this->addCustomerAddress($customer, $address);
        }

        return $customer;
    }

    protected function resolveCustomerName(array $data): array
    {
        $firstName = trim((string)($data['first_name'] ?? ''));
        $lastName = trim((string)($data['last_name'] ?? ''));

        if ($firstName === '' && $lastName === '' && ($fullName = trim((string)($data['full_name'] ?? ''))) !== '') {
            [$firstName, $lastName] = array_pad(preg_split('/\s+/', $fullName, 2) ?: [], 2, '');
        }

        return [$firstName ?: null, $lastName ?: null];
    }

    protected function resolveCustomerGroupId(?string $groupName): ?int
    {
        $groupName = trim((string)$groupName);

        if (!array_key_exists($groupName, $this->customerGroupIdCache)) {
            $groupId = $groupName !== ''
                ? CustomerGroup::query()->where('group_name', $groupName)->value('customer_group_id')
                : null;

            $groupId ??= CustomerGroup::getDefaultKey();

            $this->customerGroupIdCache[$groupName] = $groupId ? (int)$groupId : null;
        }

        return $this->customerGroupIdCache[$groupName];
    }

    /**
     * Adds an address to the customer unless one with the same first line and postcode exists.
     * Existing addresses are never removed.
     */
    protected function addCustomerAddress(Customer $customer, array $address): void
    {
        if (($address1 = trim((string)($address['address_1'] ?? ''))) === '') {
            return;
        }

        $customer->addresses()->firstOrCreate([
            'address_1' => $address1,
            'postcode' => ($postcode = trim((string)($address['postcode'] ?? ''))) !== '' ? $postcode : null,
        ], [
            'address_2' => $address['address_2'] ?? null,
            'city' => $address['city'] ?? null,
            'state' => $address['state'] ?? null,
            'country_id' => $this->resolveCountryId($address['country'] ?? null),
        ]);
    }
}
