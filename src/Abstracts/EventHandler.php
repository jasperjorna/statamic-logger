<?php

namespace MityDigital\StatamicLogger\Abstracts;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use MityDigital\StatamicLogger\Facades\StatamicLogger;
use UnhandledMatchError;

abstract class EventHandler
{
    public function handle(mixed $event): void
    {
        Log::channel('statamic-logger')
            ->info(
                json_encode($this->buildLogEntry($event))
            );
    }

    protected function buildLogEntry(mixed $event): array
    {
        // if we have a user, get their details
        $user = auth()->user();
        if ($user) {
            $user = [
                'id' => $user->id,
                'name' => $user->name,
                'model' => get_class($user),
            ];
        }

        // get the data
        $data = array_merge(
            $this->data($event),
            $this->supplement($event)
        );

        return [
            'request' => StatamicLogger::getRequestId(),
            'event' => get_class($event),
            'handler' => get_class($this),
            'user' => $user,
            'data' => $data,
        ];
    }

    abstract protected function data(mixed $event): array;

    protected function supplement(mixed $event): array
    {
        return [];
    }

    abstract public function view(): string;

    public function type(): string
    {
        // get the handler class name
        $handler = substr(get_class($this), strrpos(get_class($this), '\\') + 1);

        if (Lang::has('statamic-logger::types.'.$handler)) {
            return __('statamic-logger::types.'.$handler);
        }

        return $handler;
    }

    public function action(mixed $event): string
    {
        try {
            // if we have a verb, get it
            return $this->verb($event);
        } catch (UnhandledMatchError $e) {
            // otherwise, return the event
            return $event; // return the event by default
        }
    }

    abstract protected function verb(mixed $event): string;
}