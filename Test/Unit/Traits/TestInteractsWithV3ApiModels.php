<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Traits;

use Dotdigital\V3\Models\Contact;
use Dotdigital\V3\Models\Contact as DotdigitalContact;
use Dotdigital\V3\Models\ContactCollection;

trait TestInteractsWithV3ApiModels
{
    /**
     * Generate a collection of SMS contacts
     *
     * @param int $count
     * @return ContactCollection
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
     * @throws \Exception
     * @return Contact
     */
    public function generateSmsContact(): Contact
    {
        $faker = \Faker\Factory::create();
        $contact = new DotdigitalContact([
            'matchIdentifier' => 'email'
        ]);
        $contact->setIdentifiers([
            'email' => $faker->email,
            'mobileNumber' => $faker->phoneNumber
        ]);
        $contact->setLists(['123']);
        $contact->setDataFields([
                'store_name_additional' => $faker->word,
                'firstname' => $faker->firstName,
                'lastname' => $faker->lastName,
                'store_name' => $faker->word,
                'website_name' => $faker->word
        ]);
        return $contact;
    }
}
