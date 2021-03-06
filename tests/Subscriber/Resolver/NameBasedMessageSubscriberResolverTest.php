<?php

namespace SimpleBus\Message\Tests\Subscriber\Resolver;

use SimpleBus\Message\Handler\MessageHandler;
use SimpleBus\Message\Message;
use SimpleBus\Message\Name\MessageNameResolver;
use SimpleBus\Message\Subscriber\Collection\MessageSubscriberCollection;
use SimpleBus\Message\Subscriber\Resolver\NameBasedMessageSubscriberResolver;

class NameBasedMessageSubscriberResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_returns_message_subscribers_from_the_handler_collection_by_its_name()
    {
        $message = $this->dummyMessage();
        $messageName = 'message_name';
        $messageHandler = $this->dummyMessageHandler();

        $messageNameResolver = $this->stubMessageNameResolver($message, $messageName);
        $messageHandlerCollection = $this->stubMessageSubscribersCollection([$messageName => $messageHandler]);

        $nameBasedHandlerResolver = new NameBasedMessageSubscriberResolver(
            $messageNameResolver,
            $messageHandlerCollection
        );

        $this->assertSame($messageHandler, $nameBasedHandlerResolver->resolve($message));
    }

    private function dummyMessageHandler()
    {
        return $this->getMock('SimpleBus\Message\Handler\MessageHandler');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Message
     */
    private function dummyMessage()
    {
        return $this->getMock('SimpleBus\Message\Message');
    }

    /**
     * @param $message
     * @param $messageName
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageNameResolver
     */
    private function stubMessageNameResolver($message, $messageName)
    {
        $messageNameResolver = $this->getMock('SimpleBus\Message\Name\MessageNameResolver');

        $messageNameResolver
            ->expects($this->any())
            ->method('resolve')
            ->with($this->identicalTo($message))
            ->will($this->returnValue($messageName));

        return $messageNameResolver;
    }

    /**
     * @param MessageHandler[] $messageSubscribersByMessageName
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageSubscriberCollection
     */
    private function stubMessageSubscribersCollection(array $messageSubscribersByMessageName)
    {
        $messageSubscribersCollection = $this->getMock(
            'SimpleBus\Message\Subscriber\Collection\MessageSubscriberCollection'
        );
        $messageSubscribersCollection
            ->expects($this->any())
            ->method('subscribersByMessageName')
            ->will(
                $this->returnCallback(
                    function ($messageName) use ($messageSubscribersByMessageName) {
                        return $messageSubscribersByMessageName[$messageName];
                    }
                )
            );

        return $messageSubscribersCollection;
    }
}
