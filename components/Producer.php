<?php

namespace mikemadisonweb\rabbitmq\components;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Producer, that publishes AMQP Messages
 */
class Producer extends BaseRabbitMQ implements ProducerInterface
{
    protected $contentType = 'text/plain';
    protected $deliveryMode = 2;

    /**
     * @param $contentType
     * @return $this
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
        return $this;
    }

    /**
     * @param $deliveryMode
     * @return $this
     */
    public function setDeliveryMode($deliveryMode)
    {
        $this->deliveryMode = $deliveryMode;
        return $this;
    }

    /**
     * @return array
     */
    protected function getBasicProperties()
    {
        return [
            'content_type' => $this->contentType,
            'delivery_mode' => $this->deliveryMode,
        ];
    }

    /**
     * Publishes the message and merges additional properties with basic properties
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     * @param array $headers
     */
    public function publish($msgBody, $routingKey = '', $additionalProperties = [], array $headers = null)
    {
        if ($this->autoSetupFabric) {
            $this->setupFabric();
        }
        if (is_array($msgBody)) {
            $msgBody = serialize($msgBody);
        }
        if (!is_string($msgBody)) {
            $msgBody = (string)$msgBody;
        }
        $msg = new AMQPMessage($msgBody, array_merge($this->getBasicProperties(), $additionalProperties));
        if (!empty($headers)) {
            $headersTable = new AMQPTable($headers);
            $msg->set('application_headers', $headersTable);
        }
        $this->getChannel()->basic_publish($msg, $this->exchangeOptions['name'], (string)$routingKey);
        if ($this->logger['enable']) {
            \Yii::info([
                'info' => 'AMQP message published',
                'amqp' => [
                    'body' => $msgBody,
                    'routingkeys' => $routingKey,
                    'properties' => $additionalProperties,
                    'headers' => $headers,
                ],
            ], $this->logger['category']);
        }
    }
}
