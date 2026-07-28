<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Traits;

use Dotdigital\V3\Models\Contact;
use Dotdigital\V3\Models\Contact as DotdigitalContact;
use Dotdigital\V3\Models\ContactCollection;

trait TestInteractsWithV3ApiModels
{
    /**
     * @var int
     */
    private static int $generatedContactCounter = 0;

    /**
     * Generate a collection of SMS contacts
     *
     * @param int $count
     *
     * @return array
     * @throws \Exception
     */
    public function generateBulkImportSmsContacts(int $count): array
    {
        $contacts = [];
        for ($i = 0; $i < $count; $i++) {
            $contact = $this->generateSmsContact();
            $contact->setContactId($i);
            $contacts[] = $contact;
        }
        return $contacts;
    }

    /**
     * Generate a single SMS contact
     *
     * @return Contact
     */
    public function generateSmsContact(): Contact
    {
        $index = ++self::$generatedContactCounter;
        $contact = new DotdigitalContact([
            'matchIdentifier' => 'email'
        ]);
        $contact->setIdentifiers([
            'email' => sprintf('contact_%d@example.com', $index),
            'mobileNumber' => sprintf('+447700%06d', $index)
        ]);
        $contact->setLists(['123']);
        $contact->setDataFields([
                'store_name_additional' => sprintf('store_additional_%d', $index),
                'firstname' => sprintf('First%d', $index),
                'lastname' => sprintf('Last%d', $index),
                'store_name' => sprintf('store_%d', $index),
                'website_name' => sprintf('website_%d', $index)
        ]);
        return $contact;
    }
}
