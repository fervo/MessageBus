<?php

namespace SimpleBus\Message\Tests\Recorder;

use Exception;
use SimpleBus\Message\Bus\MessageBus;
use SimpleBus\Message\Message;
use SimpleBus\Message\Recorder\HandlesRecordedMessagesMiddleware;
use SimpleBus\Message\Recorder\ContainsRecordedMessages;
use SimpleBus\Message\Tests\Fixtures\NextCallableSpy;

class HandlesRecordedMessagesMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_handles_recorded_messages()
    {
        $messages = [$this->dummyMessage(), $this->dummyMessage()];
        $messageRecorder = $this->mockMessageRecorder();

        // first recorded messages should be fetched
        $messageRecorder
            ->expects($this->at(0))
            ->method('recordedMessages')
            ->will($this->returnValue($messages));

        // then immediately erased
        $messageRecorder
            ->expects($this->at(1))
            ->method('eraseMessages');

        $actuallyHandledMessages = [];
        $messageBus = $this->messageBusSpy($actuallyHandledMessages);
        $middleware = new HandlesRecordedMessagesMiddleware(
            $messageRecorder,
            $messageBus
        );

        $next = new NextCallableSpy();

        $middleware->handle($this->dummyMessage(), $next);
        $this->assertSame(1, $next->hasBeenCalled());
        $this->assertSame($messages, $actuallyHandledMessages);
    }

    /**
     * @test
     */
    public function it_rethrows_a_caught_exception_but_first_clears_any_recorded_messages()
    {
        $messageRecorder = $this->mockMessageRecorder();

        $middleware = new HandlesRecordedMessagesMiddleware($messageRecorder, $this->dummyMessageBus());

        $exception = new Exception();
        $nextAlwaysFails = function() use ($exception) {
            throw $exception;
        };

        $messageRecorder
            ->expects($this->once())
            ->method('eraseMessages');

        try {
            $middleware->handle($this->dummyMessage(), $nextAlwaysFails);

            $this->fail('An exception should have been thrown');
        } catch (Exception $actualException) {
            $this->assertSame($exception, $actualException);
        }
    }

    /**
     * @param array $actuallyHandledMessages
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageBus
     */
    private function messageBusSpy(array &$actuallyHandledMessages)
    {
        $messageBus = $this->getMock('SimpleBus\Message\Bus\MessageBus');

        $messageBus
            ->expects($this->any())
            ->method('handle')
            ->will(
                $this->returnCallback(
                    function (Message $message) use (&$actuallyHandledMessages) {
                        $actuallyHandledMessages[] = $message;
                    }
                )
            );

        return $messageBus;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Message
     */
    private function dummyMessage()
    {
        return $this->getMock('SimpleBus\Message\Message');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ContainsRecordedMessages
     */
    private function mockMessageRecorder()
    {
        return $this->getMock('SimpleBus\Message\Recorder\ContainsRecordedMessages');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageBus
     */
    private function dummyMessageBus()
    {
        return $this->getMock('SimpleBus\Message\Bus\MessageBus');
    }
}
