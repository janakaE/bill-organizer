<?php

namespace App\Policies;

use App\User;
use App\UserRecordIssuer;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserRecordIssuerPolicy
{
    use HandlesAuthorization;

    public function show(User $user, UserRecordIssuer $record_issuer) {
        return $user->id === $record_issuer->user_id;
    }

    public function destroy(User $user, UserRecordIssuer $record_issuer) {
        return $user->id === $record_issuer->user_id;
    }
}
