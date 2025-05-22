<?php

namespace Dusterio\PlainSqs\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatcherJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected mixed $data;

    protected bool $plain = false;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getPayload(): mixed
    {
        if (!$this->isPlain()) {
            return [
                'job' => app('config')->get('sqs-plain.default-handler'),
                'data' => $this->data
            ];
        }

        return $this->data;
    }

    public function setPlain($plain = true): static
    {
        $this->plain = $plain;

        return $this;
    }

    public function isPlain(): bool
    {
        return $this->plain;
    }
}