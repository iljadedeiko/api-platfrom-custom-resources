<?php

/*
 * @copyright C UAB NFQ Technologies
 *
 * This Software is the property of NFQ Technologies
 * and is protected by copyright law – it is NOT Freeware.
 *
 * Any unauthorized use of this software without a valid license key
 * is a violation of the license agreement and will be prosecuted by
 * civil and criminal law.
 *
 * Contact UAB NFQ Technologies:
 * E-mail: info@nfq.lt
 * https://www.nfq.lt
 */

declare(strict_types=1);

namespace App\Doctrine;

use App\Entity\CheeseListing;
use Symfony\Component\Security\Core\Security;

class CheeseListingSetOwnerListener
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function prePersist(CheeseListing $cheeseListing): void
    {
        if ($cheeseListing->getOwner()) {
            return;
        }

        if ($this->security->getUser()) {
            $cheeseListing->setOwner($this->security->getUser());
        }
    }
}
