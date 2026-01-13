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
        $this->members = $subscriptionData->presence->hash;
        $this->count = $subscriptionData->presence->count;
        $this->me = $this->get($this->myID);
    }

    /**
     * Adds a new member to the collection. For internal use only.
     */
    public function addMember(mixed $memberData): ?object
    {
        if ($this->get($memberData->user_id) === null) {
            $this->count++;
        }
        $this->members[$memberData->user_id] = $memberData->user_info;

        return $this->get($memberData->user_id);
    }

    /**
     * Adds a member from the collection. For internal use only.
     */
    public function removeMember(mixed $memberData): ?object
    {
        $member = $this->get($memberData->user_id);
        if ($member) {
            unset($this->members[$memberData->user_id]);
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
