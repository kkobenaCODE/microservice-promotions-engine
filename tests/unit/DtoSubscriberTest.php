<?php

namespace App\Tests\unit;

use App\DTO\LowestPriceEnquiry;
use App\Event\AfterDTOCreatedEvent;
use App\Tests\ServiceTestCase;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DtoSubscriberTest extends ServiceTestCase
{
    /** @test */
    public function a_dto_is_validated_after_it_has_been_created(): void
    {
        //given
        $dto = new LowestPriceEnquiry();
        $dto->setQuantity(-5);

        $event = new AfterDTOCreatedEvent($dto);

        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->container->get('debug.event_dispatcher');
        //expect

        $this->expectException(ValidationFailedException::class);
        $this->expectErrorMessage('This value should be positive.');

        $eventDispatcher->dispatch($event, $event::NAME);


    }

}