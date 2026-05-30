<?php

namespace App\Support\Tenant;

use App\Models\Company;
use App\Models\Membership;

class TenantContext
{
    private ?Company $company = null;

    private ?Membership $membership = null;

    public function set(Membership $membership): void
    {
        $this->membership = $membership;
        $this->company = $membership->company;
    }

    public function company(): ?Company
    {
        return $this->company;
    }

    public function membership(): ?Membership
    {
        return $this->membership;
    }
}
