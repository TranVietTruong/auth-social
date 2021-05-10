<?php

namespace App\Helpers;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Publisher Class helper
 */
class Publisher
{
	private $connection;
	public $queue;
	public $exchange;

	public function __construct($queue, $exchange){
		$this->queue = $queue;
		$this->exchange = $exchange;

        $this->connection = new AMQPStreamConnection(
            config('rabbitmq.connection.host'),
            config('rabbitmq.connection.port'),
            config('rabbitmq.connection.user'),
            config('rabbitmq.connection.password')
        );
	}

	/**
	 * Publisher call
	 * @param  [json] $request
	 * @return mixed
	 */
	public function call($request){
		$channel = $this->connection->channel();

		$channel->queue_declare($this->queue, false, true, false, false);

		$channel->exchange_declare($this->exchange, AMQPExchangeType::DIRECT, false, true, false);

		$channel->queue_bind($this->queue, $this->exchange);

		$message = new AMQPMessage($request, [
			'content_type' => 'text/plain', 
			'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
		]);

		$channel->basic_publish($message, $this->exchange);

		$channel->close();
		$this->connection->close();
	}
}