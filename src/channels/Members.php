<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Channels;

use ArtisanBuild\Pusher\Utils\Collections;
use Closure;

/**
 * Represents a collection of members of a presence channel.
 */
class Members
{
    public array $members;

    public int $count;

    public mixed $myID;

    public mixed $me;

    public function __construct()
    {
        $this->reset();
    }

    /**
     * Returns member's info for given id.
     *
     * Resulting object containts two fields - id and info.
     */
    public function get(string $id): ?object
    {
        if (array_key_exists($id, $this->members)) {
            return (object) [
                'id' => $id,
                'info' => $this->members[$id],
            ];
        } else {
            return null;
        }
    }

    /**
     * Calls back for each member in unspecified order.
     */
    public function each(Closure $callback): void
    {
        Collections::objectApply(
            (object) $this->members,
            fn (mixed $member, mixed $id): mixed => $callback($this->get((string) $id)),
        );
    }

    /**
     * Updates the id for connected member. For internal use only.
     */
    public function setMyID(string $id): void
    {
        $this->myID = $id;
    }

    /**
     * Handles subscription data. For internal use only.
     */
    public function onSubscription(mixed $subscriptionData): void
    {
        // Handle both array and object access patterns
        if (is_array($subscriptionData)) {
            $presence = $subscriptionData['presence'] ?? [];
            $this->members = is_array($presence) ? ($presence['hash'] ?? []) : (array) ($presence->hash ?? []);
            $this->count = is_array($presence) ? ($presence['count'] ?? 0) : ($presence->count ?? 0);
        } else {
            $this->members = (array) ($subscriptionData->presence->hash ?? []);
            $this->count = $subscriptionData->presence->count ?? 0;
        }
        $this->me = $this->get($this->myID);
    }

    /**
     * Adds a new member to the collection. For internal use only.
     */
    public function addMember(mixed $memberData): ?object
    {
        $userId = is_array($memberData) ? ($memberData['user_id'] ?? null) : ($memberData->user_id ?? null);
        $userInfo = is_array($memberData) ? ($memberData['user_info'] ?? null) : ($memberData->user_info ?? null);

        if ($userId === null) {
            return null;
        }

        if ($this->get($userId) === null) {
            $this->count++;
        }
        $this->members[$userId] = $userInfo;

        return $this->get($userId);
    }

    /**
     * Removes a member from the collection. For internal use only.
     */
    public function removeMember(mixed $memberData): ?object
    {
        $userId = is_array($memberData) ? ($memberData['user_id'] ?? null) : ($memberData->user_id ?? null);

        if ($userId === null) {
            return null;
        }

        $member = $this->get($userId);
        if ($member) {
            unset($this->members[$userId]);
            $this->count--;
        }

        return $member;
    }

    /**
     * Resets the collection to the initial state. For internal use only.
     */
    public function reset(): void
    {
        $this->members = [];
        $this->count = 0;
        $this->myID = null;
        $this->me = null;
    }
}
