<?php

namespace Dusterio\PlainSqs\Sqs;

use Aws\Sqs\SqsClient;
use Illuminate\Support\Arr;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class Connector extends SqsConnector
{
    /**
     * Establish a queue connection.
     */
    public function connect(array $config): QueueContract
    {
        $config = $this->getDefaultConfiguration($config);

        if (isset($config['key']) && isset($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);
        }

        return new Queue(
            new SqsClient($config),
            $config['queue'],
            Arr::get($config, 'prefix', '')
        );
    }
}