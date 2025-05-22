<?php

namespace Dusterio\PlainSqs\Sqs;

use Dusterio\PlainSqs\Jobs\DispatcherJob;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Queue\Jobs\SqsJob;

class Queue extends SqsQueue
{
    protected function createPayload($job, $queue, $data = '', $delay = null)
    {
        if (!$job instanceof DispatcherJob) {
            return parent::createPayload($job, $queue, $data);
        }

        $handlerJob = $this->getClass($queue) . '@handle';

        return $job->isPlain() ? json_encode($job->getPayload()) : json_encode(['job' => $handlerJob, 'data' => $job->getPayload()]);
    }

    private function getClass($queue = null): string
    {
        if (!$queue) {
            return Config::get('sqs-plain.default-handler');
        }
        $queue = explode('/', $queue);
        $queue = end($queue);

        return (array_key_exists($queue, Config::get('sqs-plain.handlers')))
            ? Config::get('sqs-plain.handlers')[$queue]
            : Config::get('sqs-plain.default-handler');
    }

    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $queue,
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);

        if (isset($response['Messages']) && count($response['Messages']) > 0) {
            $queueId = explode('/', $queue);
            $queueId = array_pop($queueId);

            $class = (array_key_exists($queueId, $this->container['config']->get('sqs-plain.handlers')))
                ? $this->container['config']->get('sqs-plain.handlers')[$queueId]
                : $this->container['config']->get('sqs-plain.default-handler');

            $response = $this->modifyPayload($response['Messages'][0], $class);

            return new SqsJob($this->container, $this->sqs, $response, $this->connectionName, $queue);
        }

        return null;
    }

    private function modifyPayload($payload, $class)
    {
        if (! is_array($payload)) $payload = json_decode($payload, true);

        $body = json_decode($payload['Body'], true);

        $body = [
            'job' => $class . '@handle',
            'data' => isset($body['data']) ? $body['data'] : $body,
            'uuid' => $payload['MessageId']
        ];

        $payload['Body'] = json_encode($body);

        return $payload;
    }

    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $payload = json_decode($payload, true);

        if (isset($payload['data']) && isset($payload['job'])) {
            $payload = $payload['data'];
        }

        return parent::pushRaw(json_encode($payload), $queue, $options);
    }
}
