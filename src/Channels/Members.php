<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Channels;

use Closure;

/**
 * Represents a collection of members in a presence channel.
 */
class Members
{
    /** @var array<string, array|null> Map of user IDs to user info */
    public array $members = [];

    public int $count = 0;

    public ?string $myID = null;

    public ?Member $me = null;

    /**
     * Returns member info for the given ID.
     */
    public function get(string $id): ?Member
    {
        if (array_key_exists($id, $this->members)) {
            return new Member(
                id: $id,
                info: $this->members[$id],
            );
        }

        return null;
    }

    /**
     * Iterates over each member in the collection.
     */
    public function each(Closure $callback): void
    {
        foreach ($this->members as $id => $info) {
            $callback(new Member(id: $id, info: $info));
        }
    }

    /**
     * Sets the current user's ID. For internal use only.
     */
    public function setMyID(string $id): void
    {
        $this->myID = $id;
    }

    /**
     * Handles subscription data. For internal use only.
     */
    public function onSubscription(PresenceData $data): void
    {
        $this->members = $data->hash;
        $this->count = $data->count;
        $this->me = $this->myID !== null ? $this->get($this->myID) : null;
    }

    /**
     * Adds a new member to the collection. For internal use only.
     */
    public function addMember(MemberData $data): Member
    {
        if ($this->get($data->user_id) === null) {
            $this->count++;
        }

        $this->members[$data->user_id] = $data->user_info;

        return new Member(id: $data->user_id, info: $data->user_info);
    }

    /**
     * Removes a member from the collection. For internal use only.
     */
    public function removeMember(MemberData $data): ?Member
    {
        $member = $this->get($data->user_id);

        if ($member !== null) {
            unset($this->members[$data->user_id]);
            $this->count--;
        }

        return $member;
    }

    /**
     * Resets the collection to initial state. For internal use only.
     */
    public function reset(): void
    {
        $this->members = [];
        $this->count = 0;
        $this->myID = null;
        $this->me = null;
    }
}
